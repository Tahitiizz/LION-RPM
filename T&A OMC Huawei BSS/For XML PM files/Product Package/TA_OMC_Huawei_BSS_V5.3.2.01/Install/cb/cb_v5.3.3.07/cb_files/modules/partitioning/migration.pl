#!/usr/bin/perl -w
use DBI;
use strict;
use threads;
use Thread::Queue;
use Time::Local;
use Data::Dumper;
use Time::HiRes;
use POSIX;
# Ce script migre le schema d'une base iu
# On partitionne automatiquement par heure toutes les tables en _hour et _hour_bh
# On partitionne automatiquement par jour toutes les tables en _day et _day_bh
# Et par semaine les week*, et mois les month*
# On récupere la definition des tables pour recreer les tables parentes
# On récupère le min et le max de hour ou de day suivant le cas, pour créer toutes les partitions
# On a une table a partitionner par jour
# On recherche le min et le max de la table pour trouver les partitions a generer
# On recherche les index de la table
# On renomme la table
# On cree une nouvelle table avec le meme nom
# On cree les partitions
# On insere les donnees dans les partitions
# On cree les index sur les partitions
# On drop la vieille table
# Il y a 4 fonctions différentes pour hour, day, week, et month. Cela fait un peu de code dupliqué, mais c'est au cas
# où des cas particuliers apparaissaient pour certains types de tables.


my $q;

# Cette valeur sert à afficher l'etat d'avancement. Elle est globale
# pour alléger les passages de paramètre
# Globale car créée et renseignée avant le démarrage des thread. Ils voient donc
# tous la même valeur
my $nb_tables_a_traiter=0;


# Paramètres par défaut
# On peut rajouter un host= et un port= dans connexion_string, si nécessaire.
# La syntaxe complète est détaillée ici : http://search.cpan.org/dist/DBD-Pg/Pg.pm#connect
# Il est préférable de les modifier dans le fichier de configuration, seul argument du script
my $max_threads=12;
my $debug=1;
my $connexion_string;
my $connexion_user;
my $week_starts_on_monday;

# Cette fonction charge le fichier de configuration passé en ligne de commande
# Format ini basique
sub charge_config
{
	my ($conf_file)=@_;
	open CONF,"$conf_file" or die "Impossible d'ouvrir $conf_file, $!\n";
	while (my $ligne=<>)
	{
		$ligne =~ s/#.*//; # Supprimer les commentaires
		$ligne =~ s/\s+$//; # Supprimer les blancs de fin de ligne
		$ligne =~ s/^\s+//; # Supprimer les blancs de début de ligne
		next if ($ligne eq '');
		$ligne =~ /^(.*?)\s*=\s*(.*)$/ or die "Impossible d'interpréter <$ligne> dans le fichier de configuration\n";
		if ($1 eq 'connexion string')
		{
			$connexion_string=$2;
		}
		elsif ($1 eq 'connexion user')
		{
			$connexion_user=$2;
		}
		elsif ($1 eq 'max threads')
		{
			$max_threads=$2;
		}
		elsif ($1 eq 'debug')
		{
			$debug=$2;
		}
	}
	close CONF;
}

# Cette fonction genere un ID unique, pour les contraintes check
sub genere_uniqueid
{
	my $unique=sprintf('%x',int(Time::HiRes::time()*1000000+rand(10000000000000)));
	return $unique;
}


# À cette fonction, on passe une liste. Cette liste va être coupée en 2 sous listes, attachées par référence 
# Dans un tableau contenant la valeur de découpage, puis les références des deux listes.
# Il transforme donc une liste en arbre binaire balancé
sub coupe_arbre
{
	my ($ref_arbre)=@_;
	my $taille_arbre=scalar(@$ref_arbre);
	if ($taille_arbre == 0)
	{
		my @retour=();
		return \@retour;
	}
	if ($taille_arbre == 1)
	{
		# On est au bout de la récursion, rien à découper, la valeur est la bonne. Même pas la peine de la retourner, 
		# c'est une valeur qui a ete testee auparavant
		my @retour=($ref_arbre->[0]);
		return (undef);
	}
	if ($taille_arbre == 2)
	{
		# On a deux elements. On doit donc faire un dernier test pour savoir qui est
		# La bonne
		# on test sur le second element de la liste. Si on est inferieur, c'est
		# Que c'est le premier element qui est bon
		my @retour=($ref_arbre->[1],[$ref_arbre->[0]]);
		return (\@retour);
	}
	# Dans le cas normal (3 ou plus), on coupe la liste en 2, et on retourne 2 sous listes:
	# La limite de decoupage est dans la seconde liste. La limite de decoupage est
	# la valeur sur laquelle on va faire le IF dans le code final (IF cle < limite)
	my $limite_decoupage=int($taille_arbre/2)+1;
	my $valeur_decoupage=$ref_arbre->[$limite_decoupage];
	my @tableau_avant=@{$ref_arbre}[0..$limite_decoupage-1];
	my @tableau_apres=@{$ref_arbre}[$limite_decoupage..$taille_arbre-1];
	# On fait 2 recursions, sur chacun de ces deux tableaux
	my $ref_arbre_avant=coupe_arbre(\@tableau_avant);
	my $ref_arbre_apres=coupe_arbre(\@tableau_apres);
	
	my @retour=($valeur_decoupage,$ref_arbre_avant,$ref_arbre_apres);
	return (\@retour);
}


# C'est simplement une fonction pour le formatage de l'arbre de IF. Indente suivant la profondeur de récursion.
sub recurse_indent
{
	my ($chaine,$recurse_level)=@_;
	return '     ' x $recurse_level . $chaine;
}


# Fonction de recursion de generation de la clause IF
sub recurse_if
{
	my ($ref_arbre,$ref_crit_partition,$cle_partitionnement,$recurse_level)=@_;
	$recurse_level++;
	# 3 cas: 
	# l'arbre ne contient qu'une entree, dans ce cas pas de test à faire, on connait la partition
	# l'arbre contient 2 entrees: une valeur charnière et une liste d'éléments plus petites
	# l'arbre contient 3 entrées: une valeur charnière et 2 listes d'éléments
	my $partition=$ref_crit_partition->{$ref_arbre->[0]};
	my $retour;
	if (not defined $ref_arbre->[1])
	{
		# On n'a qu'un element. Il est deja defini par les tests ayant amene ici
		my $retour=recurse_indent("  INSERT INTO ${partition} VALUES (NEW.*);\n",$recurse_level);
		$retour.=recurse_indent("  RETURN NULL;\n",$recurse_level);
		return $retour;
	}
	elsif (not defined $ref_arbre->[2])
	{
		# On a une valeur charnière sur laquelle faire un test. On doit tester l'infériorité
		$retour=recurse_indent("IF NEW.$cle_partitionnement < " . $ref_arbre->[0] . " THEN\n",$recurse_level);
		# Recursion
		$retour.=recurse_if($ref_arbre->[1],$ref_crit_partition,$cle_partitionnement,$recurse_level);
		$retour.=recurse_indent("ELSE\n",$recurse_level);
		$retour.=recurse_indent("  INSERT INTO ${partition} VALUES (NEW.*);\n",$recurse_level);
		$retour.=recurse_indent("  RETURN NULL;\n",$recurse_level);
		$retour.=recurse_indent("END IF;\n",$recurse_level);
	}
	else
	{
		# On a une valeur charnière sur laquelle faire un test. Si inférieur, il faut
		# aller faire le test de la branche 1. Sinon, faire le test de la branche 2.
		# Le else de la branche 2 est la valeur charnière
		$retour=recurse_indent("IF NEW.$cle_partitionnement < " . $ref_arbre->[0] . " THEN\n",$recurse_level);
		# Recursion
		$retour.=recurse_if($ref_arbre->[1],$ref_crit_partition,$cle_partitionnement,$recurse_level);
		$retour.=recurse_indent("ELSE\n",$recurse_level);
		$retour.=recurse_if($ref_arbre->[2],$ref_crit_partition,$cle_partitionnement,$recurse_level);
		$retour.=recurse_indent("END IF;\n",$recurse_level);
	}
	
	return $retour;
}




# Cette fonction génère un arbre de IF pour déterminer la partition dans
# laquelle insérer
# On lui passe:
# Une liste TRIEE de critères de partitionnement, associés à la partition destinataire
# Le parametre d'appel est donc un tableau de tableaux
sub	genere_if
{
	my ($ref_donnees,$cle_partition)=@_;
	# Generation des structures de travail:
	# Une liste de critères
	# Un tableau associatif critère => partition
	my @liste_criteres;
	my %critere_partition;
	
	foreach my $record (@$ref_donnees)
	{
		push @liste_criteres,($record->[0]);
		$critere_partition{$record->[0]}=$record->[1];
	}
	@liste_criteres=sort(@liste_criteres);
	my $arbre;
	# coupe_arbre ne marche pas si liste_criteres est de taille 1 (cas particulier, il ne peut pas démarrer la récursion)
	if (scalar(@liste_criteres) == 1)
	{
		# Pas la peine de générer un arbre, et d'aller dans recurse_if
		my $retour="INSERT INTO " . $ref_donnees->[0]->[1] . " VALUES (NEW.*);\nRETURN NULL;\n";
		return $retour;
	}
	else
	{
		$arbre=coupe_arbre(\@liste_criteres);
	}
	# On a donc un arbre balance en entree, il n'y a plus qu'à s'en servir pour generer recursivement les IF/END IF
	return recurse_if($arbre,\%critere_partition,$cle_partition);
}


# Worker de chaque thread. Il récupère une table à traiter dans la file d'attente $q, et lance le travail associé
sub thread_worker
{
	my ($id)=@_;
	while (my $todo=$q->dequeue_nb(1))
	{
		no strict 'refs';
		my ($sub,$oid,$relname)=split(/\|\|\|/,$todo);
		&$sub($oid,$relname,$id);
	}
}

# Print qui rajoute le pourcentage d'avancement
sub formatted_print
{
	my ($message,$id)=@_;
	if ($debug)
	{
		my $taille_queue=$q->pending;
		my $traites=$nb_tables_a_traiter-$taille_queue;
		my $pourcentage=int((100*$traites)/$nb_tables_a_traiter);
		print ":$traites/$nb_tables_a_traiter ($pourcentage \%) $id: $message\n";
	}
}

# Fonctions de calcul basiques de calendrier. Pour limiter le nombre de librairies en dépendance

# Ajouter un mois à un champ month formaté T&A
sub ajoute_month
{
	my ($formatmonth)=@_;
	$formatmonth=~ /(....)(..)/;
	my ($annee,$mois)=($1,$2);
	$mois++;
	if ($mois==13)
	{
		$annee++;
		$mois='01';
	}
	return $annee.$mois;
}

# Retourne le ctime du premier jour d'une annee donnee. Fonction utilitaire de «ajoute_week»
# Il ne s'agit pas de retourner le premier janvier, mais le premier jour étant dans une semaine de l'année
# Le 1er janvier est souvent dans une semaine de l'année précédente.
sub trouve_premier_jour_annee
{
	my ($annee)=@_;
	# Premier janvier.
	my $timet=timegm(0,0,0,1,0,$annee-1900);
	while (not (	POSIX::strftime("%V", gmtime $timet) == 1))
	{
		$timet+=86400;
	}
	return $timet;
}

# Ajouter une semaine à un champ week formaté T&A
sub ajoute_week
{
	my ($formatmonth)=@_;
	$formatmonth=~ /(....)(..)/;
	my ($annee,$week)=($1,$2);
	# Récupération du premier jour de cette année là
	my $time_first=trouve_premier_jour_annee($annee);
	# Jour associé au début de la semaine suivante
	my $time=$time_first+7*86400*($week);
	
	my $newweek=POSIX::strftime("%G%V", gmtime $time);
	return $newweek;
}

# Ajouter un jour à un champ day formaté T&A
sub ajoute_jour
{
	my ($formatjour)=@_;
	$formatjour =~ /(\d{4})(\d{2})(\d{2})/;
	my ($annee,$mois,$jour)=($1,$2,$3);
	# mise en conformite format localtime
	$mois--;
	$annee=$annee-1900;
	my $time=timelocal(0,0,0,$jour,$mois,$annee);
	# Ajout d'un jour
	$time=$time+95000;
	(undef,undef,undef,$jour,$mois,$annee)=localtime($time);
	# Retour aux dates normales
	$annee=$annee+1900;
	$mois=$mois+1;
	my $retour=sprintf('%04d%02d%02d',$annee,$mois,$jour);
	return $retour;
}

# Ajouter une heure à un champ hour formaté T&A
sub ajoute_heure
{
	my ($i)=@_;
	$i++;
	if ($i % 100 == 24)
	{
		# On a atteint la 24e heure. On repasse donc le compteur à zero
		$i=~ /^(\d{8})/;
		my $jour=$1;
		$i = ajoute_jour($jour) * 100;
	}
	return $i;
}

sub de_5minutes_a_heure
{
	my ($i)=@_;
	# Rajout du 20 au début
	$i = '20'.$i;
	# Suppression des minutes
	$i = int($i / 100);
	return $i
}

sub de_heure_a_5minutes
{
	my ($i)=@_;
	# Suppression du 20 du début
	$i =~ /^20(.*)$/ or die "Anomalie dans de_heure_a_5minutes\n";
	$i = $1;
	$i = $i . '00';
	return $i;
}

# Ajouter 5 minutes à un champ 5minutes T&A
sub ajoute_5minutes
{
	my ($i)=@_;
	# On rajoute 5 minutes.
	$i+=5;
	# Si on arrive à 60 minutes, on appelle ajoute heure, après avoir reformatté au format complet
	# (le format 5 minutes n'a pas les deux premiers chiffres de l'année)
	if ($i =~ /60$/)
	{
		$i=de_5minutes_a_heure($i);
		$i=ajoute_heure($i);
		$i=de_heure_a_5minutes($i);
	}
	return $i;
}



# Extraire le format semaine à partir du format day
# Pas garanti portable en dehors d'un système Linux
# Utilisé pour calculer la semaine associée à un jour, pour partitionner les tables day (il y a 3 contraintes check sur ces tables)
sub week_de_day
{
	my ($day)=@_;
	$day=~/(....)(..)(..)/ or die "Impossible de comprendre le day : $day\n";
	my ($annee,$mois,$jour)=($1,$2,$3);
	$annee-=1900;
	$mois--;
	my $timegm=timegm(0,0,0,$jour,$mois,$annee);
	my $week;
	# 28/11/2013 GFS - Bug 37447 - [SUP][TA HPG][AVP 39655][MCI Iran]: Partitioning failed when "week start on monday"='6'
	if($week_starts_on_monday == "0") {
		# Decalage de 1 jour pour simuler le debut de la semaine au dimanche
		$timegm+=60*60*24;
	} 
	elsif($week_starts_on_monday == "6") {
		# Decalage de 2 jours pour simuler le debut de la semaine au samedi
		$timegm+=2*60*60*24;
	}
	$week=POSIX::strftime("%G%V", gmtime $timegm);
	return $week;
}

# Cette fonction reçoit une référence vers une liste d'index en premier paramètre
# Les paramètres suivants sont la liste des colonnes à supprimer de ces index
# On supprime ces colonnes des index, pour commencer
# Puis on supprime les index en doublons et les index vides
sub reduit_index
{
	my ($ref_index,@liste_cles_a_supprimer)=@_;
	foreach my $index (@$ref_index)
	{
		$index =~ /CREATE INDEX (\S+) ON (\S+) USING btree \((.*?)\)/ or die "Impossible de comprendre $index\n";
		my ($index_name,$table_name,$index_cols)=($1,$2,$3);
		# Découpage des colonnes
		my @colonnes=split(/\s*,\s*/,$index_cols);
		foreach my $cle (@liste_cles_a_supprimer)
		{
			# La cle pourrait avoir un nom reserve, comme "day", et donc etre entre ""
			@colonnes=grep(!/^"?$cle"?$/,@colonnes);
		}
		# Si on a encore des colonnes restantes:
		if (scalar(@colonnes))
		{
			my $newcols=join(',',@colonnes);
			$index =~ s/$index_cols/$newcols/;
		}
		else
		{
			# Suppression de l'index
			$index=undef;
		}
	}
	# Recherche de doublons dans les index:
	for (my $i=0;$i<scalar(@$ref_index);$i++)
	{
		next if (not defined $ref_index->[$i]);
		$ref_index->[$i] =~ /CREATE INDEX \S+ ON \S+ USING btree \((.*?)\)/;
		my $coldefi=$1;
		for (my $j=$i+1;$j<scalar(@$ref_index);$j++)
		{
			next if (not defined $ref_index->[$j]);
			$ref_index->[$j] =~ /CREATE INDEX \S+ ON \S+ USING btree \((.*?)\)/;
			my $coldefj=$1;
			if ($coldefi eq $coldefj)
			{
				# On a trouve un doublon, on invalide la definition du second index
				$ref_index->[$j] = undef;
			}
		}
		# On va retourner le tableau, mais propre:
		# Creation d'un tableau temporaire
		my @tmparray;
		while (scalar(@$ref_index))
		{
			my $elt=pop(@$ref_index);
			next unless (defined $elt);
			unshift @tmparray,($elt);
		}
		return @tmparray;
	}
}

# Cette fonction recupere tous les index d'une table. On lui passe en parametres
# L'oid de la table, un dbh valide
sub recup_index_table
{
	my ($tableoid,$dbh)=@_;
	my $result=$dbh->selectall_arrayref("SELECT pg_get_indexdef(indexrelid) from pg_index where indrelid=$tableoid");
	# On transforme le select à deux dimensions (une inutile) en tableau à une dimension
	my @indexes;
	foreach my $record (@$result)
	{
		push @indexes,($record->[0]);
	}
	return @indexes;
}

# Cette fonction récupère le min et le max d'un champ d'une table (celui utilisé pour le partitionnement)
# Cela permet ensuite de ne créer que les partitions nécessaires
sub recup_min_max_partition
{
	my ($champ,$relname,$dbh)=@_;
	my $result=$dbh->selectall_arrayref("SELECT min($champ),max($champ) from $relname");
	my $min=$result->[0]->[0];
	my $max=$result->[0]->[1];
	return ($min,$max);
}

# Détermine si day, week, etc… doit avoir du bh dans le nom de la table
sub suffixe_bh
{
	my ($relname)=@_;
	my $suffixe='';
	if ($relname =~ /_bh$/)
	{
		$suffixe='_bh';
	}
	return $suffixe;
}

############# Fonctions de traitement month, week, day, hour ######################


# Traitement des tables de type month et month_bh
# Contrainte sur month uniquement
sub traite_month
{
	my ($oid,$relname,$tid)=@_;
	formatted_print("Traitement de $relname.",$tid);
	my $dbh=DBI->connect($connexion_string,$connexion_user,'')
		or die "Impossible de se connecter.\n";
	
	my $month='month'.suffixe_bh($relname);
	
	# Recherche des min et max
	my ($min,$max)=recup_min_max_partition($month,$relname,$dbh);
	# Si on n'a pas de min, on ne peut pas partitionner
	if (not defined $min)
	{
		formatted_print(" Non partitionnable.",$tid);
		return 0;
	}

	# Generation de la liste des partitions
	my @partitions;
	my $i=$min;
	while ($i <= $max)
	{
		push @partitions,($i);
		$i=ajoute_month($i);
	}

	# Recherche des index
	my @indexes_tmp=recup_index_table($oid,$dbh);
	# Generation des ordres SQL pour les nouveaux index:
	my @indexes=reduit_index(\@indexes_tmp,$month);
	# On va commencer le renommage, la creation d'objets, etc
	# On démarre une transaction
	$dbh->begin_work();
	# Renommage de la vieille table
	$dbh->do("ALTER TABLE $relname RENAME TO ${relname}_tmpreorg") or exit(127);
	# Creation d'une nouvelle table ayant la même structure
	$dbh->do("CREATE TABLE $relname (LIKE ${relname}_tmpreorg INCLUDING DEFAULTS INCLUDING CONSTRAINTS)") or exit(127);
	# Creation des tables filles et remplissage
	formatted_print("Création des partitions.",$tid);
	foreach my $partition_month (@partitions)
	{
		$dbh->do("CREATE TABLE ${relname}_${partition_month}
		          (
					      CONSTRAINT cst_".genere_uniqueid()."_${partition_month}_month_check CHECK ($month = $partition_month) 
						  ) INHERITS ($relname)") or exit(127);
	}
	# Generation du trigger
	formatted_print("Création du trigger.",$tid);
	my $trigger="CREATE OR REPLACE FUNCTION ${relname}_i_trig_f()\nRETURNS TRIGGER AS \$\$\nBEGIN\n";

	my @tableau_genere_if;
	foreach my $partition(@partitions)
	{
		push @tableau_genere_if,([$partition,"${relname}_${partition}"]);
	}
	$trigger.=genere_if(\@tableau_genere_if,$month);
	$trigger.="RAISE EXCEPTION 'Fin du trigger sans avoir trouve de partition';\n";
	$trigger.="END;\n\$\$\nLANGUAGE plpgsql;";
#	formatted_print($trigger,$tid);
	$dbh->do($trigger) or exit(127);
	$dbh->do("CREATE TRIGGER ${relname}_i_trig BEFORE INSERT ON ${relname} FOR EACH ROW EXECUTE PROCEDURE ${relname}_i_trig_f()") or exit(127);

	# Maintenant qu'on a le trigger pour dispatcher les enregistrements, inserons
	formatted_print("Insertion des données.",$tid);
	$dbh->do("INSERT INTO ${relname} SELECT * FROM ${relname}_tmpreorg") or exit(127);


	foreach my $partition (@partitions)
	{
		formatted_print("Indexation de la partition ${relname}_${partition}.",$tid);
		# Creation des index
		foreach my $index (@indexes)
		{
			$index =~ /CREATE INDEX (\S+) ON (\S+) USING btree \((.*?)\)/;
			my $sql = "CREATE INDEX ${1}_${partition} ON ${relname}_${partition} USING btree (${3})";
			$dbh->do($sql) or exit(127);
		}
		formatted_print("ANALYZE de la partition ${relname}_${partition}.",$tid);
		$dbh->do("ANALYZE ${relname}_${partition}") or exit(127);
	}
	# Suppression de la vieille table
	formatted_print("Destruction de l'ancienne table.",$tid);
	$dbh->do("DROP TABLE ${relname}_tmpreorg") or exit(127);
	$dbh->commit;
	formatted_print("Passage des statistiques",$tid);
}

# Traitement des tables de type week et week_bh
# Contrainte sur week uniquement
sub traite_week
{
	my ($oid,$relname,$tid)=@_;
	formatted_print("Traitement de $relname.",$tid);
	my $dbh=DBI->connect($connexion_string,$connexion_user,'')
  or die "Impossible de se connecter.\n";
	
	my $week='week'.suffixe_bh($relname);
	
	# Recherche des min et max
	my ($min,$max)=recup_min_max_partition($week,$relname,$dbh);
	# Si on n'a pas de min, on ne peut pas partitionner
	if (not defined $min)
	{
		formatted_print(" Non partitionnable.",$tid);
		return 0;
	}

	# Generation de la liste des partitions
	my @partitions;
	my $i=$min;
	while ($i <= $max)
		
	{
		push @partitions,($i);
		$i=ajoute_week($i);
	}

	# Recherche des index
	my @indexes_tmp=recup_index_table($oid,$dbh);
	# Generation des ordres SQL pour les nouveaux index:
	my @indexes=reduit_index(\@indexes_tmp,$week);
	# On va commencer le renommage, la creation d'objets, etc
	# On démarre une transaction
	$dbh->begin_work();
	# Renommage de la vieille table
	$dbh->do("ALTER TABLE $relname RENAME TO ${relname}_tmpreorg") or exit(127);
	# Creation d'une nouvelle table ayant la même structure
	$dbh->do("CREATE TABLE $relname (LIKE ${relname}_tmpreorg INCLUDING DEFAULTS INCLUDING CONSTRAINTS)") or exit(127);
	# Creation des tables filles et remplissage
	formatted_print("Création des partitions.",$tid);
	foreach my $partition_week (@partitions)
	{
		$dbh->do("CREATE TABLE ${relname}_${partition_week} 
		          (
						    CONSTRAINT cst_".genere_uniqueid()."_${partition_week}_week_check CHECK ($week = $partition_week)
						  ) INHERITS ($relname)") or exit(127);
	}
	# Generation du trigger
	formatted_print("Création du trigger.",$tid);
	my $trigger="CREATE OR REPLACE FUNCTION ${relname}_i_trig_f()\nRETURNS TRIGGER AS \$\$\nBEGIN\n";

	my @tableau_genere_if;
	foreach my $partition(@partitions)
	{
		push @tableau_genere_if,([$partition,"${relname}_${partition}"]);
	}
	$trigger.=genere_if(\@tableau_genere_if,$week);
	$trigger.="RAISE EXCEPTION 'Fin du trigger sans avoir trouve de partition';\n";
	$trigger.="END;\n\$\$\nLANGUAGE plpgsql;";
#	formatted_print($trigger,$tid);
	$dbh->do($trigger) or exit(127);
	$dbh->do("CREATE TRIGGER ${relname}_i_trig BEFORE INSERT ON ${relname} FOR EACH ROW EXECUTE PROCEDURE ${relname}_i_trig_f()") or exit(127);

	# Maintenant qu'on a le trigger pour dispatcher les enregistrements, inserons
	formatted_print("Insertion des données.",$tid);
	$dbh->do("INSERT INTO ${relname} SELECT * FROM ${relname}_tmpreorg") or exit(127);


	foreach my $partition (@partitions)
	{
		formatted_print("Indexation de la partition ${relname}_${partition}.",$tid);
		# Creation des index
		foreach my $index (@indexes)
		{
			$index =~ /CREATE INDEX (\S+) ON (\S+) USING btree \((.*?)\)/;
			my $sql = "CREATE INDEX ${1}_${partition} ON ${relname}_${partition} USING btree (${3})";
			$dbh->do($sql) or exit(127);
		}
		formatted_print("ANALYZE de la partition ${relname}_${partition}.",$tid);
		$dbh->do("ANALYZE ${relname}_${partition}") or exit(127);
	}
	# Suppression de la vieille table
	formatted_print("Destruction de l'ancienne table.",$tid);
	$dbh->do("DROP TABLE ${relname}_tmpreorg") or exit(127);
	$dbh->commit;
}

# Traitement des tables de type day et day_bh
# Contraintes sur day, week et month
sub traite_day
{
	my ($oid,$relname,$tid)=@_;
	formatted_print("Traitement de $relname.",$tid);
	my $dbh=DBI->connect($connexion_string,$connexion_user,'')
  or die "Impossible de se connecter.\n";
	
	my $day='day'.suffixe_bh($relname);
	my $week='week'.suffixe_bh($relname);
	my $month='month'.suffixe_bh($relname);
	
	# Recherche des min et max
	my ($min,$max)=recup_min_max_partition($day,$relname,$dbh);
	# Si on n'a pas de min, on ne peut pas partitionner
	if (not defined $min)
	{
		formatted_print(" Non partitionnable.",$tid);
		return 0;
	}

	# Generation de la liste des partitions
	my @partitions;
	my $i=$min;
	while ($i <= $max)
	{
		push @partitions,($i);
		$i=ajoute_jour($i);
	}

	# Recherche des index
	my @indexes_tmp=recup_index_table($oid,$dbh);
	# Generation des ordres SQL pour les nouveaux index:
	my @indexes=reduit_index(\@indexes_tmp,$day,$week,$month);
	# On va commencer le renommage, la creation d'objets, etc
	# On démarre une transaction
	$dbh->begin_work();
	# Renommage de la vieille table
	$dbh->do("ALTER TABLE $relname RENAME TO ${relname}_tmpreorg") or exit(127);
	# Creation d'une nouvelle table ayant la même structure
	$dbh->do("CREATE TABLE $relname (LIKE ${relname}_tmpreorg INCLUDING DEFAULTS INCLUDING CONSTRAINTS)") or exit(127);
	# Creation des tables filles et remplissage
	formatted_print("Création des partitions.",$tid);
	foreach my $partition (@partitions)
	{
		my $partition_week = week_de_day($partition); # On recupere la semaine
		my $partition_month =  int($partition/100);
		$dbh->do("CREATE TABLE ${relname}_${partition} 
		          ( CONSTRAINT cst_".genere_uniqueid()."_${partition}_day_check CHECK ($day = $partition), 
						    CONSTRAINT cst_".genere_uniqueid()."_${partition_week}_week_check CHECK ($week = $partition_week),
						    CONSTRAINT cst_".genere_uniqueid()."_${partition_month}_month_check CHECK ($month = $partition_month) 
						  ) INHERITS ($relname)") or exit(127);
	}
	# Generation du trigger
	formatted_print("Création du trigger.",$tid);
	my $trigger="CREATE OR REPLACE FUNCTION ${relname}_i_trig_f()\nRETURNS TRIGGER AS \$\$\nBEGIN\n";

	my @tableau_genere_if;
	foreach my $partition(@partitions)
	{
		push @tableau_genere_if,([$partition,"${relname}_${partition}"]);
	}
	$trigger.=genere_if(\@tableau_genere_if,$day);
	$trigger.="RAISE EXCEPTION 'Fin du trigger sans avoir trouve de partition';\n";
	$trigger.="END;\n\$\$\nLANGUAGE plpgsql;";
#	formatted_print($trigger,$tid);
	$dbh->do($trigger) or exit(127);
	$dbh->do("CREATE TRIGGER ${relname}_i_trig BEFORE INSERT ON ${relname} FOR EACH ROW EXECUTE PROCEDURE ${relname}_i_trig_f()") or exit(127);

	# Maintenant qu'on a le trigger pour dispatcher les enregistrements, inserons
	formatted_print("Insertion des données.",$tid);
	$dbh->do("INSERT INTO ${relname} SELECT * FROM ${relname}_tmpreorg") or exit(127);


	foreach my $partition (@partitions)
	{
		formatted_print("Indexation de la partition ${relname}_${partition}.",$tid);
		# Creation des index
		foreach my $index (@indexes)
		{
			$index =~ /CREATE INDEX (\S+) ON (\S+) USING btree \((.*?)\)/;
			my $sql = "CREATE INDEX ${1}_${partition} ON ${relname}_${partition} USING btree (${3})";
			$dbh->do($sql) or exit(127);
		}
		formatted_print("ANALYZE de la partition ${relname}_${partition}.",$tid);
		$dbh->do("ANALYZE ${relname}_${partition}") or exit(127);
	}
	# Suppression de la vieille table
	formatted_print("Destruction de l'ancienne table.",$tid);
	$dbh->do("DROP TABLE ${relname}_tmpreorg") or exit(127);
	$dbh->commit;
}

# Traitement des tables hour et hour_bh
# Contraintes check sur hour et day
sub traite_hour
{
	my ($oid,$relname,$tid)=@_;
	formatted_print("Traitement de $relname.",$tid);
	my $dbh=DBI->connect($connexion_string,$connexion_user,'')
  or die "Impossible de se connecter.\n";

	my $day='day'.suffixe_bh($relname);
	my $hour='hour'.suffixe_bh($relname);
	
	# Recherche des min et max
	my ($min,$max)=recup_min_max_partition($hour,$relname,$dbh);
	# Si on n'a pas de min, on ne peut pas partitionner
	if (not defined $min)
	{
		formatted_print(" Non partitionnable.",$tid);
		return 0;
	}
	# Generation de la liste des partitions
	my @partitions;
	my $i=$min;
	while ($i <= $max)
	{
		push @partitions,($i);
		$i=ajoute_heure($i);
	}

	# Recherche des index
	my @indexes_tmp=recup_index_table($oid,$dbh);
	# Generation des ordres SQL pour les nouveaux index:
	my @indexes=reduit_index(\@indexes_tmp,$day,$hour);
	# On va commencer le renommage, la creation d'objets, etc
	# On démarre une transaction
	$dbh->begin_work();
	# Renommage de la vieille table
	$dbh->do("ALTER TABLE $relname RENAME TO ${relname}_tmpreorg") or exit(127);
	# Creation d'une nouvelle table ayant la même structure
	$dbh->do("CREATE TABLE $relname (LIKE ${relname}_tmpreorg INCLUDING DEFAULTS INCLUDING CONSTRAINTS)") or exit(127);
	# Creation des tables filles et remplissage
	formatted_print("Création des partitions.",$tid);
	foreach my $partition (@partitions)
	{
		my $partition_day = int($partition/100); # On recupere le jour
		$dbh->do("CREATE TABLE ${relname}_${partition} 
		         ( CONSTRAINT cst_".genere_uniqueid()."_${partition}_day_check CHECK ($day = $partition_day), 
						   CONSTRAINT cst_".genere_uniqueid()."_${partition}_hour_check CHECK ($hour = $partition )
						 ) INHERITS ($relname)") or exit(127);
	}
	formatted_print("Création du trigger.",$tid);
	# Generation du trigger
	my $trigger="CREATE OR REPLACE FUNCTION ${relname}_i_trig_f()\nRETURNS TRIGGER AS \$\$\nBEGIN\n";



	my @tableau_genere_if;
	foreach my $partition(@partitions)
	{
		push @tableau_genere_if,([$partition,"${relname}_${partition}"]);
	}
	$trigger.=genere_if(\@tableau_genere_if,$hour);
	$trigger.="RAISE EXCEPTION 'Fin du trigger sans avoir trouve de partition';\n";
	$trigger.="END;\n\$\$\nLANGUAGE plpgsql;";
#	formatted_print("$trigger\n",$tid);
	$dbh->do($trigger) or exit(127);
	$dbh->do("CREATE TRIGGER ${relname}_i_trig BEFORE INSERT ON ${relname} FOR EACH ROW EXECUTE PROCEDURE ${relname}_i_trig_f()") or exit(127);

	# Maintenant qu'on a le trigger pour dispatcher les enregistrements, inserons
	formatted_print("Insertion des données.",$tid);
	$dbh->do("INSERT INTO ${relname} SELECT * FROM ${relname}_tmpreorg") or exit(127);


	foreach my $partition (@partitions)
	{
		formatted_print("Indexation de la partition ${relname}_${partition}.",$tid);
		# Creation des index
		foreach my $index (@indexes)
		{
			$index =~ /CREATE INDEX (\S+) ON (\S+) USING btree \((.*?)\)/;
			my $sql = "CREATE INDEX ${1}_${partition} ON ${relname}_${partition} USING btree (${3})";
			$dbh->do($sql) or exit(127);
		}
		formatted_print("ANALYZE de la partition ${relname}_${partition}.",$tid);
		$dbh->do("ANALYZE ${relname}_${partition}") or exit(127);
	}
	# Suppression de la vieille table
	formatted_print("Destruction de l'ancienne table.",$tid);
	$dbh->do("DROP TABLE ${relname}_tmpreorg") or exit(127);
	$dbh->commit;
}

# Traitement des tables à 5 minutes
# Contraintes check sur ????
sub traite_5minutes
{
	my ($oid,$relname,$tid)=@_;
	formatted_print("Traitement de $relname.",$tid);
	my $dbh=DBI->connect($connexion_string,$connexion_user,'')
  or die "Impossible de se connecter.\n";

	my $hour='hour'.suffixe_bh($relname);
	my $minute='minute'.suffixe_bh($relname);
	
	# Recherche des min et max
	my ($min,$max)=recup_min_max_partition($minute,$relname,$dbh);
	# Si on n'a pas de min, on ne peut pas partitionner
	if (not defined $min)
	{
		formatted_print(" Non partitionnable.",$tid);
		return 0;
	}
	# Generation de la liste des partitions
	my @partitions;
	my $i=$min;
	while ($i <= $max)
	{
		push @partitions,($i);
		$i=ajoute_5minute($i);
	}

	# Recherche des index
	my @indexes_tmp=recup_index_table($oid,$dbh);
	# Generation des ordres SQL pour les nouveaux index:
	my @indexes=reduit_index(\@indexes_tmp,$minute,$hour);
	# On va commencer le renommage, la creation d'objets, etc
	# On démarre une transaction
	$dbh->begin_work();
	# Renommage de la vieille table
	$dbh->do("ALTER TABLE $relname RENAME TO ${relname}_tmpreorg") or exit(127);
	# Creation d'une nouvelle table ayant la même structure
	$dbh->do("CREATE TABLE $relname (LIKE ${relname}_tmpreorg INCLUDING DEFAULTS INCLUDING CONSTRAINTS)") or exit(127);
	# Creation des tables filles et remplissage
	formatted_print("Création des partitions.",$tid);
	foreach my $partition (@partitions)
	{
		my $partition_heure = de_5minutes_a_heure($partition); # On recupere le jour
		$dbh->do("CREATE TABLE ${relname}_${partition} 
		         ( CONSTRAINT cst_".genere_uniqueid()."_${partition}_hour_check CHECK ($hour = $partition_heure), 
						   CONSTRAINT cst_".genere_uniqueid()."_${partition}_minute_check CHECK ($minute = $partition )
						 ) INHERITS ($relname)") or exit(127);
	}
	formatted_print("Création du trigger.",$tid);
	# Generation du trigger
	my $trigger="CREATE OR REPLACE FUNCTION ${relname}_i_trig_f()\nRETURNS TRIGGER AS \$\$\nBEGIN\n";



	my @tableau_genere_if;
	foreach my $partition(@partitions)
	{
		push @tableau_genere_if,([$partition,"${relname}_${partition}"]);
	}
	$trigger.=genere_if(\@tableau_genere_if,$hour);
	$trigger.="RAISE EXCEPTION 'Fin du trigger sans avoir trouve de partition';\n";
	$trigger.="END;\n\$\$\nLANGUAGE plpgsql;";
#	formatted_print("$trigger\n",$tid);
	$dbh->do($trigger) or exit(127);
	$dbh->do("CREATE TRIGGER ${relname}_i_trig BEFORE INSERT ON ${relname} FOR EACH ROW EXECUTE PROCEDURE ${relname}_i_trig_f()") or exit(127);

	# Maintenant qu'on a le trigger pour dispatcher les enregistrements, inserons
	formatted_print("Insertion des données.",$tid);
	$dbh->do("INSERT INTO ${relname} SELECT * FROM ${relname}_tmpreorg") or exit(127);


	foreach my $partition (@partitions)
	{
		formatted_print("Indexation de la partition ${relname}_${partition}.",$tid);
		# Creation des index
		foreach my $index (@indexes)
		{
			$index =~ /CREATE INDEX (\S+) ON (\S+) USING btree \((.*?)\)/;
			my $sql = "CREATE INDEX ${1}_${partition} ON ${relname}_${partition} USING btree (${3})";
			$dbh->do($sql) or exit(127);
		}
		formatted_print("ANALYZE de la partition ${relname}_${partition}.",$tid);
		$dbh->do("ANALYZE ${relname}_${partition}") or exit(127);
	}
	# Suppression de la vieille table
	formatted_print("Destruction de l'ancienne table.",$tid);
	$dbh->do("DROP TABLE ${relname}_tmpreorg") or exit(127);
	$dbh->commit;
}


############################# Main ####################################

# Chargement de la configuration, si on a un argument dans la ligne de commande
if (defined $ARGV[0])
{
	charge_config($ARGV[0]);
}

# Connexion initiale à la base
my $dbh;

$dbh=DBI->connect($connexion_string,$connexion_user,'')
  or die "Impossible de se connecter.\n";
  
# La semaine commenence-t-elle un lundi ou un dimanche ?
my $result_monday=$dbh->selectrow_arrayref("SELECT value::integer AS value FROM sys_global_parameters WHERE parameters = 'week_starts_on_monday'");
$week_starts_on_monday=@$result_monday[0];

# Recuperation de la liste des tables
my $result=$dbh->selectall_arrayref("SELECT pg_class.oid,pg_class.relname,decompte from pg_class left join (select inhparent,count(*) as decompte from pg_inherits group by inhparent) as count_inherit on (pg_class.oid=count_inherit.inhparent) where relnamespace = (SELECT oid from pg_namespace where nspname='public') and relkind='r'")
  or die "Impossible de recuperer la liste des tables.\n";
 
# On crée une file d'attente pour les threads fils, dans laquelle on va envoyer tous nos messages
# On en profite pour comptabiliser le nombre total de message
$q = Thread::Queue->new();

foreach my $record (@$result)
{
	my ($oid,$relname,$nbherit)=@$record;
	# Seulement si la table n'est pas deja partitionnee
	if ($relname =~ /_month(_bh)?$/ and not $nbherit)
	{
		my $todo="traite_month|||$oid|||$relname";
		$q->enqueue($todo);
		$nb_tables_a_traiter++;
	}
	elsif ($relname =~ /_week(_bh)?$/ and not $nbherit)
	{
		my $todo="traite_week|||$oid|||$relname";
		$q->enqueue($todo);
		$nb_tables_a_traiter++;
	}
	elsif ($relname =~ /_day(_bh)?$/ and not $nbherit)
	{
		my $todo="traite_day|||$oid|||$relname";
		$q->enqueue($todo);
		$nb_tables_a_traiter++;
	}
	elsif ($relname =~ /_hour(_bh)?$/ and not $nbherit)
	{
		my $todo="traite_hour|||$oid|||$relname";
		$q->enqueue($todo);
		$nb_tables_a_traiter++;
	}
	elsif ($relname =~ /_minute(_bh)?$/ and not $nbherit)
	{
		my $todo="traite_5minute|||$oid|||$relname";
		$q->enqueue($todo);
		$nb_tables_a_traiter++;
	}

}

# La file d'attente est pleine.
# On va creer des threads de travail, qui vont dequeuer les operations
my @threads;
for (my $i=0;$i<$max_threads;$i++)
{
	my $thread=threads->create('thread_worker',$i);
	push @threads,($thread);
}

# On attend la fin des threads
foreach my $thread(@threads)
{
	$thread->join();
}

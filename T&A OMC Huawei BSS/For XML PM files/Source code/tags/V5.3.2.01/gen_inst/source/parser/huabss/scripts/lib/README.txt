V4.09
	- [Evolution] Modification de la méthode getCountervalue pour gérer les multivalued counters ayant des valeurs condensées. Pas de modif côté spécifique concernant l'appelle à la méthode.
	Il faut cependant modifier le code du parser sépcifique pour gérer l'attribution des valeurs pour les compteurs multi-valués condensés
V4.08
	- [Evolution] Ajout de la famille Node-B dans le script d’activation des familles optionnelles. Pas de modif côté spécifique.
V4.07
	- [Evolution] Modification de la méthode getCountervalue pour gérer les multivalued counters. Pas de modif côté spécifique concernant l'appelle à la méthode.
	Il faut cependant modifier le code du parser sépcifique pour gérer l'attribution des valeurs pour les compteurs multi-valués
V4.06
	- [Correction] Bug 37531 - [REC][ERICSSON][LTE] There is no message displayed when using optional_statistic script with option -l
V4.05
	- [Correction] Possibilité de redéfinir des méthodes de la classe ExecQueryCopy coté spécifique. Pas de modif côté spécifique.
V4.04
	- [Correction] Changement de la propriété hour à 5 dans "Data History" pour les familles optionelles. 
V4.03
	- [Correction] Modificaition de la méthode OpenCsvHandles dans la class LoadTopology, afin que les fichiers temporaires soit bien supprimés (suppression des fichier en .topo au lieu de .csv) Aucune modification côté spécifique
	- [Evolution] Ajout de la méthode checkFileType dans la classe LoadData pour vérifier si on collecte un fichier de topology et éviter la reprise de donnée pour l'heure 00h00. Aucune modification côté spécifique
	- [Evolution] Modification de la méthode waitEndOfAllProcess dans la classe ProcessManager pour eviter la répétition du message 'parsing ongoing 100%' dans le process log. Aucune modification côté spécifique
	
V4.02
	- [Correction] le nms_table dans le todo est désormais insensible à la casse. Pas de modif côté spécifique.
	- [Correction] le scripts optional_statistics prends maintenant en compte la famille rnc. Pas de modif côté spécifique.
V4.01
	- [Correction] Gestion du mode multi process avec fichier de topologie. La signature du constructeur du parser spécifique a changé. Modification requise côté spécifique.
	- [Correction] Historique horaire des statistiques optionnelles
	
V4.00
	- [Evolution] L'évolution majeure est la parallèlisation du step ExecCopyQuery. Pas de modif côté spécifique.
	- [Evolution] Suppression de la CTU. On fait maintenant l'upload de topology à chaque retrieve en traitant toutes les heures de la dernière collecte. Pas de modif côté spécifique.
	- [Evolution] L'automatic mapping est fait une fois par jour, un nouveau paramètre global "automapping_last_update_date" est à ajouter dans le contexte. Génération d'une erreur si absent. Pas de modif côté spécifique.
	- [Evolution] On n'utilise plus les paramètres "topology_last_update_date" et "topology_max_hour_retrieved". Peuvent être supprimés du contexte. Pas de modif côté spécifique.
	- [Evolution] Ajout d'une méthode Tools::getEntitiesForFlatFileName permettant de récupérer plusieurs nms_table pour un même flat_file_name, ex : BSS & BSS GPRS - xxxx - RBS_PS_PCU_BTS|PACKET_CONTROL_UNIT. Pas de modif côté spécifique.
	- [Evolution] Ajout du script optional_statistics.php pour la gestion des statistiques optionnelles. Voir wiki. Penser à ajouter les paramètre globaux suivants : NSSX ("extended_topology"), BSS et UTRAN ("specif_enable_adjacencies","specif_enable_trx","specif_enable_iurlink","specif_enable_iublink","specif_enable_lac","specif_enable_rac") selon les familles présentes. Désactiver ces paramètres par défaut dans le contexte. Le côté spécifique doit prendre en charge la gestion du parsing optionnel des familles.
	- [Correction] Mise à jour du script CreateTempTableCB.class.php pour tenir compte des corrections des CB 5.1.6.42 et 5.2.0.34. Pas de modif côté spécifique.
V3.08
	-[Correction] Retrait des caractères accentués pour écriture dans le demon. Pas de modif côté spécifique.
	-[Evolution] Modification de la clause "order by" de la méthode "CreateTempTable->getConditions" pour trier par famille puis par heure. Pas de modif côté spécifique.
	-[Correction] Suppression de l'affichage des statistiques de durées en mode "Single process". Pas de modif côté spécifique.
V3.07
	-[Correction] Suppression des espaces et caractères se trouvant après le tag fermant des scripts php. Sinon affichage inattendu dans le file demon. Pas de modifs côté spécifique. Penser à vérifier les scripts spécifiques.
	-[Correction] BZ 30782 dans ConditionProvider, dans le cas de regroupement de conditions par heures et par groupe de flat_file_name, le nombre de groupe était mal calculé pour maximiser le nombre de processus créés. Pas de modif côté spécifique.
V3.06

	-[Correction] lecture/ecriture du fichier paramsSerialized.ser: verrou exclusif en mode ecriture; verrou partagé en mode lecture.
V3.05
	-[Correction] Ajout d'un uniqid plus discriminant lors de la création d'index sur les tables temporaires dans la fonction CreateTempTableCB::create_group_table_temp_table pour éviter les index portant le même nom en cas de parallélisation des taches. Pas de modif côté spécifique requise.

V3.04
	-[Correction] On surcharge également la méthode create_temp_table::copy_temp_table_to_object_table du cb pour éviter les doublons dans les tables temporaires de topo. Pas de modification côté spécifique.
	-[Correction] En mode croisière, l'upload de topology se fait maintenant sur l'heure la plus récente. Pas de modif côté spécifique.
	-[Correction] Dans CreateTempTable::process, on ne vérifie plus que la classe create_temp_table du cb n'a pas évoluée. Vérifier que la méthode n'a pas changée à chaque nouvelle livraison de cb.
	-[Evolution] Parallelisation du create_temp_table par heure. Pas de modif côté spécifique.
V3.03
	-[Correction] BZ29750 correction de la méthode update_dynamic_counter_list (automatic mapping) dans le cas du mutli_process. Ajout d'un lock table sur la table sys_field_reference_all pour éviter que plusieurs process insèrent en mêlme temps de nouveaux compteurs. Pas de modif côté spécifique.
	-[Correction] Le paramètre "topology_last_update_date" est seulement mis à jour lors du mode full ($this->topologyHour=='ALL'). Pas de modif côté spécifique.
	-[Correction] Ajout de verrous sur les fonction côté spécifique qui insèrent dans les tables edw_object%. Ceci afin d'éviter les accès simultanés en mode multi process. On surcharge également la méthode create_temp_table::updateObjectRef du cb. Pas de modification côté spécifique.
	-[Evolution] Dans CreateTempTable::process, on vérifie que la classe create_temp_table du cb n'a pas évoluée depuis le dernier cb. Si changement, on passe en mode mono process pour le CreateTempTable. Modification requise côté spécifique si la méthode du cb a évoluée.
	-[Correction] On surcharge également la méthode create_temp_table::insert_into_sys_to_compute du cb pour éviter les doublons. Pas de modification côté spécifique.
	-[Evolution] Le constructeur de la classe Parser prend désormais comme paramètre le booléen single_process_mode, modification requise côté constructeur du parser spécifique.
	-[Evolution] Le constructeur de la classe create_temp_table_omc prend désormais un deuxième argument =single_process_mode. Modification requise côté spécifique.
	
V3.02
	-[Correction] correction de l'upload de topologie en cas de mono processus. Pas de modif côté spécifique requise.
	-[Correction] correction des messages de logs durant le parsing. Pas de modif côté spécifique.
V3.01
	- [Evolution] La méthode Parameters->addTopologyInfo() a changé de signature.Modifications requises coté spécifique
	- [Correction] Des bugs ont également été corrigés par rapport à la derniere version
V3.00
	- [Evolution] L'évolution majeure est la parallèlisation des scripts load_data.php et create_temp_table.php
	- [Evolution] La méthode Tools::getCopyFilePath a changé de signature.Modifications requises coté spécifique
	- [Evolution] Ajout paramètres globaux "retrieve_perf_logs_enabled" et "retrieve_single_process"
	- [Evolution] Le constructeur de la classe DatabaseServices a changé (plus d'objet ParameterList). Modifications requises coté spécifique
	- [Evolution] Le constructeur de la classe ParserImpl doit avoir la signature suivante ParserImpl(DatabaseConnexion,FileTypeCondition=NULL) et ne pas oublier l'appel à parent::__construct().modifications requises coté spécifique
	- [Evolution] On peut desormais hériter de la classe LoadData pour notamment redéfinir les méthodes getDatabaseServicesClassName(),onParsingStart() et onParsingEnd().
	- [Evolution] Ajouter le fichier IncludeAllSpecific.php pour inclure les fichiers définissant les classes spécifiques notamment (ParserImpl,Configuration, DatabaseServicesImpl, CreateTempTableImpl).modifications requises coté spécifique
	- [Evolution] Il est également nécessaire d'hériter de la classe ContentProvider si l'on veut redéfinir un de ses paramètres par défaut notamment parserPoids et templateForNE.
	- [Evolution] Pour éviter que plusieurs processus écrivent sur en meme temps sur un même fichier temporaire il faudra vérouiller le fichier juste avant l'écriture : flock(resource,LOCK_EX), fwrite(), flock(resource,LOCK_UN). Modifications requises coté spécifique
	- [Evolution] Les scripts create_temp_table_omc.class.php et create_temp_table.php ont légèrement changé ; s’inspirer de Ericsson BSS

V2.11
	-[Correction] modification de update_dynamic_counter_list pour la compatibilité postgres 8 et 9, et la prise en charge des compteurs avec parenthèses et + dans leur nom lors de l'automatic mapping. Pas de modif côté spécifique.
	-[Correction] BZ 24198 ajout de l'appel à tracelogErrors() dans LoadTopology::load_files_topo(). Pas de modif côté spécifique. 
V2.10
	-[Correction] la méthode update_dynamic_counter_list est désormais compatible postgres 8 et 9, pas de modif côté spécifique.
V2.9
	-[Correction] BZ 28843 correction de la méthode DatabaseServices::activateSourceFileByCounter. Il faut échapper par un antislash les motif de regexpe du type \s, \w, \d,.... Modification requise côté spécifique.

V2.8
	- [Evolution] Gestion des compteurs identifiés par une position.
	- [Evolution] Recherche des fichiers collectés : un nouveau paramètre permet de ne récupérer que les fichiers d'un parser donné (optimisation dans le cas de parsers multiples). Ce paramètre est optionnel donc aucune modification côté spécifique n'est requise.
	- [Evolution] createCopyBody : nouveau paramètre optionnel "flag_traitement_topo" permettant d'optimiser les perfs (on ne prépare pas l'upload de topo si on sait qu'il n'aura pas lieu). Modification conseillée.
	
V2.7
	-[Correction] correction de la fonction update_dynamic_counter_list : echappements des caractères spéciaux, pas de modif côté spécifique
V2.6
	-[Correction] BZ 28481 - [REC][T&A OMC Ericsson BSS][5.1.1.00] w_astellia tables are not purged after first retrieve when initializing bsc list (Parser->processDbInitTasks())
V2.5
	-[Evolution] L'automatic mapping est desormais compatible avec les compteurs déclinés (cad avec '@@' dans le nms_field_name)
V2.4
	-[Evolution] L'automatic mapping ne se fait maintenant qu'une fois par jour (parametre global "topology_last_update_date" utilisé)
	-[Evolution] Le parametre "topo_update_label" est desormais géré par le parser library.Modif côté spécifique requise
	-[Evolution] Les éléments réseaux desactivés en topo ne seront plus intégrés
V2.3
	-[Correction] BZ 28197 : la méthode DatabaseServices->completeDuplicatedFiles est corrigée pour la maj des collectes virtuelles avec des fichiers multi TZ, pas de modif côté spécifique
	-[Correction] BZ 28180 : la méthode DatabaseServices->activateSourceFileByCounter d'activation/désactivation des fichiers sources suivant les compteurs activés est corrigée. Il faut désormais passer en paramètre de cette méthode deux chaine représentant un motif de regexp qui seront placés avant et après le nms_table. Modif côté spécifique requise.
	-[Correction] BZ 28182 : Correction de la méthode Parser->addToCountersList, le préfix_counter est désormais mis en minuscule.
V2.2
	- [Evolution] ajout de la méthode CreateLteArc pour les produits LTE. Aucune modification côté spécifique requise.
V2.1
	- [Correction] corrections des méthodes lièes au multi-timezones (27889)
	- [Correction] correction de l'automatic mapping, le nms_table est desormais insensible à la casse (27903)
V2.0
	- [Evolution] preferez l'utilisation de la méthode Parser->getCptsByNmsTable() à Parser->getCptsInFile() lorsque le nms_table est connu
	- [Evolution] la méthode Parser->addToCountersList() prend desormais en argument le nms_table au lieu du todo. Modification coté spécifique requise.
	- [Evolution] les constructeurs des classe filles et le fichier load_data.php doivent changer. Modification coté spécifique requise.
	- [Evolution] Parser->getCounterValue(): Utilisez dans le tableau de valeur des clés en minucules. Modification coté spécifique requise.
	- [Evolution] Plus d'extension dans le nom des fichiers temporaires; voir méthode Tools->getCopyFilePath(). Modification coté spécifique requise.
	- [Correction] correction (mineure) de la méthode CreateTempTable->setJoinDynamic() 
V1.9
	- [Evolution] la méthode CreateTempTable->setJoinDynamic() prend desormais comme argument un objet de type Parameter.modif coté spécifique requise
	- [Evolution] la méthode DatabaseServices->update_dynamic_counter_list() a également été maj en tenant compte du nms_table spécifique "unknown"
V1.8
	- [Evolution] la méthode abstraite  Collect->getFiletime() ne prend plus en argument le nom du fichier source mais desormais un objet de type Flatfile.modif coté spécifique requise

V1.7
	- [Correction] compatibilité postgresql 9 : la méthode DatabaseServices->update_dynamic_counter_list() a été corrigé

V1.6
	- [Evolution] gestion des valeurs par défaut des compteurs, modification de GetCounterValue, GetAllCounters. Il faut maintenant utilisé les paramètres globaux default_value_from_sfr et default_value_from_sfr_non_numeric_value  pour gérer les différents cas. 
	- [Correction] Erreur dans createFileTopo si fichier vide pour la famille, corrigé.
	
V1.5
	- [Evolution] Supprimez du coté spécifique: la méthode ParserImpl->getTopoCellsArray() si elle existe, supprimez "topo" de $fileType dans load_data.php
	- [Evolution] la topologie est gérée de manière différente; il faut desormais utiliser la méthode Parameters->addTopologyInfo() pour ajouter un élément en topologie: modif coté spécifique requis
	- [Evolution] ajout de la gestion/parsing des fichiers de topologie (Exple NSN UTRAN):Aucune modif coté spécifique
V1.4
	- [Correction] BZ 25434 : échappement des '+' dans les noms de compteurs

V1.3
    - [Correction] Bug 25864 : ajout de IF EXISTS dans "drop table captured_cells", fichier DatabaseServices.class.php
    - [Evolution] ajout de la methode getDeactivatedNe dans DatabaseServices.  Retourne les elements reseau desactives en topo.
    - [Evolution] Les compteurs déclinés ne sont pas sensibles à la casse.  fichier lib/Parser.class.php
    - [Evolution] S'il y a 0 fichier a traiter, il ne faut pas generer de log -> Suppression des messages "0 files to parse over 0 Hour(s)()". fichier lib/LoadData.class.php

V1.2	
	- [Correction] Modification de la méthode createWimArc pour rattaché le vendor à l'apbs et non plus l'apgw

V0.9
 - [Evolution] Creer un lien vers le parser librabry: Ajouter sur le repertoire parser/<module>/script un svn:externals avec comme valeur 'lib http://asttools/svn/TA/TOOLS/parser_library/TAG/V0.9/lib' (à ajuster selon la version)
 - [Correction]	Modification de la methode Collect->activateSourceFileByCounter: les types de fichiers non présents dans sys_field_reference ne seront pas concernés(exemple de l'ASN1).Modification coté specifique requis.
 - [Evolution] 	Ajout de la methode DatabaseServices->displayDataTable pour afficher en mode debug les tables NA et TA
 - [Correction]	Bug sur l'automatic mapping lié au double '|' : suppression de la méthode Parser->getAutomaticMapping, creation d'une nouvelle méthode Parser->addToCountersList et correction de la méthode DatabaseServices->update_dynamic_counter_list. Modification coté specifique requis.(s'inspirer de huawei BSS)
 - [Correction] Changement de la méthode Parser->getCptsInFile suites aux problèmes de compteurs et/ou des nms_tables dupliqués:Modification coté specifique requis. (s'inspirer de huawei BSS)

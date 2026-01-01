<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php

class PDF extends acurioPDF {
/*
    // fonction qui définit l'entete du rapport
    function Header1()
    {
        global $pdf_image_logo, $pdf_image_logo_astellia;
        //echo "$pdf_image_logo";
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 16);
        if ($this->orientation_pdf == 'P') {
            $this->pdf_largeur_page = 210;
            $this->pdf_hauteur_page = 297;
        } else {
            $this->pdf_largeur_page = 297;
            $this->pdf_hauteur_page = 210;
        }

        switch ($this->orientation_pdf) { // positionne le haut de page en fonction de l'orientation de la page
            case "P" :
                $this->line(10, 25, 50, 25);
                $this->line(160, 25, 180, 25);

                $this->Image($pdf_image_logo_astellia, 185, 7, 17);
                $this->Image($pdf_image_logo, 15, 7, 22);
                break;

            case "L" :
                $this->line(10, 17, 70, 17);
                $this->line(230, 17, 265, 17);
                if (file_exists($pdf_image_logo)) {
                    $this->Image($pdf_image_logo_astellia, 270, 7, 17);
                    $this->Image($pdf_image_logo, 15, 7, 22);
                }
                break;
        }
    }

    function Header()
    {
        global $pdf_image_logo, $pdf_image_logo_astellia;
        // echo "dans header image".$pdf_image_logo;
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'B', 16);
        $longueur_titre = $this->GetStringWidth($this->titre_pdf);
        if ($this->orientation_pdf == 'P') {
            $this->pdf_largeur_page = 210;
            $this->pdf_hauteur_page = 297;
        } else {
            $this->pdf_largeur_page = 297;
            $this->pdf_hauteur_page = 210;
        }

        $this->text(($this->pdf_largeur_page - $longueur_titre) / 2, 32, $this->titre_pdf);
        $this->SetFont('Arial', 'I', 9);
        $today = date("F j, Y");

        switch ($this->orientation_pdf) { // positionne le haut de page en fonction de l'orientation de la page
            case "P" :
                $this->text(35, 24, $today);
                $this->line(10, 25, 14, 25);
                $this->line(28, 25, 200, 25);
                $this->line(10, 35, 200, 35);
                if (file_exists($pdf_image_logo_astellia)) {
                    $this->Image($pdf_image_logo_astellia, 165, 17, 30);
                }
                if (file_exists($pdf_image_logo)) {
                    $this->Image($pdf_image_logo, 15, 17, 12);
                }

                break;

            case "L" :
                $this->text(35, 24, $today);
                $this->line(10, 25, 14, 25);
                $this->line(28, 25, 290, 25);
                $this->line(10, 35, 290, 35);
                if (file_exists($pdf_image_logo_astellia)) {
                    $this->Image($pdf_image_logo_astellia, 250, 17, 30);
                }
                if (file_exists($pdf_image_logo)) {
                    $this->Image($pdf_image_logo, 15, 15, 12);
                }
                break;
        }
    }
    // fonction qui définit le pied de page du rapport
    function Footer()
    {
        $this->SetTextColor(50);
        $this->SetFont('Arial', 'I', 8);
        $powered = "Powered by Trending & Aggregation";
        // Numéro de page
        $texte_page = "Page " . $this->PageNo() . '/{nb}';
        $longueur_texte_page = $this->GetStringWidth($texte_page);

        switch ($this->orientation_pdf) { // positionne le bas de page en fonction de l'orientation de la page
            case "P" :
                // echo "portrait<br>";
                $this->line(10, 285, 200, 285);
                $this->Text(10, 290, $powered);
                $this->Text(205 - $longueur_texte_page, 290, $texte_page);
                break;

            case "L" :
                // echo "landscape <br>";
                $this->line(10, 200, 287, 200);
                $this->Text(10, 205, $powered);
                $this->Text(290 - $longueur_texte_page, 205, $texte_page);
                break;
        }
    }
	*/
    // Affcihe l'en-tête du tableau
    function FancyTable_Header($nombre_colonne, $hauteur_cellule, $largeur, $position_x)
    {
        // Couleurs, épaisseur du trait et police grasse
        $this->SetfillColor(38, 55, 114); //couleur bleu foncé
        $this->SetTextColor(255);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B');
        // Affichage de l'en-tête
        for ($i = 0;$i < $nombre_colonne;$i++) {
            $this->Cell($largeur[$i], $hauteur_cellule, $this->nom_tableau->tableau_entete_colonnes[$i], 1, 0, 'C', 1);
        }
        $this->Ln($hauteur_cellule);
        $this->SetX($position_x);
        // Restauration des couleurs et de la police
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
    }

    // Affiche le titre d'un tableau -- sls ajout du 20/06/2005
    function Table_Title_Header($nombre_colonne, $hauteur_cellule, $largeur, $position_x,$title)
    {
        // Couleurs, épaisseur du trait et police grasse
        // $this->SetfillColor(38, 55, 114); //couleur bleu foncé
        // $this->SetTextColor(255);
        $this->SetfillColor(150, 150, 255); //couleur bleu foncé
        $this->SetTextColor(0);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B');
        // Affichage de l'en-tête
		$largeur_totale = 0;
        for ($i = 0;$i < $nombre_colonne;$i++) {
			$largeur_totale += $largeur[$i];
        }
        $this->Cell($largeur_totale, $hauteur_cellule, $title, 1, 0, 'C', 1);
        $this->Ln($hauteur_cellule);
        $this->SetX($position_x);
        // Restauration des couleurs et de la police
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
    }
	// Idem Table_Title_Header avec la gestion de l'axe 3.
    function Table_Title_Header_axe3($axe3, $nombre_colonne, $hauteur_cellule, $largeur, $position_x,$title)
    {
        // Couleurs, épaisseur du trait et police grasse
        // $this->SetfillColor(38, 55, 114); //couleur bleu foncé
        // $this->SetTextColor(255);
        $this->SetfillColor(210, 210, 230); //couleur bleu foncé
        $this->SetTextColor(0);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B');
        // Affichage de l'en-tête
		$largeur_totale = 0;
        for ($i = 0;$i < $nombre_colonne;$i++) {
			if(!$axe3 && $i <> 2){
				$largeur_totale += $largeur[$i];
			}
        }
        $this->Cell($largeur_totale, $hauteur_cellule, $title, 1, 0, 'C', 1);
        $this->Ln($hauteur_cellule);
        $this->SetX($position_x);
        // Restauration des couleurs et de la police
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
    }



    // Affiche un tableau bas sur une requete et un tableau contenu dans la base de données
    function FancyTable($numero_table, $largeur, $hauteur_cellule, $position_x, $position_y, $flag_requete_special, $requete_speciale)
    {
        global $tableau_legend_export_excel;
        global $tableau_abscisse_export_excel;
        global $tableau_data_export_excel;
        global $pdf_marge_bas;
        global $pdf_position_y_initiale;
        // le fichier complement_pdf est appelé depuis le répertoire php => le include tel quel du fichier qui lui aussi se trouve dans le répertoire php
        include_once("table_generation.php"); //fichier qui va servir pour le retrieve des data
        // Récupère les données relative au tableau HTML utilisées dans le tableau PDF
        $this->nom_tableau = new Tableau_HTML($numero_table, $flag_requete_special);
        if ($numero_table != 0) { // 0 est utilise pour les tableau de MY REPORT pour lesquels, il n'y a pas de stockage dans la BDD
            $this->nom_tableau->requete_speciale = $requete_speciale;
            $this->nom_tableau->complement_sql = $this->complement_sql;
            $this->nom_tableau->compared_field = $this->compared_field;
            $this->nom_tableau->Tableau_Information($numero_table);
            $this->nom_tableau->Tableau_Retrieve_Data();
        } else { // uniquement pour les tableau dont l'identifiant est 0
            $this->nom_tableau->tableau_entete_colonnes = $tableau_legend_export_excel[0];
            $this->nom_tableau->tableau_abscisse = $tableau_abscisse_export_excel[0];
            $this->nom_tableau->tableau_data = $tableau_data_export_excel[0];
            array_unshift($this->nom_tableau->tableau_data, $this->nom_tableau->tableau_abscisse); //ajoute dans tableau data les données d'abscisse
            $this->nom_tableau->line_counter = "yes";
        }
        $this->SetXY($position_x, $position_y);
        $nombre_lignes = count($this->nom_tableau->tableau_abscisse);

        if ($this->nom_tableau->line_counter == "yes") { // si le tableau doit afficher un compteur de lignes
            array_unshift($this->nom_tableau->tableau_entete_colonnes, "Rank"); //ajoute l'entête "Rank" en début de tableau
            for ($i = 0;$i < $nombre_lignes;$i++) {
                $tableau_counter[] = $i + 1; //crée un tableau avec les numéros de lignes
            }
            array_unshift($this->nom_tableau->tableau_data, $tableau_counter); //ajoute les numéros de ligne au tableau des données
        }

        $nombre_colonne = count($this->nom_tableau->tableau_entete_colonnes);
        $this->Ln($hauteur_cellule); //saute une ligne
        $this->SetX($position_x); //repositionne le curseur
        // Affiche l'En-tête
        $this->FancyTable_Header($nombre_colonne, $hauteur_cellule, $largeur, $position_x);
        // Données
        $fill = 0;
        for ($j = 0;$j < $nombre_lignes;$j++) {
            for ($k = 0;$k < $nombre_colonne;$k++) {
                $valeur = $this->nom_tableau->tableau_data[$k][$j];
                $this->Cell($largeur[$k], $hauteur_cellule, $valeur, 'LR', 0, 'C', $fill);
            }
            $this->Ln($hauteur_cellule);
            $this->SetX($position_x);
            // Teste si la position Y actuelle du curseur est supérieur à la hauteur de la page- la marge
            // auquel cas un saut de page est généré
            if ($this->GetY() > $this->pdf_hauteur_page - $pdf_marge_bas) {
                $this->Cell(array_sum($largeur), 0, '', 'T'); //on met la bordure de bas de tableau sur la page considérée
                $this->AddPage(); //gère l'ajout de page manuellement car SetAutoPageBreak ne fonctionne pas
                $this->SetY($pdf_position_y_initiale);
                $this->SetX($position_x);
                // Affiche l'en-tête du tableau sur la nouvelle page
                $this->FancyTable_Header($nombre_colonne, $hauteur_cellule, $largeur, $position_x);
            }

            $fill = !$fill;
        }
        $this->Cell(array_sum($largeur), 0, '', 'T');
    }

    function pdf_graph_display($agregation, $agregation_value, $graph_number_motorola, $graph_number_alcatel, $largeur_pdf_graph, $position_x_pdf_graph, $offset_position_y)
    {
        global $database_connection, $id_user, $last_comment_pdf_export;

        if ($agregation == "cell") {
            $agregation = "omc_index";
        }
        switch ($agregation_value) {
            case "ALL" :
                $query = "SELECT distinct edw_group_table, $agregation from edw_object_ref ORDER by $agregation";
                break;

            case "ALCATEL" :
                $query = "SELECT distinct edw_group_table,$agregation from edw_object_ref where edw_group_table='edw_alcatel_0'";
                break;

            case "MOTOROLA" :
                $query = "SELECT distinct edw_group_table,$agregation from edw_object_ref where edw_group_table='edw_motorola_0'";
                break;

            default :
                $query = "SELECT distinct edw_group_table,$agregation from edw_object_ref where $agregation='$agregation_value'";
                break;
        }
        $result = pg_query($database_connection, $query);
        $nombre_resultat = pg_num_rows($result);
        // boucle sur tous les resultats trouves
        if ($nombre_resultat > 0) { // cela gère le cas des graphes mixed donc pas de souci
            $row = pg_fetch_array($result, 0);
            $current_value_agregation = $row[$agregation]; //recupere la valeur d'agregation
            $agregation_network_value = $current_value_agregation; //mets dans la variable  $agregation_network_value utilisee par graph_complement la valeur d'agregation
            if ($agregation_network_value != "") { // teste si la valeur d'agregation est non vide
                $current_group_table_value = $row["edw_group_table"];
                $this->largeur_graph = $largeur_pdf_graph;
                $this->position_x = $position_x_pdf_graph;
                // teste dans quel group table on se situe pour affihcer le bon graphe
                switch ($current_group_table_value) {
                    case "edw_motorola_0" :
                        // Affichage du graphe motorola
                        // recupere le commentaire du tableau
                        $comment = $last_comment_pdf_export[$graph_number_motorola]; //ce tableau est une variable de session crée dans graphe_complement
                        $array_comment = explode("<br>", $comment); //explose le commentaire en fonction des <br>
                        $this->cadre_image("", $graph_number_motorola, 0, 0, $this->largeur_graph, $array_comment, $this->position_x, $this->position_y, $id_user);
                        break;

                    default:
                        // Affichage du graphe alcatel
                        // recupere le commentaire du tableau
                        $comment = $last_comment_pdf_export[$graph_number_alcatel]; //ce tableau est une variable de session crée dans graphe_complement
                        $array_comment = explode("<br>", $comment); //explose le commentaire en fonction des <br>
                        $this->cadre_image("", $graph_number_alcatel, 0, 0, $this->largeur_graph, $array_comment, $this->position_x, $this->position_y, $id_user);
                        break;
                }
                $this->position_y = $this->position_y + $offset_position_y + 4 * count($array_comment);;
            }
        }
    }
    // fonction qui positionne une image dans un cadre avec titre et commentaire
    function cadre_image($titre, $numero_graph, $instance, $ordre, $largeur_image, $comment, $position_x, $position_y, $id_user)
    {
        global $pdf_marge_bas, $pdf_position_y_initiale;
        global $database_connection, $table_stockage_nom_image;
        global $offset_position_y, $repertoire_physique_niveau0;
        // récupère toutes les informations sur le graphe
        $query = "SELECT t0.nom_image, t1.graph_title FROM $table_stockage_nom_image t0, graph_information t1 WHERE t0.id_graph='$numero_graph' and t0.id_graph=t1.id_graph and id_user='$id_user'";
        $result = pg_query($database_connection, $query);
        $nombre_resultat = pg_num_rows($result);
        if ($nombre_resultat > 0) { // le graphe a été stocké en tant qu'image
            // on récupère le nom du graphe
            $row = pg_fetch_array($result, 0);
            $nom_image = $repertoire_physique_niveau0 . "png_file/" . $row["nom_image"];
            $this->titre_graph = $row["graph_title"];
            // teste si l'image existe bien sur le disque et dans ce cas affiche l'image dans le PDF
            if (file_exists($nom_image)) {
                $size_image = getimagesize ($nom_image);
                $rapport_largeur_hauteur = $size_image[0] / $size_image[1]; //calcule le rapport largeur par hauteur
                // calcule le nombre de ligne présent dans le commentaire qui est un array
                if (count($comment) > 0 && $comment[0] != "") {
                    $nombre_ligne_commentaire = count($comment);
                } else {
                    $nombre_ligne_commentaire = 0;
                }
                // Affiche le graphe
                $hauteur_image = $largeur_image / $rapport_largeur_hauteur;
                $this->hauteur_image = $hauteur_image;
                // calcule la hauteur de l'image, des espace et des commentaire afin de vérifier si ca dépasse ou non la limite de bas de page
                $hauteur_evaluee = $position_y + $hauteur_image + $pdf_marge_bas + 4 * $nombre_ligne_commentaire; //on considere qu'il faut une ligne de hauteur 4 pour écrire un commentaire
                if ($hauteur_evaluee > $this->pdf_hauteur_page) {
                    $this->AddPage(); //gère l'ajout de page manuellement car SetAutoPageBreak ne fonctionne pas
                    $this->position_y = $pdf_position_y_initiale;
                    $position_y = $pdf_position_y_initiale;
                }
                $espace_droit_gauche = 0.5; //Espace entre l'image et le cadre côté gauche et côté droit
                $espace_haut_bas = 6.5; //Espace entre l'image et le haut ou bas du cadre
                $this->SetTextColor(0, 0, 0); //couleur noire
                $this->SetfillColor(155, 155, 155); //couleur bleu foncé
                $this->SetFont('Arial', 'I', 10);
                // défini le cadre
                $this->Rect($position_x - $espace_droit_gauche, $position_y - $espace_haut_bas, $largeur_image + 2 * $espace_droit_gauche, $hauteur_image + $espace_haut_bas + 4 * $nombre_ligne_commentaire, "F");
                // défini le titre
                $this->Text(($largeur_image - $this->GetStringWidth($titre)) / 2, $position_y-2, $this->titre_graph);
                // défini le commentaire
                $this->SetFont('Arial', 'I', 8);
                $trans_tbl = get_html_translation_table (HTML_ENTITIES); //recupere la table de translation HTML
                $trans_tbl = array_flip ($trans_tbl); //retourne la table
                foreach ($comment as $key => $ligne_comment) {
                    $ligne_comment = strtr ($ligne_comment, $trans_tbl); //converti les carctères HTML en caratères normaux
                    $this->Text($position_x + ($largeur_image - $this->GetStringWidth($ligne_comment)) / 2, $position_y + $key * 4 + $hauteur_image + $espace_haut_bas / 2, $ligne_comment);
                }
                // Affcihe l'image
                $this->Image($nom_image, $position_x , $position_y , $largeur_image, $hauteur_image, "PNG");
            } else {
                $this->position_y = $this->position_y - $offset_position_y; //repositionne la position y dans le cas où l'image du graphe n'a pas ete trouvee
            }
        } else {
            $this->position_y = $this->position_y - $offset_position_y; //repositionne la position y dans le cas où le numero du graphe n'a pas été créé
        }
    }

    function cadre_image_carte($nom_image, $comment, $position_x, $position_y, $scale)
    {
        global $pdf_marge_bas, $pdf_position_y_initiale;

        global $offset_position_y;

        $nombre_resultat = 1;
        if ($nombre_resultat > 0) { // le graphe a été stocké en tant qu'image
            // on récupère le nom du graphe
            if (file_exists($nom_image)) {
                $size_image = getimagesize ($nom_image);
                $largeur_image = ceil($size_image[0] * $scale);
                $hauteur_image = ceil($size_image[1] * $scale);
                $rapport_largeur_hauteur = $size_image[0] / $size_image[1]; //calcule le rapport largeur par hauteur
                // calcule le nombre de ligne présent dans le commentaire qui est un array
                if (count($comment) > 0 && $comment[0] != "") {
                    $nombre_ligne_commentaire = count($comment);
                } else {
                    $nombre_ligne_commentaire = 0;
                }
                // Affiche le graphe
                $this->hauteur_image = $hauteur_image;
                // calcule la hauteur de l'image, des espace et des commentaire afin de vérifier si ca dépasse ou non la limite de bas de page
                $hauteur_evaluee = $position_y + $hauteur_image + $pdf_marge_bas + 4 * $nombre_ligne_commentaire; //on considere qu'il faut une ligne de hauteur 4 pour écrire un commentaire
                if ($hauteur_evaluee > $this->pdf_hauteur_page) {
                    $this->AddPage(); //gère l'ajout de page manuellement car SetAutoPageBreak ne fonctionne pas
                    $this->position_y = $pdf_position_y_initiale;
                    $position_y = $pdf_position_y_initiale;
                }
                $espace_droit_gauche = 0.5;
                $espace_gauche = 0.5; //Espace entre l'image et le cadre côté gauche et côté droit
                $espace_haut_bas = 0.5;
                $espace_haut = 0.5;
                $espace_bas = 0.5; //Espace entre l'image et le haut ou bas du cadre
                $this->SetTextColor(0, 0, 0); //couleur noire
                $this->SetfillColor(155, 155, 155); //couleur bleu foncé
                $this->SetFont('Arial', 'I', 10);
                // défini le cadre
                $this->Rect($position_x-0.5, $position_y-0.5, $largeur_image + 1, $hauteur_image + 1, "F");
                // défini le titre
                // $this->Text($position_x + ($largeur_image - $this->GetStringWidth($titre)) / 2, $position_y-2, $titre);
                // défini le commentaire
                $this->SetFont('Arial', 'I', 8);
                $trans_tbl = get_html_translation_table (HTML_ENTITIES); //recupere la table de translation HTML
                $trans_tbl = array_flip ($trans_tbl); //retourne la table
                foreach ($comment as $key => $ligne_comment) {
                    $ligne_comment = strtr ($ligne_comment, $trans_tbl); //converti les carctères HTML en caratères normaux
                    $this->Text($position_x + ($largeur_image - $this->GetStringWidth($ligne_comment)) / 2, $position_y + $key * 4 + $hauteur_image + $espace_haut_bas / 2, $ligne_comment);
                }
                // Affcihe l'image
                $this->Image($nom_image, $position_x , $position_y , $largeur_image, $hauteur_image, "PNG");
            } else {
                $this->position_y = $this->position_y - $offset_position_y; //repositionne la position y dans le cas où l'image du graphe n'a pas ete trouvee
            }
        } else {
            $this->position_y = $this->position_y - $offset_position_y; //repositionne la position y dans le cas où le numero du graphe n'a pas été créé
        }
    }

    function cadre_image_graphe($nom_image, $comment, $position_x, $position_y, $scale)
    {
        global $pdf_marge_bas, $pdf_position_y_initiale;

        global $offset_position_y;

        $nombre_resultat = 1;
        if ($nombre_resultat > 0) { // le graphe a été stocké en tant qu'image
            // on récupère le nom du graphe
            if (file_exists($nom_image)) {
                $size_image = getimagesize ($nom_image);
                $largeur_image = ceil($size_image[0] * $scale);
                $hauteur_image = ceil($size_image[1] * $scale);
                $rapport_largeur_hauteur = $size_image[0] / $size_image[1]; //calcule le rapport largeur par hauteur
                // calcule le nombre de ligne présent dans le commentaire qui est un array
                $nombre_ligne_commentaire = 0;
                // Affiche le graphe
                $this->hauteur_image = $hauteur_image;
                // calcule la hauteur de l'image, des espace et des commentaire afin de vérifier si ca dépasse ou non la limite de bas de page
                $hauteur_evaluee = $position_y + $hauteur_image + $pdf_marge_bas + 4 * $nombre_ligne_commentaire; //on considere qu'il faut une ligne de hauteur 4 pour écrire un commentaire
                if ($hauteur_evaluee > $this->pdf_hauteur_page) {
                    $this->AddPage(); //gère l'ajout de page manuellement car SetAutoPageBreak ne fonctionne pas
                    $this->position_y = $pdf_position_y_initiale;
                    $position_y = $pdf_position_y_initiale;
                }
                $espace_droit_gauche = 0.5;
                $espace_gauche = 1; //Espace entre l'image et le cadre côté gauche et côté droit
                $espace_haut_bas = 4; //Espace entre l'image et le haut ou bas du cadre
                $this->SetTextColor(0, 0, 0); //couleur noire
                $this->SetfillColor(155, 155, 155); //couleur bleu foncé
                $this->SetFont('Arial', 'I', 10);
                // défini le cadre
                $this->Rect($position_x - $espace_droit_gauche, $position_y - 0.5, $largeur_image + 2 * $espace_droit_gauche, $hauteur_image + $espace_haut_bas + 4 * $nombre_ligne_commentaire, "F");
                // défini le titre
                // $this->Text($position_x + ($largeur_image - $this->GetStringWidth($titre)) / 2, $position_y-2, $titre);
                // défini le commentaire
                $this->SetFont('Arial', 'I', 8);
                $trans_tbl = get_html_translation_table (HTML_ENTITIES); //recupere la table de translation HTML
                $trans_tbl = array_flip ($trans_tbl); //retourne la table
                foreach ($comment as $key => $ligne_comment) {
                    $ligne_comment = strtr ($ligne_comment, $trans_tbl); //converti les carctères HTML en caratères normaux
                    $this->Text($position_x + ($largeur_image - $this->GetStringWidth($ligne_comment)) / 2, $position_y + $key * 4 + $hauteur_image + $espace_haut_bas / 2, $ligne_comment);
                }
                // Affcihe l'image
                $this->Image($nom_image, $position_x , $position_y , $largeur_image, $hauteur_image, "PNG");
            } else {
                $this->position_y = $this->position_y - $offset_position_y; //repositionne la position y dans le cas où l'image du graphe n'a pas ete trouvee
            }
        } else {
            $this->position_y = $this->position_y - $offset_position_y; //repositionne la position y dans le cas où le numero du graphe n'a pas été créé
        }
    }

    function Init_PDF()
    {
        global $pdf_marge_gauche;
        global $pdf_marge_droite;
        global $pdf_marge_haut;
        // Instanciation de la classe dérivée
        if ($this->orientation_pdf == 'P') {
            $this->pdf_largeur_page = 210;
            $this->pdf_hauteur_page = 297;
        } else {
            $this->pdf_largeur_page = 297;
            $this->pdf_hauteur_page = 210;
        }

        $this->SetDisplayMode('fullpage', 'single'); //affiche par défaut du rapport PDF
        $this->SetMargins($pdf_marge_gauche, $pdf_marge_droite, $pdf_marge_haut); //marge gauche, haut et droite de 1.5cm
        $this->SetAutoPageBreak(false, 0); //la gestion des sauts de page est gérée dans l'affichage ce qui permet de replacer le curseur au bon endroit
        $this->Open();
        $this->AliasNbPages();
        $this->AddPage($this->orientation_pdf);
        // crée un nom pour le PDF
        $this->set_pdf_name();
        // insère le nom du PDF dans la base de données et efface le nom du précédent PDF
        $this->set_database_pdf_name();
    }

    function Init_PDF_page_titre()
    {
        global $pdf_marge_gauche;
        global $pdf_marge_droite;
        global $pdf_marge_haut;
        // Instanciation de la classe dérivée
        if ($this->orientation_pdf == 'P') {
            $this->pdf_largeur_page = 210;
            $this->pdf_hauteur_page = 297;
        } else {
            $this->pdf_largeur_page = 297;
            $this->pdf_hauteur_page = 210;
        }

        $this->SetDisplayMode('fullpage', 'single'); //affiche par défaut du rapport PDF
        $this->SetMargins($pdf_marge_gauche, $pdf_marge_droite, $pdf_marge_haut); //marge gauche, haut et droite de 1.5cm
        $this->SetAutoPageBreak(false, 0); //la gestion des sauts de page est gérée dans l'affichage ce qui permet de replacer le curseur au bon endroit
        $this->Open();
        $this->AliasNbPages();
        $this->AddPage0();
        // crée un nom pour le PDF
        $this->set_pdf_name();
        // insère le nom du PDF dans la base de données et efface le nom du précédent PDF
        $this->set_database_pdf_name();
    }

    function FancyTable5($largeur, $hauteur_cellule, $position_x, $position_y, $data, $max_to_display)
    {
        $this->Ln($hauteur_cellule); //saute une ligne
        $this->SetXY($position_x, $position_y);
        // Affiche l'En-tête
        // $this->FancyTable_Header(1, $hauteur_cellule, $largeur, $position_x);
        // Données
        $fill = 0;
        $this->SetTextColor(0, 0, 0); //couleur noire
        $this->SetFont('Arial', '', 12);
        $fill = 1;
        $nb_ligne = 0;
        $nb_ligne_tableau = count($data);
        // $max_to_display=15;
        for ($n = 0;$n < count($data);$n++) {
            $news_explode = explode("\n", $data[$n]);
            unset($news_explode2);
            for ($j = 0;$j < count($news_explode);$j++) {
                $news_explode[$j] = urlencode($news_explode[$j]);
                $news_explode_wrap = wordwrap(htmlspecialchars($news_explode[$j], ENT_QUOTES), 70, '@@', 1);
                $news_explode2_array[$j] = explode("@@", $news_explode_wrap);

                for ($w = 0;$w < count($news_explode2_array[$j]);$w++) {
                    $news_explode2[] = $news_explode2_array[$j][$w];
                }
            }
            $position_x_init = $position_x;
            $position_y_init = $position_y;
            $this->SetDrawColor(0, 0, 0);
            $max_ligne = min(count($news_explode2), 8);
            for ($k = 0;$k < $max_ligne;$k++) {
                if ($n % 2) {
                    $this->SetFillColor(255, 255, 255);
                } else {
                    $this->SetFillColor(255, 255, 255);
                }

                $this->SetTextColor(0, 0, 0); //couleur noire
                $this->SetFont('Arial', '', 12);

                $this->Cell($largeur, $hauteur_cellule, urldecode($news_explode2[$k]), 0, 0, 'L', $fill);

                $position_y = $position_y + 3;
                $this->SetXY($position_x, $position_y);
                // $this->SetY($position_y);
                if ($nb_ligne > $max_to_display) {
                    $k = $max_ligne;
                }
                $nb_ligne++;
            }
            // $this->line($position_x_init,$position_y,$position_x_init+$largeur,$position_y);
            $position_y = $position_y + 3;
            $this->SetXY($position_x, $position_y);
            // $this->line($position_x_init,$position_y,$position_x_init+$largeur,$position_y);
            if ($nb_ligne > $max_to_display) {
                $n = $nb_ligne_tableau;
            }
            $nb_ligne++;
        }
    }

    function FancyTable6($largeur, $hauteur_cellule, $position_x, $position_y, $data, $data1, $max_to_display)
    {
        $this->Ln($hauteur_cellule); //saute une ligne
        $this->SetXY($position_x, $position_y);
        // Affiche l'En-tête
        // $this->FancyTable_Header(1, $hauteur_cellule, $largeur, $position_x);
        // Données
        $fill = 0;
        $this->SetTextColor(0, 0, 0); //couleur noire
        $this->SetFont('Arial', '', 10);
        $fill = 0;
        $nb_ligne = 0;
        $nb_ligne_tableau = count($data1);
        // $max_to_display=15;
        for ($n = 0;$n < $nb_ligne_tableau;$n++) {
            $news_explode = explode("\n", $data1[$n]);
            unset($news_explode2);
            for ($j = 0;$j < count($news_explode);$j++) {
                $news_explode[$j] = urlencode($news_explode[$j]);
                $news_explode_wrap = wordwrap(htmlspecialchars($news_explode[$j], ENT_QUOTES), 70, '@@', 1);
                $news_explode2_array[$j] = explode("@@", $news_explode_wrap);

                for ($w = 0;$w < count($news_explode2_array[$j]);$w++) {
                    $news_explode2[] = $news_explode2_array[$j][$w];
                }
            }

            $this->SetDrawColor(0, 0, 0);
            $max_ligne = min(count($news_explode2), 8);
            $this->SetFillColor(255, 255, 255);
            $this->SetTextColor(0, 0, 0); //couleur noire
            $this->SetFont('Arial', 'B', 10);

            $this->Cell($largeur, $hauteur_cellule, $data[$n], 0, 0, 'C', $fill);
            for ($k = 0;$k < $max_ligne;$k++) {
                $position_y = $position_y;
                $this->SetXY($position_x, $position_y);
                // $this->SetY($position_y);
                if ($nb_ligne > $max_to_display) {
                    $k = $max_ligne;
                }
                $nb_ligne++;
            }
            // $this->line($position_x_init,$position_y,$position_x_init+$largeur,$position_y);
            $position_y = $position_y;
            $this->SetXY($position_x, $position_y);

            if ($nb_ligne > $max_to_display) {
                $n = $nb_ligne_tableau;
            }
            $nb_ligne++;
        }
    }
    // fonction qui détermine le nom du rapport PDF
    function set_pdf_name()
    {
        global $id_user;

        if (!isset($this->nom_rapport_pdf)) {
            $this->nom_rapport_pdf = "../fichier/rapport_pdf" . "_" . $id_user . "_";
        }
        list($usec, $sec) = explode(" ", microtime());
        $usec = $usec * 100000000;
        $this->nom_rapport_pdf .= $usec . ".pdf";
    }
    // fonction  insère le nom du PDF dans la base de données et efface le nom du précédent PDF
    function set_database_pdf_name()
    {
        global $id_user, $nom_table_user_pdf, $database_connection;

        $query = "SELECT nom_pdf FROM $nom_table_user_pdf where id_user='$id_user'";
        $resultat = pg_query($database_connection, $query);
        if ($resultat <> false) { // teste si la requete s'est bien passé
            $row = pg_fetch_array($resultat, 0);
            $nom_pdf = $row["nom_pdf"];
            if ($nom_pdf != "") { // si l'utilisateur n'a généré aucun pdf, l'information dans la base de données est vide
                // efface le PDF pour le même utilisateur et mets à jour la BDD
                if (file_exists($nom_pdf)) {
                    unlink($nom_pdf);
                }
                // Mets la base à jour avec le nouveau nom de l'image
                $query = "UPDATE user_pdf set nom_pdf='$this->nom_rapport_pdf' WHERE (id_user='$id_user')";
                pg_query($database_connection, $query);
            } else { // s'il n'existe pas de PDF alors on crée une entrée dans la table
                $query = "INSERT INTO user_pdf (id_user, nom_pdf) VALUES ('$id_user','$this->nom_rapport_pdf')";
                pg_query($database_connection, $query);
            }
        }
    }
    // fonction qui détermine le nom du rapport PDF
    function Generation_PDF()
    {
        global $email_automatique;

        $this->Output($this->nom_rapport_pdf);
        $this->Close();
        if ($email_automatique != "true") {

            ?>
          <script>
               document.location="<?=$this->nom_rapport_pdf?>";
          </script>
          <?php
        }
    }

	/*Fonction qui génére le PDF. Le PDF est systèmatiquement généré dans un répertoire
	//Ensuite, le PDF peut-être lancé pour être affcihé
	* @param $display valeur qui prend on ou off : 'on' si on affiche le PDF, 'off' sinon. Par défaut c'est 'off'
	* @global $repertoire_physique_niveau0
	*
	*/
	//
	// rq : je remets la fonction PDF_output() qui avait disparu de complement_pdf.php,
	// on ne sait pourquoi -- sls le 20/06/2005
    function PDF_output($display="on")
    {
		global $repertoire_physique_niveau0,$niveau0;

		$output_rapport_pdf=$repertoire_physique_niveau0."png_file/".$this->nom_rapport_pdf;
        $this->Output($output_rapport_pdf);
        $this->Close();
        if ($display == "on") {
			$output_rapport_pdf_location=$niveau0."png_file/".$this->nom_rapport_pdf;
            ?>
          <script>
               document.location="<?=$output_rapport_pdf_location?>";
          </script>
          <?php
        }
    }


    function FancyTable2($largeur, $hauteur_cellule, $position_x, $position_y, $data, $max_to_display)
    {
        $this->Ln($hauteur_cellule); //saute une ligne
        $this->SetXY($position_x, $position_y);
        // Affiche l'En-tête
        // $this->FancyTable_Header(1, $hauteur_cellule, $largeur, $position_x);
        // Données
        $fill = 0;
        $this->SetTextColor(0, 0, 0); //couleur noire
        $this->SetFont('Arial', '', 12);
        $fill = 1;
        $nb_ligne = 0;
        $nb_ligne_tableau = count($data);
        // $max_to_display=15;
        for ($n = 0;$n < count($data);$n++) {
            $news_explode = explode("\n", $data[$n]);
            unset($news_explode2);
            for ($j = 0;$j < count($news_explode);$j++) {
                $news_explode[$j] = urlencode($news_explode[$j]);
                $news_explode_wrap = wordwrap(htmlspecialchars($news_explode[$j], ENT_QUOTES), 70, '@@', 1);
                $news_explode2_array[$j] = explode("@@", $news_explode_wrap);

                for ($w = 0;$w < count($news_explode2_array[$j]);$w++) {
                    $news_explode2[] = $news_explode2_array[$j][$w];
                }
            }
            $position_x_init = $position_x;
            $position_y_init = $position_y;
            $this->SetDrawColor(0, 0, 0);
            $max_ligne = min(count($news_explode2), 8);
            for ($k = 0;$k < $max_ligne;$k++) {
                if ($n % 2) {
                    $this->SetFillColor(255, 255, 255);
                } else {
                    $this->SetFillColor(245, 245, 190);
                }

                $this->SetTextColor(0, 0, 0); //couleur noire
                $this->SetFont('Arial', '', 12);

                $this->Cell($largeur, $hauteur_cellule, urldecode($news_explode2[$k]), 0, 0, 'L', $fill);

                $position_y = $position_y + 5;
                $this->SetXY($position_x, $position_y);
                // $this->SetY($position_y);
                if ($nb_ligne > $max_to_display) {
                    $k = $max_ligne;
                }
                $nb_ligne++;
            }
            $this->line($position_x_init, $position_y, $position_x_init + $largeur, $position_y);
            $position_y = $position_y + 4;
            $this->SetXY($position_x, $position_y);

            if ($nb_ligne > $max_to_display) {
                $n = $nb_ligne_tableau;
            }
            $nb_ligne++;
        }
    }

    function FancyTable3($largeur, $hauteur_cellule, $position_x, $position_y, $data, $data1, $max_to_display)
    {
        $this->Ln($hauteur_cellule); //saute une ligne
        $this->SetXY($position_x, $position_y);
        // Affiche l'En-tête
        // $this->FancyTable_Header(1, $hauteur_cellule, $largeur, $position_x);
        // Données
        $fill = 0;
        $this->SetTextColor(0, 0, 0); //couleur noire
        $this->SetFont('Arial', '', 10);
        $fill = 1;
        $nb_ligne = 0;
        $nb_ligne_tableau = count($data1);
        // $max_to_display=15;
        for ($n = 0;$n < $nb_ligne_tableau;$n++) {
            $news_explode = explode("\n", $data1[$n]);
            unset($news_explode2);
            for ($j = 0;$j < count($news_explode);$j++) {
                $news_explode[$j] = urlencode($news_explode[$j]);
                $news_explode_wrap = wordwrap(htmlspecialchars($news_explode[$j], ENT_QUOTES), 70, '@@', 1);
                $news_explode2_array[$j] = explode("@@", $news_explode_wrap);

                for ($w = 0;$w < count($news_explode2_array[$j]);$w++) {
                    $news_explode2[] = $news_explode2_array[$j][$w];
                }
            }

            $this->SetDrawColor(0, 0, 0);
            $max_ligne = min(count($news_explode2), 8);
            $this->SetFillColor(255, 255, 255);
            $this->SetTextColor(0, 0, 0); //couleur noire
            $this->SetFont('Arial', 'B', 10);

            $this->Cell($largeur, $hauteur_cellule, $data[$n], 0, 0, 'C', $fill);
            for ($k = 0;$k < $max_ligne;$k++) {
                $position_y = $position_y + 5;
                $this->SetXY($position_x, $position_y);
                // $this->SetY($position_y);
                if ($nb_ligne > $max_to_display) {
                    $k = $max_ligne;
                }
                $nb_ligne++;
            }
            // $this->line($position_x_init,$position_y,$position_x_init+$largeur,$position_y);
            $position_y = $position_y + 4;
            $this->SetXY($position_x, $position_y);

            if ($nb_ligne > $max_to_display) {
                $n = $nb_ligne_tableau;
            }
            $nb_ligne++;
        }
    }

    function FancyTable4($header, $data)
    {
        // Colors, line width and bold font
        $this->SetFillColor(255, 0, 0);
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B');
        // Header
        $w = array(40, 35, 40, 45);
        for($i = 0;$i < count($header);$i++)
        $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        $this->Ln();
        // Color and font restoration
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
        // Data
        $fill = 0;
        foreach($data as $row) {
            $this->Cell($w[0], 6, $row[0], 'LR', 0, 'L', $fill);
            $this->Cell($w[1], 6, $row[1], 'LR', 0, 'L', $fill);
            $this->Cell($w[2], 6, number_format($row[2]), 'LR', 0, 'R', $fill);
            $this->Cell($w[3], 6, number_format($row[3]), 'LR', 0, 'R', $fill);
            $this->Ln();
            $fill = !$fill;
        }
        $this->Cell(array_sum($w), 0, '', 'T');
    }

    function BasicTable2($header, $data)
    {
        // Header
        $this->SetFillColor(255, 200, 0);
        $this->SetTextColor(255);
        $this->SetDrawColor(150, 150, 150);
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B');
        // $this->SetX(35);
        $n = 0;
        if ($header != '') {
            foreach($header as $col) {
                $this->Cell(30, 7, $col, 1);

                $n++;
            }
        }
        $this->Ln();
        // Data
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
        $row_nb = 0;
        foreach($data as $row) {
            $n = 0;
            foreach($row as $col) {
                if ($row_nb == 0) {
                    $this->SetFillColor(0, 0, 0);
                    $this->SetTextColor(255);
                    $this->SetFont('Arial', 'B', 12);
                    switch ($n) {
                        case 0:$this->Cell(65, 7, $col, 1, 0, 'L', 1);
                            break;
                        case 1:break;
                        case 2: $this->Cell(60, 7, $col, 1, 0, 'L', 1);
                            break;
                        case 3:break;
                        case 4:$this->Cell(60, 7, $col, 1, 0, 'L', 1);
                            break;
                        case 5:break;
                    }

                    $this->SetFillColor(224, 235, 255);
                    $this->SetTextColor(0, 0, 0);
                    $this->SetFont('Arial', '', 10);
                } else {
                    if ($n == 0) {
                        $this->Cell(25, 7, $col, 1);
                    }
                    if ($n == 1) {
                        $this->Cell(40, 7, $col, 1);
                    }
                    if ($n > 1) {
                        $this->Cell(30, 7, $col, 1);
                    }
                }
                $n++;
            }
            $this->Ln();
            $row_nb++;
        }
    }
    // génère un en-tete de tableau à partir d'un tableau de données
    function FancyTable_Header_Basic($nombre_colonne, $hauteur_cellule, $largeur, $position_x, $tableau_entete_colonnes)
    {
        // Couleurs, épaisseur du trait et police grasse
        // $this->SetfillColor(38, 55, 114); //couleur bleu foncé
        // $this->SetfillColor(38, 55, 200); //couleur bleu foncé
        $this->SetfillColor(160, 160, 160); //couleur bleu foncé
        $this->SetTextColor(0, 0, 0); //couleur noire
        // $this->SetTextColor(255);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 7);
        // Affichage de l'en-tête
        for ($i = 0;$i < $nombre_colonne;$i++) {
            $this->Cell($largeur[$i], $hauteur_cellule, $tableau_entete_colonnes[$i], 1, 0, 'C', 1);
        }
        $this->Ln($hauteur_cellule);
        $this->SetX($position_x);
        // Restauration des couleurs et de la police
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
    }
	 // génère un en-tete de tableau à partir d'un tableau de données, gère l'axe3 affichae ou non.
    function FancyTable_Header_Basic_axe3($axe3,$nombre_colonne, $hauteur_cellule, $largeur, $position_x, $tableau_entete_colonnes)
    {
        // Couleurs, épaisseur du trait et police grasse
        // $this->SetfillColor(38, 55, 114); //couleur bleu foncé
        // $this->SetfillColor(38, 55, 200); //couleur bleu foncé
        $this->SetfillColor(160, 160, 160); //couleur bleu foncé
        $this->SetTextColor(0, 0, 0); //couleur noire
        // $this->SetTextColor(255);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 7);
        // Affichage de l'en-tête
        for ($i = 0;$i < $nombre_colonne;$i++) {
			if(!$axe3 && $i <> 2){
				$this->Cell($largeur[$i], $hauteur_cellule, $tableau_entete_colonnes[$i], 1, 0, 'C', 1);
			}
        }
        $this->Ln($hauteur_cellule);
        $this->SetX($position_x);
        // Restauration des couleurs et de la police
        $this->SetFillColor(231, 231, 231);
        $this->SetTextColor(0);
        $this->SetFont('');
    }
    // Affiche un tableau basé sur un tableau de données
    function FancyTable_Basic($largeur, $hauteur_cellule, $position_x, $position_y, $tableau_data, $tableau_entete, $titre_tableau)
    {
        global $pdf_marge_bas;
        global $pdf_position_y_initiale;

        $this->SetFont('Arial', 'B', 7);
        $this->SetXY($position_x, $position_y);
        $largeur_totale = array_sum($largeur); //Calcule la largeur du tableau afin de positionne le titre
        $this->Cell($largeur_totale, $hauteur_cellule, $titre_tableau, 0, 1, 'C');

        $nombre_lignes = count($tableau_data);

        if ($this->nom_tableau->line_counter == "yes") { // si le tableau doit afficher un compteur de lignes
            array_unshift($tableau_entete, "Rank"); //ajoute l'entête "Rank" en début de tableau
            for ($i = 0;$i < $nombre_lignes;$i++) {
                $tableau_counter[] = $i + 1; //crée un tableau avec les numéros de lignes
            }
            array_unshift($tableau_data, $tableau_counter); //ajoute les numéros de ligne au tableau des données
        }

        $nombre_colonne = count($tableau_entete);
        $this->Ln($hauteur_cellule); //saute une ligne
        $this->SetX($position_x); //repositionne le curseur
        // Affiche l'En-tête
        $this->FancyTable_Header_Basic($nombre_colonne, $hauteur_cellule, $largeur, $position_x, $tableau_entete);
        // Données
        $fill = 0;
        for ($j = 0;$j < $nombre_lignes;$j++) {
            for ($k = 0;$k < $nombre_colonne;$k++) {
                $valeur = $tableau_data[$j][$k];
                $this->Cell($largeur[$k], $hauteur_cellule, $valeur, 'LR', 0, 'C', $fill);
            }
            $this->Ln($hauteur_cellule);
            $this->SetX($position_x);
            // Teste si la position Y actuelle du curseur est supérieur à la hauteur de la page- la marge
            // auquel cas un saut de page est généré
            // echo "Y = ".$this->GetY()." , hauteur_page = $this->pdf_hauteur_page, marge_bas=$pdf_marge_bas <br>";
            if (($this->GetY() + $hauteur_cellule) >= ($this->pdf_hauteur_page - $pdf_marge_bas)) {
                $this->Cell(array_sum($largeur), 0, '', 'T'); //on met la bordure de bas de tableau sur la page considérée
                $this->AddPage(); //gère l'ajout de page manuellement car SetAutoPageBreak ne fonctionne pas
                $this->SetY($pdf_position_y_initiale);
                $this->SetX($position_x);
                // Affiche l'en-tête du tableau sur la nouvelle page
                $this->FancyTable_Header_Basic($nombre_colonne, $hauteur_cellule, $largeur, $position_x, $tableau_entete);
            }
            $fill = !$fill;
        }
        $this->Cell(array_sum($largeur), 0, '', 'T');
    }

    function FancyTable_Alarm($axe3,$largeur, $hauteur_cellule, $position_x, $position_y,
        $tableau_data, $tableau_entete, $titre_tableau)
    {
        global $pdf_marge_bas;
        global $pdf_position_y_initiale;
		global $view;
		global $database_connection;

        $this->SetFont('Arial', 'B', 7);
        $this->SetXY($position_x, $position_y);
        // Calcule la largeur du tableau afin de positionner le titre
		$largeur_totale = 0;
        for($s = 0;$s < count($tableau_entete);$s++){
			$largeur_totale += $largeur[$s];
		}
        // echo "largeur totale = $largeur_totale<br>";
        $this->Cell($largeur_totale, $hauteur_cellule, $titre_tableau, 0, 1, 'C');

        $nombre_lignes = count($tableau_data);
        // si le tableau doit afficher un compteur de lignes
        if ($this->nom_tableau->line_counter == "yes") {
            // ajoute l'entête "Rank" en début de tableau
            array_unshift($tableau_entete, "Rank");
            for ($i = 0;$i < $nombre_lignes;$i++) {
                // crée un tableau avec les numéros de lignes
                $tableau_counter[] = $i + 1;
            }
            // ajoute les numéros de ligne au tableau des données
            array_unshift($tableau_data, $tableau_counter);
        }

        $nombre_colonne = count($tableau_entete);
        // saute une ligne
        $this->Ln($hauteur_cellule);
        // repositionne le curseur
        $this->SetX($position_x);


		// Données
        $fill = 1;
		$table_title = '';
		$cpt_tableau = 0;

        foreach($tableau_data as $ligne) {

            unset($largeur_tmp, $largeur_init);
			// a chaque ligne, on regarde si on a changé d'alarm ou top / worst list
			if ($table_title != $ligne[0]) {
				// changement de table
				$table_title = $ligne[0];
				if ($view == 'top-worst') {
					$title = 'Top / Worst list: ' . $ligne[0] . ' based on ' . $ligne[3];
					// on a besoin de savoir si le tri est par asc / desc
					// on cherche donc la top/worst list ayant pour nom $ligne[0] :
					$query = "SELECT list_sort_asc_desc FROM sys_definition_cell_list WHERE list_name='".$ligne[0]."' LIMIT 1";
					$get_list = pg_query($database_connection,$query);
					if (pg_num_rows($get_list)) {
						$row = pg_fetch_array($get_list);
						$title .= ' '.$row['list_sort_asc_desc'];
					}
				} else if($view == 'dyn_alarm') {
					$title = 'Dynamic Alarm: ' . $ligne[0];
				} else {
					$title = 'Static Alarm: ' . $ligne[0];
				}
	  			$this->Ln($hauteur_cellule);
				$this->Table_Title_Header_axe3($axe3,$nombre_colonne, $hauteur_cellule, $largeur, $position_x,$title);
    		    $this->FancyTable_Header_Basic_axe3($axe3,$nombre_colonne, $hauteur_cellule,$largeur, $position_x, $tableau_entete);
				$fill = 0;
			}

            for($d = 0;$d < count($ligne);$d++) {
			//echo count($ligne[0])."<br>";
                if ($d == 0)
                    $this->SetX($position_x);
                $nb = count($ligne);
				if ($view == 'top-worst') {
				// cas d'une top-worst list
	                $nb_ligne = floor(($nb-3)/2);
					if(!$axe3 && $d <> 2){ // empêche l'affichage de l'axe3.
						if ($d <= 2) {
							$hauteur = $nb_ligne * $hauteur_cellule;
							$this->Cell($largeur[$d], $hauteur, ucfirst(trim($ligne[$d])), 1, 0, 'C', $fill);
							$largeur_init += $largeur[$d];
							$largeur_tmp += $largeur[$d];
						} else {
							$pos_x = $position_x + $largeur_tmp;
							// $this->SetX($pos_x);
							// on calcule la largeur de la cellule $d (qui correspond à la largeur de la cellule 3 ou 4)
							if ($d%2) {
								$lc = 3;
							} else {
								$lc = 4;
							}
							$this->Cell($largeur[$lc], $hauteur_cellule, $ligne[$d], 1, 0, 'C', $fill);
							$largeur_tmp += $largeur[$d];
							if ((($d-2) % 2) == 0) {
								$this->Ln($hauteur_cellule);
								$largeur_tmp = $largeur_init;
								$this->SetX($position_x + $largeur_tmp);
							}
						}
					}
				} else {
				// cas d'une static alarm
	                $nb_ligne = floor(($nb-1)/4);
					if(!$axe3 && $d <> 2){ // empêche l'affichage de l'axe3.
						if ($d <= 2) {
						// if ($d <= 3) {
							$hauteur = $nb_ligne * $hauteur_cellule;
							$this->Cell($largeur[$d], $hauteur, ucfirst(trim($ligne[$d])), 1, 0, 'C', $fill);
							$largeur_init += $largeur[$d];
							$largeur_tmp += $largeur[$d];
						} else {
							$pos_x = $position_x + $largeur_tmp;
							// $this->SetX($pos_x);

							$this->Cell($largeur[(($d-3)%4)+3], $hauteur_cellule, $ligne[$d], 1, 0, 'C', $fill);
							$largeur_tmp += $largeur[$d];
							if ((($d-2) % 4) == 0) {
						// if ((($d-3) % 4) == 0) {
								$this->Ln($hauteur_cellule);
								$largeur_tmp = $largeur_init;
								$this->SetX($position_x + $largeur_tmp);
							}
						}
					}
				}
            }

			$cpt_tableau++;
            if (($this->GetY() + $hauteur) >= ($this->pdf_hauteur_page - $pdf_marge_bas)) {
                // echo $this->GetY()." + $hauteur >=  $this->pdf_hauteur_page - $pdf_marge_bas <br>";
				//echo "saut<br>";
                $this->SetX($position_x);
				if($axe3)
					$this->Cell($largeur_totale, 0, '', 'T'); //on met la bordure de bas de tableau sur la page considérée
                $this->AddPage();
                $this->SetY($pdf_position_y_initiale);
                $this->SetX($position_x);
				$val = $tableau_data[$cpt_tableau];
				$titre_table_page_precedente = $val[0];
				$val = $tableau_data[$cpt_tableau-1];
				$titre_table_page_courrante = $val[0];
				// Si ce n'est pas le même tableau qui est affiché sur la page précédente et la nouvelle page courrante alors on n'affiche pas l'en tête du tableau précédent.
				if($titre_table_page_precedente == $titre_table_page_courrante){
					$this->FancyTable_Header_Basic_axe3($axe3,$nombre_colonne, $hauteur_cellule, $largeur, $position_x, $tableau_entete);
					$fill = 1;
				}
            }
            $fill = !$fill;
        }
        $this->SetX($position_x);
        if($axe3) $this->Cell($largeur_totale, 0, '', 'T');
		//exit;
    }

    function cadre_image_from_existing_png($nom_image, $titre, $largeur_image, $comment, $position_x, $position_y, $scale)
    {
        global $pdf_marge_bas, $pdf_position_y_initiale;
        global $database_connection;
        global $offset_position_y;
        // récupère toutes les informations sur le graphe
        if (file_exists($nom_image)) {
            $size_image = getimagesize ($nom_image);
            $rapport_largeur_hauteur = $size_image[0] / $size_image[1]; //calcule le rapport largeur par hauteur
            // calcule le nombre de ligne présent dans le commentaire qui est un array
            if (count($comment) > 0 && $comment[0] != "") {
                $nombre_ligne_commentaire = count($comment);
            } else {
                $nombre_ligne_commentaire = 0;
            }
            // Affiche le graphe
            $hauteur_image = $largeur_image / $rapport_largeur_hauteur;
            $this->hauteur_image = $hauteur_image;
            // calcule la hauteur de l'image, des espace et des commentaire afin de vérifier si ca dépasse ou non la limite de bas de page
            $hauteur_evaluee = $position_y + $hauteur_image + $pdf_marge_bas + 4 * $nombre_ligne_commentaire; //on considere qu'il faut une ligne de hauteur 4 pour écrire un commentaire
            if ($hauteur_evaluee > $this->pdf_hauteur_page) {
                $this->AddPage(); //gère l'ajout de page manuellement car SetAutoPageBreak ne fonctionne pas
                $this->position_y = $pdf_position_y_initiale;
                $position_y = $pdf_position_y_initiale;
            }
            $espace_droit_gauche = 0.5; //Espace entre l'image et le cadre côté gauche et côté droit
            $espace_haut_bas = 6.5; //Espace entre l'image et le haut ou bas du cadre
            $this->SetTextColor(0, 0, 0); //couleur noire
            $this->SetfillColor(155, 155, 155); //couleur bleu foncé
            $this->SetFont('Arial', 'I', 10);
            // défini le cadre
            $this->Rect($position_x - $espace_droit_gauche, $position_y - $espace_haut_bas, $largeur_image + 2 * $espace_droit_gauche, $hauteur_image + $espace_haut_bas + 4 * $nombre_ligne_commentaire, "F");
            // défini le titre
            $this->Text($position_x + ($largeur_image - $this->GetStringWidth($titre)) / 2, $position_y-2, $titre);
            // défini le commentaire
            $this->SetFont('Arial', 'I', 8);
            $trans_tbl = get_html_translation_table (HTML_ENTITIES); //recupere la table de translation HTML
            $trans_tbl = array_flip ($trans_tbl); //retourne la table
            foreach ($comment as $key => $ligne_comment) {
                $ligne_comment = strtr ($ligne_comment, $trans_tbl); //converti les carctères HTML en caratères normaux
                $this->Text($position_x + ($largeur_image - $this->GetStringWidth($ligne_comment)) / 2, $position_y + $key * 4 + $hauteur_image + $espace_haut_bas / 2, $ligne_comment);
            }
            // Affcihe l'image
            $largeur_image = ceil($largeur_image * $scale);
            $hauteur_image = ceil($hauteur_image * $scale);
            $this->Image($nom_image, $position_x , $position_y , $largeur_image, $hauteur_image, "PNG");
            $this->position_y = $this->position_y + $hauteur_image + 20;
        } else {
            $this->position_y = $this->position_y - $offset_position_y; //repositionne la position y dans le cas où l'image du graphe n'a pas ete trouvee
        }
    }
}

?>

<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
/*
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?
/*
*	@cb2001_iu2030_111006@
*
*	11/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.1
*
*	Parser version iu_2.0.3.0
*/
?>
<?
	/*
		Enregistrement des page configuration des range sur les kpi et les raw counter.

		- maj 11/10/2006, christophe : message d'erreur si les data ne sont pas conformes (sup < inf ...)

		- maj 28/02/2007, benoit : pour la sauvegarde en base de la valeur '$svg_style', on transforme '$filled_transparence' en '$filled_opacity' afin de sauvegarder la valeur d'opacité et non celle de transparence


	*/
	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");


	// analyse les variables envoyées
	$id_element	= $_GET['id_element'];
	$type		= $_GET['type'];
	$family		= $_GET['family'];
	$product		= $_GET['product'];
	
	// Element vide.
	if (!$id_element || !$type) {
		header("location:pageframe_range.php?product=$product&family=$family");
		exit;
	}

	// On vérifie la validité de tous les ranges.
	for ($i=1; isset($_POST["max_range".$i]);$i++) {
		// 1 . on vérifie si range sup > range inf.
		$range_sup	= $_POST['max_range'.$i];
		$range_inf	= $_POST['min_range'.$i];
		//echo "<br> range_sup:".$range_sup. " // range_inf:".$range_inf;
		if (($range_inf != '') && ($range_sup != '')) {
			if ($range_inf >= $range_sup) {
				//echo "ok >> $i";
				$range_id = $i;
				//echo "range inf : ".$range_inf." / range sup : ".$range_sup." ligne $i";
				$range_error = " Error : save aborted [invalid min / max]";
				header("location:pageframe_range.php?product=$product&family=$family&id_element=$id_element&type=$type&range_id=$range_id&range_error=$range_error");
				exit;
			}
			// 2 . on vérifie si tous les range sont de type numeric.
			if (!is_numeric($range_inf) || !is_numeric($range_sup)) {
				$range_id = $i;
				$range_error = " Error : save aborted [invalid numeric number]";
				header("location:pageframe_range.php?product=$product&family=$family&id_element=$id_element&type=$type&range_id=$range_id&num=num&range_error=$range_error");
				exit;
			}
		}
	}

	// on se connecte a la base de données du produit en cours
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db_prod		= Database::getConnection($product);

	// On recherche si l'enregistrement existe déjà.
	// Si des enregistrements existe déjà, on les supprime.
	$query = "
		SELECT *
		FROM sys_data_range_style
		WHERE id_element='$id_element'
			AND data_type='$type'
			AND family = '$family' ";
	$ranges = $db_prod->getall($query);
	
	if ($ranges) {
		$query = "
		DELETE FROM sys_data_range_style
		WHERE id_element = '$id_element'
			AND data_type = '$type'
			AND family = '$family' ";
		$db_prod->execute($query);
	}


	// On parcours tous les ranges.
	for ($i=1; isset($_POST["max_range".$i]);$i++) {
	
		
		$range_order	= $i;
		$range_sup	= $_POST["max_range".$i];
		$range_inf	= $_POST["min_range".$i];
		
		if (($range_inf != '') && ($range_sup != '')) {
			// On vérifie la validité des ranges.
			if ($range_inf >= $range_sup) {
				//echo "ok";
				$range_id = $i;
				header("location:pageframe_range.php?product=$product&family=$family&id_element=$id_element&type=$type&range_id=$range_id");
				exit;
			}

			$filled_color	= $_POST["fill_color".$i];
			$color		= $_POST["stroke_color".$i];

			// 28/02/2007 - Modif. benoit : pour la sauvegarde en base de la valeur '$svg_style', on transforme '$filled_transparence' en '$filled_opacity' afin de sauvegarder la valeur d'opacité et non celle de transparence
			$filled_transparence	= $_POST["filled_transparence".$i];
			$filled_opacity		= 1-$filled_transparence;
			$svg_style			= "stroke-opacity:$filled_opacity;stroke:$color;fill-opacity:$filled_opacity;fill:$filled_color";
			$query = "
				INSERT INTO sys_data_range_style
				(id_element,data_type,svg_style,range_sup,range_inf,filled_color,color,filled_transparence,range_order,family)
				VALUES
				('$id_element','$type','$svg_style','$range_sup','$range_inf','$filled_color','$color','$filled_transparence','$range_order','$family') ";			
			$db_prod->execute($query);
		}
	}
	
	header("location:pageframe_range.php?product=$product&family=$family&id_element=$id_element&type=$type");
	exit;
?>

<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Astellia
*
*	Composant de base version cb_4.0.0.00
*
* 	- maj 18/01/08 - maxime - Deux modes d'affichage ( portrait/ paysage ) 
*/
?>
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
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?>
<?php
/*
* Classe de création des fichiers PDF.
* @package Object_PDF
* @author
* @version V1 2005-05-17
*/
/*
	- maj 20/11/2006, benoit : correction du formatage de la date dans le nom des rapports pdf
	- maj 21/11/2006, christophe : correction du décalage des graphs sur la première page des PDF.
	- maj 21/11/2006, benoit : inhibition de la date dans le nom des rapports pdf
	- maj 21/11/2006, benoit : ajout d'un parametre '$pdf_filename' au constructeur de la classe pour separer nom    et titre du pdf
*/

session_start();

include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");

include_once($repertoire_physique_niveau0 . "pdf/fpdf153/fpdf.php");
include_once($repertoire_physique_niveau0 . "class/acurioPDF.class.php");
include_once($repertoire_physique_niveau0 . "pdf/complement_pdf.php");
define('FPDF_FONTPATH',$repertoire_physique_niveau0."pdf/fpdf153/font/");

// Chemin vers les images contenues dans la barre de titre des PDF.
$pdf_image_logo =			$repertoire_physique_niveau0."images/bandeau/logo_operateur.jpg";
$pdf_image_logo_astellia =	$repertoire_physique_niveau0."images/client/logo_client_pdf.png";

$tableau_html=$str; // pour la caddy

class Object_PDF {
// 21/11/2006 - Modif. benoit : ajout d'un parametre '$pdf_filename' au constructeur de la classe
	function  Object_PDF($mode_robot, $page_title, $pdf_filename, $pdf_repertoire_stockage,$display_mode = 'landscape')
	{
		global  $pdf_image_logo,$pdf_image_logo_astellia,$tableau_pdf,$database_connection,$id_user,$niveau0,$tableau_html;

		$this->database_connection = 	$database_connection;
		$this->tableau_html = 			$tableau_html;
		$this->mode_robot = 			$mode_robot;

		if ($this->mode_robot!="auto")
		{
			$this->mode_robot="online";
		}

		$this->get_pdf_element();
		$this->ylong = 40;
		$this->orientation_pdf = ($display_mode == 'landscape') ? 'L' : 'p';
		
		$this->Object_PDF=new PDF($this->orientation_pdf,'mm','A4'); //orientation = P ou L, unité = mm, Taille = A4
		$this->Object_PDF->AliasNbPages();
		$this->Object_PDF->setDisplayMode($display_mode); // 21/01/08 - maxime - On récupère le mode d'affichage
		$this->Object_PDF->SetAutoPageBreak(true);

		// 20/11/2006 - Modif. benoit : correction du formatage de la date dans le nom des rapports pdf
		$date_du_jour = date("Y")."_".date("m")."_".date("d");

		// 21/11/2006 - Modif. benoit : inhibition de la date dans le nom des rapports pdf
		$nom_rapport_pdf =trim($pdf_repertoire_stockage."$pdf_filename"/*."_".$date_du_jour*/.".pdf");

		if (file_exists($nom_rapport_pdf)) unlink($nom_rapport_pdf);
		$this->Object_PDF->nom_rapport_pdf = $nom_rapport_pdf;

		$this->Object_PDF->orientation_pdf=$this->orientation_pdf;
		$this->Object_PDF->titre_pdf=$page_title;
		
		
		// 21/01/08 - maxime - On limite le titre à 80 caractères

		$titre = ($nb_char > 80) ? substr($this->Object_PDF->titre_pdf,0,80)."..." : $this->Object_PDF->titre_pdf;
			
		$this->Object_PDF->setHeaderTitle($titre);
		
		$this->Object_PDF->AddPage();
		$this->Object_PDF->SetX(5);

		$this->Add_content_in_Pdf();

		$this->Generate_pdf();
	}

	function parametre_sortie()
	{

		return $this->Object_PDF->nom_rapport_pdf;

	}

	function Generate_pdf()
	{

		$this->Object_PDF->Output($this->Object_PDF->nom_rapport_pdf);
		$this->Object_PDF->Close();

		if ($this->mode_robot!="auto")
		{
			?>
			<script type="text/javascript">
			//document.location="<?=$this->Object_PDF->nom_rapport_pdf?>";
			document.location="view_pdf.php?pdf=<?=$this->Object_PDF->nom_rapport_pdf?>";
			</script>
			<?
		}
		else
		{
			$this->parametre_sortie();
		}
	}
	function get_pdf_element()
	{
		global $id_user;

		/*
			Si on fait un export PDF depuis le caddy, la variable $_GET['type_PDF']
			est définie
		*/
		if(isset($_GET['type_PDF'])){
			// On fait un export PDF depuis le caddy.
			$query="select * from sys_panier_mgt where id_user='$id_user' order by oid asc";
			$result = pg_query($this->database_connection, $query);
			$result_nb = pg_num_rows($result);
			for ($i = 0;$i < $result_nb;$i++)
			{
				$row = pg_fetch_array($result, $i);
				// si l'objet est de type table, on ne met pas le même contenu.
				if($row["object_type"] == "table")
				{
					$this->tableau_pdf["object_content"][$i]= $this->tableau_html[$row["object_id"]];       // $row["object_id"];
				}
				else if($row["object_type"] == "builder_report")
				{
					$this->tableau_pdf["object_content"][$i]=urldecode($row["object_id"]);
				} else {
					$this->tableau_pdf["object_content"][$i]=$row["object_id"];
				}
				$this->tableau_pdf["object_type"][$i]=$row["object_type"];
				$this->tableau_pdf["object_titre"][$i]=$row["object_title"];
	        }
		} else {

                    // 17/01/2011 BBX
                    // Table sys_pdf_mgt obsolète
                    // BZ 20200
                    /*
			$query="select * from sys_pdf_mgt where id_user='$id_user' and object_type='graph_dashboard_solo' order by oid asc";

			$result = pg_query($this->database_connection, $query);
			$result_nb = pg_num_rows($result);
			for ($i = 0;$i < $result_nb;$i++)
			{
				$row = pg_fetch_array($result, $i);
				$this->tableau_pdf["object_content"][$i]=$row["object_content"];
				$this->tableau_pdf["object_type"][$i]=$row["object_type"];
				$this->tableau_pdf["object_titre"][$i]=$row["object_titre"];
			}*/
		}
	}


	function diplay_titre($titre){
		global $repertoire_physique_niveau0;
		
		// 21/01/08 - maxime - On décale le titre si le mode d'affichage du rapport est en mode portrait
		if(!$this->second_img and $this->orientation_pdf == 'p'){
			$y = $this->Object_PDF->GetY() + 10;
			$this->Object_PDF->SetY($y);
		}
		
		$this->Object_PDF->Image($repertoire_physique_niveau0.'images/icones/pdf_alarm_titre_arrow.png', 10, $this->Object_PDF->GetY() - 4 );
 
		$y= $this->Object_PDF->GetY();

		$titre_len=strlen($titre);
		$x= 20;
		$this->Object_PDF->SetFont('Arial', 'B', 12);

		$this->Object_PDF->Bookmark(trim($titre,chr(160)));
		$this->Object_PDF->Text($x,$y,$titre);
		$this->ylong = $this->ylong + 3;
	
	}

	function saut_de_page(){
		if ($this->ylong>190){
			$this->Object_PDF->AddPage();
			$this->ylong=0;
			$this->Object_PDF->SetX(5);
			$this->Object_PDF->SetY(40);
			$this->ylong=40;
		}
	}

	function Add_content_in_Pdf()
	{

		for ($i=0;$i<count($this->tableau_pdf["object_content"]);$i++)
		{
			$this->saut_de_page();

			switch($this->tableau_pdf["object_type"][$i])
			{
				case "graph" :
					$this->diplay_titre($this->tableau_pdf["object_titre"][$i]);
					$this->add_image($this->tableau_pdf["object_content"][$i]);
					/*$this->Object_PDF->ln(10);
					$this->ylong=$this->ylong+15;*/
					$this->Object_PDF->SetX(5);
					$this->Object_PDF->SetY(40);
					$this->ylong=40;
					$this->tmp = $i + 1;
					if($this->tmp<count($this->tableau_pdf["object_content"]))$this->Object_PDF->AddPage();
					break;
				case "graph_dashboard_solo" :
					// 21/01/08 - maxime - On vérifie si l'iimage est la deuxième de la page en mode portrait					
					$this->second_img = ( $this->orientation_pdf == 'p' and is_int($this->tmp / 2) ) ? true : false;
					$this->diplay_titre($this->tableau_pdf["object_titre"][$i]);
					$this->add_image($this->tableau_pdf["object_content"][$i]);

					$this->Object_PDF->SetX(5);
					// 21/01/08 - maxime - On décale la deuxième img en mode portrait
					if($this->second_img){
						$y = $this->ylong + 30;
						$this->Object_PDF->SetY($y);
					}else{
						$this->Object_PDF->SetY(40);
						$this->ylong = 40;
					}
					
					$this->tmp = $i + 1;
					
					// 21/01/08 - maxime - on supprime le saut de page pour la deuxième img en mode portrait
					if($this->tmp<count($this->tableau_pdf["object_content"])){
						if( $this->orientation_pdf == 'p' and is_int($this->tmp / 2) or $this->orientation_pdf !== 'p'){
							$this->Object_PDF->AddPage();
						}
					}
					break;
				case "table" :
					$this->diplay_titre($this->tableau_pdf["object_titre"][$i]);
					$this->add_html_table($this->tableau_pdf["object_content"][$i]);
					$this->Object_PDF->ln(10);
					break;
				case "builder_report" :
					$this->diplay_titre($this->tableau_pdf["object_titre"][$i]);
					$this->add_html_table($this->tableau_pdf["object_content"][$i]);
					$this->Object_PDF->ln(10);
					break;
			}
		}

	}

	function  add_html_table($table)
	{

		$str="";

		$str_len=strlen($table);
		$delete=false;
		
		for ($i=0;$i<$str_len;$i++){
			$char= substr($table,$i,1);
			if ($char=="<"){$delete=true;}
			if ($char==">"){$delete=false;}
			if ($delete==false){$str.=$char;}
		}
		
		$str=str_replace("<br>","",$str);
		$str=str_replace("\n","",$str);
		$str=str_replace(">>>>>","",$str);
		$str=str_replace(">>>>","/",$str);
		$str=str_replace(">>>","",$str);
		$str=str_replace(">>","@",$str);
		$str=str_replace(">","/",$str);

		$str_array_line=explode('/',$str);

		for ($k=0;$k<count($str_array_line);$k++){
		     $this->str_array[$k]=explode('@',$str_array_line[$k]);
		}
		
		$this->add_pdf_table();
	}

	function add_pdf_table()
	{
	  $this->Object_PDF->SetX(5);

	  //calcul de la largeur max de chaque colonne.
	  $scale=2.4;

	  $this->pdf_data_array[0][0]="";

	  for ($i=0;$i<count($this->str_array);$i++)
	     {
	      for ($j=0;$j<count($this->str_array[$i]);$j++)
	           {
	            if ($i==0)
	            {
	             $this->pdf_data_array[$i][$j+1]=$this->str_array[$i][$j];
	            }
	            else
	             {
	             $this->pdf_data_array[$i][$j]=$this->str_array[$i][$j];
	            }
	           }
	     }


	       for ($i=0;$i<count($this->pdf_data_array);$i++)
	     {
	      for ($j=0;$j<count($this->pdf_data_array[$i]);$j++)
	           {
	            $larg_cell_max[$j]=0;
	           }
	      }

	  for ($i=0;$i<count($this->pdf_data_array);$i++)
	     {
	      for ($j=0;$j<count($this->pdf_data_array[$i]);$j++)
	           {
	            $larg_cell[$j]=strlen($this->pdf_data_array[$i][$j]);

	            if ($larg_cell[$j]>$larg_cell_max[$j]){$larg_cell_max[$j]=$larg_cell[$j];}
	           }
	     }

	 //prepare les bloc
	  $larg=ceil($larg_cell_max[0]*$scale);
	  $larg_init=ceil($larg_cell_max[0]*$scale);
	  $bloc=0;
	  $nb=0;

	 for ($k=1;$k<count($larg_cell);$k++) {
	        $larg=$larg+(ceil($larg_cell_max[$k]*$scale));

	        $nb=$nb+1;
	        $nb_par_bloc[$bloc]=$nb;

	        if ($larg>175){
	            $bloc=$bloc+1;
	            $larg=ceil($larg_cell_max[0]*$scale);
	            $nb=0;
	        }


	 }
	 $q=0;

	 for ($k=0;$k<=$bloc;$k++){

	    $depart=1;
	    for ($q=0;$q<$k;$q++){
			$depart=$depart+$nb_par_bloc[$q];
		}
		$fin=$depart+$nb_par_bloc[$k];

	  //pour chaque bloc j'affiche la premeire colonne

		if ($fin>$depart){

		 for ($i=0;$i<count($this->pdf_data_array);$i++)
		     {

		      $this->Object_PDF->SetX(10);

		         if ($i==0)
		               {
		                $this->Object_PDF->SetTextColor(0,0,0); //couleur noire
		                $this->Object_PDF->SetfillColor(255, 255,255); //couleur bleu foncé
		                $this->Object_PDF->SetFont('Arial', '', 10);
		                $largeur_cell=ceil($larg_cell_max[0]*$scale);
		                $this->Object_PDF->Cell($largeur_cell,5,$this->pdf_data_array[$i][0],0, 0, 'C', 1);
		               }
		              else
		               {
		                $this->Object_PDF->SetTextColor(255,255,255); //couleur noire
		                $this->Object_PDF->SetfillColor(155, 155, 155); //couleur bleu foncé
		                $this->Object_PDF->SetFont('Arial', '', 10);

		                $largeur_cell=ceil($larg_cell_max[0]*$scale);
		                $this->Object_PDF->Cell($largeur_cell,5,$this->pdf_data_array[$i][0], $cell_border, 0, 'C', 1);
		               }

		           for ($j=$depart;$j<$fin;$j++)
		           {

		            if ($i==0 and $j==0){$cell_border=0;}else{$cell_border=1;}
		            if (($i==0 and $j>0))
		               {

		                $this->Object_PDF->SetTextColor(255,255,255); //couleur noire
		                $this->Object_PDF->SetfillColor(155, 155, 155); //couleur bleu foncé
		                $this->Object_PDF->SetFont('Arial', '', 10);


		               }
		              else
		               {
		                $this->Object_PDF->SetTextColor(0,0,0); //couleur noire
		                $this->Object_PDF->SetfillColor(255, 255,255); //couleur bleu foncé
		                $this->Object_PDF->SetFont('Arial', '', 10);
		               }

		            $largeur_cell=ceil($larg_cell_max[$j]*$scale);
		            $this->Object_PDF->Cell($largeur_cell,5,$this->pdf_data_array[$i][$j], $cell_border, 0, 'C', 1);

		         }
		       $this->Object_PDF->ln(5);
		      $this->ylong=$this->ylong+5;
		      //$this->saut_de_page();
		     }

		   $this->Object_PDF->ln(5);
		   $this->ylong=$this->ylong+5;
		}
	 }
	}

	function  add_image($image_name){
	
		global  $repertoire_physique_niveau0;
		$this->nb_img = (!isset($this->nb_img) ) ? 1 : $this->nb_img + 1;
		$scale = 0.21;
		$path_of_png_file = $repertoire_physique_niveau0.get_sys_global_parameters("pdf_save_dir");
		$file = $path_of_png_file.$image_name;

		$size_image = getimagesize ($file);
		
		// 18/01/08 - maxime - On diminue la taille des img en mode portrait
		$largeur_image = ( $this->orientation_pdf != 'p' ) ? ceil($size_image[0] * $scale) : ceil($size_image[0] * $scale) / 1.5;
		$hauteur_image = ( $this->orientation_pdf != 'p' ) ? ceil($size_image[1] * $scale) : ceil($size_image[1] * $scale) / 1.5;
				
		// 18/01/08 - maxime - Deux modes d'affichage (portrait / paysage)
		if($this->orientation_pdf == 'L'){
			$width = 290;
			$height = 210;
		}else{
			$width = 210;
			$height = 290;
		}
		
		$largeurPDF = $width;
		$hauteurPDF = $height - ceil($this->Object_PDF->GetY() + 25); // hauteur restante.

		$this->ylong = $this->ylong + $hauteur_image;
		
		// 18/01/08 - maxime - On affiche sur la même page deux graphs si on est en mode portrait
		if( ( is_int($this->nb_img / 2) and $this->orientation_pdf == 'p' and count($this->tableau_pdf["object_content"]) < $this->tmp ) or $this->orientation_pdf != 'p' ){
			$this->saut_de_page();
			// $this->ylong = $this->ylong + 5;
			$y = $this->Object_PDF->GetY() + 10;
			$this->Object_PDF->setY($y);
		}
	
		$this->Object_PDF->SetX(10);

		// On centre horizontalement et verticalement l'image.
		$x = ceil(($largeurPDF / 2) - ($largeur_image / 2));
		$h = ( $this->orientation_pdf !== 'p' ) ? 2 : 8;
		$y_tmp = ceil(($hauteurPDF / $h) - ($hauteur_image / $h)) + $this->Object_PDF->GetY();
		
		// 21/01/08 - maxime - On décale la deuxième img en mode portrait 
		if(!$this->second_img and $this->orientation_pdf == 'p')
			$y = $y_tmp + 10;
		else
			$y = $y_tmp;
	
		$this->Object_PDF->SetfillColor(155, 155, 155);

		$this->Object_PDF->Rect($x-0.5,$y-0.5,$largeur_image+1,$hauteur_image+1, "F");

		$this->Object_PDF->SetX($x);
		
		if($this->second_img) // 21/01/08 - maxime - On décale la deuxième img en mode portrait 
			$this->Object_PDF->SetY( $this->ylong + 5);

		$this->Object_PDF->Image($file, $x, $y,$largeur_image);
		
		$y=$this->Object_PDF->GetY();
	
		
		$this->Object_PDF->SetX(5);
		$this->Object_PDF->SetY($y);

		//apres tout image saut de ligne
		$this->Object_PDF->ln($hauteur_image);

	}
}


?>

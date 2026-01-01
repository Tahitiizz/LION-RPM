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
		Classe alarmExportExcel
		permet de gérer les exportations de fichiers excel.
		last update 2005 10 06
	*/
	class alarmExportExcel{

		/*
			Constructeur de la classe
			$nom_onglet :	 nom de la feuille de calcul
			$tab : tableau contenant la liste des alarmes.
			$headers : titres des en têtes du tableau.
		*/
		function alarmExportExcel($tab, $headers){
			$this->tab = $tab;
			$this->headers = $headers;
		}

		/*
			Permet de générer le fichier excel dont les données sont passées en paramètres.
		*/
		function generateExcelData(){

			global $repertoire_physique_niveau0;
			$i = 0;
			// On construit un nouveau tableau pour générer le fichier excel.
			foreach ($this->tab as $key => $ligne_alarme) {
				foreach ($ligne_alarme as $identifiant_alarme => $ligne_alarm_result) {
					foreach ($ligne_alarm_result as $identifiant_result => $ligne_a_afficher) {
						$p=0;
						foreach ($ligne_a_afficher as $titre_colonne => $element) {
							// Gestion du cas particulier du égal.
							if(strlen($element) == 1 && $element == "=") $element=" = ";
							if($titre_colonne != "na_value_gis"){
								$data[$i][$p] = $element;
								$p++;
							}
						}
					}
					$i++;
				}
			}

			$this->data = $data;
			$this->ordonne = $ordonne;

		}

		/*
			Permet d'obtenir les éléments nécessaires à la construction du fichier excel
		*/
		function getExcelData(){ return $this->data; }
		function getExcelHeaders(){ return $this->headers; }

	}

?>

<?
/*
*	@cb50000@
*
*	27/07/2009 - Copyright Astellia
*
*	27/07/2009 BBX : utilisation des fonctions prototype pour assurer la compatibilité des navigateurs
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
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?
/*
  * 01 09 2006 : MD - Creation du fichier
  */

/*
  Permet d'afficher l'interface permettant d'activer une fonctionnalite de l'interface

  L'interface est composee d'un bouton permettant d'activer la nouvelle option  de l'interface

  Un clique sur le bouton lance l'execution du script $script avant de rediriger la page vers $url
  Le script est execute en via la technologie AJAX

  Tant que le script n'a pas fini de s'executer un indicateur de chargement est affichee ($loading_img)

  */
class option_activator{

	var $title;//titre de l'interface si $title_img n'est pas fourni ou est invalide
	var $title_img;//bandeau titre
	var $msg_info;//information
	var $script_to_execute;//le script d'activation
	var $url_to_load;//page a charger apres l'execution de $script_to_execute
	var $loading_img;//indicateur de chargement

	function option_activator($title,$title_img,$msg_info,$script,$url,$loading_img){

		$this->title=$title;
		$this->title_img=$title_img;
		$this->msg_info=$msg_info;
		$this->script_to_execute=$script;
		$this->url_to_load=$url;
		$this->loading_img=$loading_img;

	}

	//affiche la partie superieur de l'interface
	function displayTitle(){

		?>
		<div class="texteGrisBoldGrand" style="text-align:center;margin-bottom:5px">
			<img src="<?=$this->title_img?>" alt="<?=$this->title?>"/>
		</div>
		<?

	}

	//fonction ajax permettant de lancer $this->script_to_execute
	function displayAJAXFunction()
	{
		// 27/07/2009 BBX : utilisation des fonctions prototype pour assurer la compatibilité des navigateurs
		?>
		<script>
			function setupOption(img,script,url_to_load)
			{
				$('setup_button').setStyle({visibility:'hidden'});
				$('loading_area').update('<img src="'+img+'" alt="Loading..." />');
				// On envoie la requete via Ajax
				new Ajax.Request(script,{
					onSuccess: function(res) {
						window.location = url_to_load;
					}
				});
			}
		</script>
		<?
	}

	//affiche le corps de l'interface
	function displayActivationArea(){

		?>
		<div id="setup_button">
			<table align="center" class="tabPrincipal" width="400px">

				<tr>
					<td class="texteGrisBold">
						<?=$this->msg_info?>
					</td>
				</tr>

				<tr>
					<td align="center">
						<input type="button" name="" value="Activate" class="bouton" style="margin:10px"
							   onclick="setupOption('<?=$this->loading_img?>','<?=$this->script_to_execute?>','<?=$this->url_to_load?>')"/>
					</td>
				</tr>
			</table>
		</div>

		<div id="loading_area" style="text-align:center"></div>
		<?

	}

	//pour afficher l'interface d'activation
	function display(){

		$this->displayTitle();
		$this->displayAJAXFunction();
		$this->displayActivationArea();

	}

}
?>

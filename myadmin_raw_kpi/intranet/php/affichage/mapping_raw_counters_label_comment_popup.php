<?
/**
 * @cb5100@
 *
 * 26/07/2010 OJT : Correction bz16719 et réorganisation du script
 *
 */
/*
*	@cb41000@
*
*	11/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	11/12/2008 BBX : modifications pour le CB 4.1 :
*	=> Utilisation des nouvelles méthodes et constantes
*	=> Utilisation de la classe de connexion àa la base de données
*	=> Gestion du produit
*
*	30/07/2009 GHX
*		- Correction du BZ 10653 [REC][Mapping]: modification description raw/compteur non fonctionnelle
*			-> Modification du filtre de validation du label de compteur
*			-> Ajout de l'id produit dans l'url du formulaire
*
*	10/12/2009 BBX : ajout du label pour "*". BZ 13278
*	19/01/2010 NSE : on n'interdit plus la simple quot ' dans le commentaire mais on effectue une vérification dessus (bz 13799)
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	- maj 15/05/2007 Gwénaël : suppresion des simples et doubles cotes dans les commentaires des Raw Counters
*	- maj 31/05/2007 Gwénaël : changement de la méthode pour recharger la fenetre principale on change l'url par la même url au lieu de faire un reload
*	- maj 12/06/2007 Gwénaël :  suppresions du retour à la ligne dans les commentaires
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
<?php
/*
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php
/*
*	@cb1300b_iu2000b_070706@
*
*	12/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0b
*
*	Parser version iu_2.0.0.0b
*
* 28-07-2006 : creation du fichier. Possibilité de changer le label et le commantaire du compteur
*
*   - maj 27/02/2007 Gwénaël : correction erreur JS ( ajout de "valid_RE" => FP_VALID_NAME, dans la construction des objects FPTextField)
*
*/
session_start();
$arborescence = 'Counter Label/Comment Modification';
include_once dirname(__FILE__).'/../../../../php/environnement_liens.php';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
require REP_PHYSIQUE_NIVEAU_0.'class.phpObjectForms/lib/FormProcessor.class.php';

$database = DataBase::getConnection( $_GET['product'] ); // Connexion à la base de données produit
$id_counter = $_GET["id"];
$query = "SELECT edw_field_name,edw_field_name_label,comment FROM sys_field_reference WHERE id_ligne='{$id}'";
$array_counter = $database->getRow($query);
$counter_name = $array_counter["edw_field_name"];
$counter_label = $array_counter["edw_field_name_label"];
$counter_comment = $array_counter["comment"];

// Initialize the phpObjectForms class
$fp = new FormProcessor(REP_PHYSIQUE_NIVEAU_0 . "class.phpObjectForms/lib/");
$fp->importElements(array("FPButton", "FPHidden", "FPText", "FPTextField", "FPTextArea"));
$fp->importLayouts(array("FPColLayout", "FPRowLayout", "FPGridLayout"));
$fp->importWrappers(array("FPLeftTitleWrapper"));
$leftWrapper = new FPLeftTitleWrapper( array( 'table_title_cell_width' => 120, 'table_field_cell_width' => 200 ) );
// 0. Create the form object
$myForm = new FPForm(array(
        "name" => 'myForm',
        "action" => 'mapping_raw_counters_label_comment_popup.php?id=' . $id_counter.'&product='.$_GET['product'],
        "enable_js_validation" => true,
        "display_outer_table" => true,
        "table_align" => 'center',
        ));
// 1. Form data structure
// TEXT : counter name
// - modif 27/02/2007 Gwénaël :
    // ajout de la ligne "valid_RE" => FP_VALID_NAME : permet de valider le champ lors avant de valider le formulaire
    // FP_VALID_NAME => type de vérification sur le champ
$form_counter_name = new FPTextField(array("title" => '<span class=texteGris>Name</span>                    ',
        "name" => 'counter_name',
        "value" => $counter_name,
        "size" => '45',
        "valid_RE" => FP_VALID_NAME,
        "readonly" => true,
        "wrapper" => &$leftWrapper,
        ));
// TEXT : counter label
//11:38 30/07/2009 GHX
// Correction du BZ  10653
// Modification du filtre de validation
$form_counter_label = new FPTextField(array("title" => '<span class=texteGris>Label</span>',
        "name" => 'counter_label',
        "value" => $counter_label,
        "size" => '45',
        "required" => true,
        "valid_RE" => FP_VALID_TITLE,
        "wrapper" => &$leftWrapper,
        ));
// TEXT : user_prenom
// 19/01/2010 NSE : on effectue une vérification sur le commentaire (bz 13799)
$form_counter_comment = new FPTextArea(array("title" => '<span class=texteGris>Comment</span>',
        "name" => 'counter_comment',
        "value" => $counter_comment,
        "valid_RE" => FP_VALID_COMMENT,
        "rows" => 5,
        "cols" => 34,
        "wrapper" => &$leftWrapper,
        ));
// SUBMIT
$form_submit_button = new FPButton(
                                array(
                                    "submit" => true,
                                    "name" => 'submit',
                                    "title" => 'Save',
                                    "css_class" => 'bouton',
                                    )
                        );
// 3. Form layout
// on a besoin d'une grille
$form_grid = new FPGridLayout(array("table_padding" => 1,
        "columns" => 1,
        ));
$form_grid->addElement($form_counter_name);
$form_grid->addElement($form_counter_label);
$form_grid->addElement($form_counter_comment);
$form_layout = new FPColLayout(array("elements" => array($form_grid)));
$form_layout->addElement(
                        new FPRowLayout(
                            array(
                                "table_align" => 'center',
                                'table_padding' => 2,
                                "elements" => array($form_submit_button)
                            )
                        )
                    );

$myForm->setBaseLayout($form_layout);
?>
<br />
<table width="85%" align="center" cellpadding="0" cellspacing="2" class="tabPrincipal">
    <tr>
        <td>
            <?php
                if( $myForm->getSubmittedData() && $myForm->isDataValid() )
                {
                    $edw_field_name_label = $_POST['counter_label'];
                    $edw_field_name_comment = $_POST['counter_comment'];
                    $tableau = explode( ',', 'à,ä,â,é,è,ë,ê,ï,î,ô,ö,ù,û,ü,ÿ,ç,²,&,~,§,²,€,£,¤,µ,°' );
                    // 15/05/2007 GHX Suppression des simples et doubles cotes dans les commentaires des Raw counter
                    // 12/06/2007 GHX Suppression du retour à la ligne
                    // 19/01/2010 NSE On n'interdit plus la simple quot ' dans le commentaire (bz 13799)
                    // 26/07/2010 OJT Correction bz 16719
                    $edw_field_name_comment = str_replace(array('"', "\n", "\r",), ' ', $edw_field_name_comment );
                    $edw_field_name_comment = str_replace( $tableau, ' ', $edw_field_name_comment );
                    $database->execute( "UPDATE sys_field_reference 
                                            SET edw_field_name_label='$edw_field_name_label',
                                                comment='$edw_field_name_comment'
                                            WHERE id_ligne='$id_counter'" );
                    ?>
                    <script>
                        // modif 31/05/2007 Gwénaël
                            // on change la méthode pour recharger la fenetre principal
                            // on remplace l'url par la même url  (équivalent à cliquer sur le OKde la barre d'adresse)
                            // au lien de recharger la place (équivalent à actualiser la page)
                        var win_open_href = window.opener.location.href;
                        window.opener.location.href = win_open_href;
                        //window.opener.location.reload();
                        self.close();
                    </script>
                    <?php
                }
                else
                {
                    $myForm->display();
                }
            ?>
        </td>
    </tr>
    <tr>
        <td>
            <!-- 10/12/2009 BBX : ajout du label pour "*". BZ 13278 -->
            <label style="font-size: 9px; color: red;">* Mandatory field</label>
        </td>
    </tr>
</table>
</body>
</html>

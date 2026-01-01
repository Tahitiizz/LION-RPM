<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*       03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
*/
?>
<?php
    /**
     * Cette classe genere un bouton de couleur qui fait appel à une palette quand on clique dessus
	 * On a ainsi un champ utilisable comme selecteur de couleur
	 *
	 * Un truc a savoir : on est obligé de passer le nom du formulaire à l'objet FPColorField
	 * car ce nom est necessaire dans l'appel à la palette de couleur.
	 *
     */

    class FPColorField extends FPElement {

        var $_size;
        var $_readOnly;
        var $_formName;

        function FPColorField($params)
        {
            FPElement::FPElement($params);
            if (isset($this->_size))
                $this->_size = $params["size"];
            else
                $this->_size = 16;
            if (!$this->_value)
                $this->_value = '#000000';
            if (isset($params['readonly'])) $this->_readOnly = $params["readonly"];
			$this->_formName = $params["formName"];
        }

/*
<input type="button" style="background-color:$color" class="hexfield" name="color1"
	onfocus=this.blur() value=""
	onclick="javascript:ouvrir_fenetre('$niveau0php/palette_couleurs_graphe.php?nom_zone=color1&nom_champ_cache=color','Palette','no','no',304,100)"
	onMouseOver="style.cursor='hand';" />

*/
        function echoSource()
        {

			global $niveau0;

            $this->_append(
                '<input type="button"'.
                    ' name="'.$this->_name.'_btn"'.
                    ' value=""'.
                    ' size="'.$this->_size.'"'.
                    (isset($this->_cssStyle) ?
                        ' style="'.$this->_cssStyle.'"' : ''
                    ).
					' style="background-color:'.$this->_value.';"'.
                    (isset($this->_cssClass) ?
                        ' class="'.$this->_cssClass.'"' : ''
                    ).
                    (isset($this->_maxValueLength) ?
                        ' maxlength="'.$this->_maxValueLength.'"' : ''
                    ).
                    // $this->getEventsSource().
                    // 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
                    ($this->_readOnly ? " readonly" : "").
                    ($this->_disabled ? " disabled" : "").
					//' onfocus="this.blur();"'.
					' onMouseOver="style.cursor=\'pointer\';"'.
					' onclick="javascript:ouvrir_fenetre(\''.$niveau0.'php/palette_couleurs_2.php?form_name='.$this->_formName.'&field_name='.$this->_name.'_btn&hidden_field_name='.$this->_name.'\',\'Palette\',\'no\',\'no\',304,100);"'.
                ' /><input type="hidden" name="'.$this->_name.'" value="'.$this->_value.'">'
            );
        }


        function setValue($value)
        {
            // $value == false when the element value is not sent to the server
            // (this can happen when an element is added to the form only after
            // it is submitted) in this case the value set in the class constructor
            // is used
            if ($value !== false)
                $this->_value = trim($value);
        }
    }

?>

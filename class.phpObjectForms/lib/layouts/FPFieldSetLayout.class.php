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
    /**
     * Creation d'un layout FieldSet
     */

    class FPFieldSetLayout extends FPLayout {

        var $_tblPadding = 2;
        var $_tblSpacing = 0;
        var $_tblAlign;
        var $_tblWidth;
        var $_tblHeight;
        var $_elemAlign;
		var $_legends;
		var $_cssStyles;

        function FPFieldSetLayout($params = array())
        {
            FPLayout::FPLayout($params);
            if (isset($params["table_padding"])) $this->_tblPadding = $params["table_padding"];
            if (isset($params["table_spacing"])) $this->_tblSpacing = $params["table_spacing"];
            if (isset($params["table_align"])) $this->_tblAlign = $params["table_align"];
            if (isset($params["table_width"])) $this->_tblWidth = $params["table_width"];
            if (isset($params["table_height"])) $this->_tblHeight = $params["table_height"];
            if (isset($params["element_align"])) $this->_elemAlign = $params["element_align"];
            if (isset($params["legends"])) $this->_legends = $params["legends"];
            if (isset($params["css_styles"])) $this->_cssStyles = $params["css_styles"];
        }

        function echoSource()
        {
            for ($i=0; $i<$this->_elementsNum; $i++)
            {
                $this->_append(
                    '<fieldset'.
                        ($this->_cssStyles ? ' style="'.$this->_cssStyles[$i].'"' : '')
					.'>'.
                        ($this->_legends ? '<legend>'.$this->_legends[$i].'</legend>' : '')
                    ."\n"
                );
                $this->_append($this->_elements[$i]->display());
                $this->_append('</fieldset>'."\n");
            }
        }
    }

?>

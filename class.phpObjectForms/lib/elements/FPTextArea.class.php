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
     * @author Ilya Boyandin <ilyabo@gmx.net>
     */
	/*
		- maj 31 08 2006 : ajout du paramètre readonly.
	*/

    class FPTextArea extends FPElement {

        var $_rows = 5;
        var $_cols = 30;

        function FPTextArea($params)
        {
            FPElement::FPElement($params);
            if (isset($params["rows"])) $this->_rows = $params["rows"];
            if (isset($params["cols"])) $this->_cols = $params["cols"];
			if (isset($params["readonly"])) $this->_readonly = $params["readonly"];
        }


        function echoSource()
        {
            $this->_append(
                '<textarea '.
                    ' name="'.$this->_name.'"'.
                    ' rows="'.$this->_rows.'"'.
                    ' cols="'.$this->_cols.'"'.
                    (isset($this->_cssStyle) ?
                        ' style="'.$this->_cssStyle.'"' : ''
                    ).
					(isset($this->_readonly) ?
                        $this->_readonly : ''
                    ).
                '>'
                   . htmlspecialchars($this->_value).
                '</textarea>'
            );
        }


        function setValue($value)
        {
            $this->_value =
                //htmlspecialchars(
                    //stripslashes(
                        $value
                    //)
                //)
            ;
        }
    }


?>

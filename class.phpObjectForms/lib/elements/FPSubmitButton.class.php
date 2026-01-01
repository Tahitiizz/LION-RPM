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

    class FPSubmitButton extends FPButton {

        function FPSubmitButton($params)
        {
            FPButton::FPButton($params);
            $this->_submit = true;
        }

    }

?>

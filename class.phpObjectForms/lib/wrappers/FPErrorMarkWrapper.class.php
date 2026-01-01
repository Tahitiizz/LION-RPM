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
     * $Id: FPErrorMarkWrapper.class.php 23852 2009-01-13 16:28:57Z b.berteaux $
     */

    class FPErrorMarkWrapper extends FPWrapper {

        var $_tblPadding = 0;
        var $_tblSpacing = 0;

        function FPLeftTitleWrapper($params = array())
        {
            FPWrapper::FPWrapper($params);
            if (isset($params["table_padding"])) $this->_tblPadding = $params["table_padding"];
            if (isset($params["table_spacing"])) $this->_tblSpacing = $params["table_spacing"];
        }

        function display(&$element)
        {
            if (isInstanceOf($element, "FPElement")  &&
                $errMsg = $element->getErrorMsg())
            {
                $elto =& $element->getOwner();
                $cssPrefix = $elto->getCssClassPrefix();

                $element->_append(
                    '<table '.
                        ' cellpadding="'.$this->_tblPadding.'"'.
                        ' cellspacing="'.$this->_tblSpacing.'"'.
                        ' border="0"'.
                        ' title="'.addslashes($errMsg).'"'.
                    '>'."\n".
                        '<td>'."\n"
                );
                $element->echoSource();
                $element->_append(
                        '</td>'."\n".
                        '<td>'."\n".
                            '<span class="'.$cssPrefix.'Error">'.
                                "*".
                            '</span>'.
                        '</td>'."\n".
                    '</table>');
            } else
                $element->echoSource();
        }

    }

?>

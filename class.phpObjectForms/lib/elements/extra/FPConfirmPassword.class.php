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

    class FPConfirmPassword extends FPPassword {

        var $_confirmObj;

        function FPConfirmPassword($params)
        {
            FPPassword::FPPassword($params);

            // required params
            $this->_confirmObj = &$params["confirm_object"];
        }


        function validate()
        {
            FPPassword::validate();
            if ($this->_errCode != FP_SUCCESS)
                return false;
            else {
                if ($this->_confirmObj->getValue() != $this->_value)
                {
                    $this->_errCode = FP_ERR_CODE__CUSTOM_ERROR;
                    $this->_customErrMsg = str_replace(
                        array('[element_title]', '[confirm_element_title]'),
                        array($this->_confirmObj->getTitle(), $this->getTitle()),
                        $GLOBALS["FP_ERR_MSG"][FP_ERR_CODE__CONFIRM__VALUES_DOESNT_MATCH]
                    );
                    return false;
                }
            }
            return true;
        }
    }

?>

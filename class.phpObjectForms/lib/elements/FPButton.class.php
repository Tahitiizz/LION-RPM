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

    class FPButton extends FPElement {

        var $_caption;
        var $_submit;
        var $_onClick;
		var $_form_name;
		var $_enable_js_validation;

        function FPButton($params)
        {
            parent::FPElement($params);
			
			if(isset($params["enable_js_validation"]))
				$this->_enable_js_validation = $params["enable_js_validation"];
			if(isset($params["form_name"]))
				$this->_form_name = $params["form_name"];
            if (isset($params["size"]))
                $this->_size = $params["size"];
            $this->_caption = isset($params["caption"]) ? $params["caption"] : $this->_title;
            if (isset($params["on_click"]))
                $this->_onClick = $params["on_click"];
            if (isset($params["submit"]))
                $this->_submit = $params["submit"] ? true : false;
        }

        function echoSource()
        {
            // JavaScript validation support
            
			$form = &$this->getParentFormObj();
            $jsEnabled = $this->_enable_js_validation;
            if ($jsEnabled) {
                $formName = $this->_form_name;
			    // $formElements = $form->getInnerElements();
                $funcName = "_fp_validate".ucfirst($formName);

                // $this->_append(
                // '<script language="JavaScript" type="text/javascript">'."\n".
                // '<!--'."\n".
                // 'function '.$funcName.'Element(re, elt, title, isRequired) {'."\n".
                    // 'if (isRequired && elt.value == "") {'."\n".
                        // 'alert("'.
                                // str_replace(
                                // "[element_title]", '" + title + "',
                                // addslashes(
                                    // $GLOBALS["FP_ERR_MSG"]
                                            // [FP_ERR_CODE__JS_REQ_FIELD_IS_EMPTY]
                                // )).
                        // '");'."\n".
                        // "elt.focus();\n".
                        // 'return false;'."\n".
                    // '}'."\n".
                    // 'if (elt.value != "" && !re.test(elt.value)) {'."\n".
                        // 'alert("'.
                                // str_replace(
                                // "[element_title]", '" + title + "',
                                // addslashes(
                                    // $GLOBALS["FP_ERR_MSG"]
                                            // [FP_ERR_CODE__JS_FIELD_IS_INVALID]
                                // )).
                        // '");'."\n".
                        // "elt.focus();\n".
                        // 'return false;'."\n".
                    // '} else return true;'."\n".
                // '}'."\n".

                // 'function '.$funcName.'() {'."\n".
                    // 'var els = document.forms["'.$formName.'"].elements;'."\n".
                    // 'return '
                // );
				
                // foreach ($formElements as $element)
                    // if ((isInstanceOf($element, "FPTextField") || isInstanceOf($element, "FPPassword"))
                            // && $element->getName())
                        // $this->_append(
                            // $funcName."Element(".
                                // $element->getValidRE().','.
                                // 'els["'.$element->getName().'"]'.','.
                                // '"'.strip_tags($element->getTitle()).'",'.
                                // ($element->isRequired() ? 'true' : 'false').
                            // ")\n&& "
                        // );
                // $this->_append(
                    // "true;"."\n"
                // );
                // $this->_append(
                // '}'."\n".'//-->'."\n".
                // '</script>'
                // );

                $onClick = "if (!$funcName()) return false;".
                    ($this->_onClick ? "else {".$this->_onClick."}" : "");
            } else
                $onClick = $this->_onClick;

            $this->_append(
                '<input type="'.($this->_submit ? "submit" : "button").'"'.
                    ' name="'.$this->_name.'"'.
                    ' value="'.$this->_caption.'"'.
                    (isset($this->_cssStyle) ?
                        ' style="'.$this->_cssStyle.'"' : ''
                    ).
                    (isset($this->_tabIndex) ?
                        ' tabindex="'.$this->_tabIndex.'"' : ''
                    ).
                    $this->getEventsSource().
                    ($this->_disabled ? " disabled" : "").
                    ($onClick ? ' onclick="'.$onClick.'"' : "").
                    (isset($this->_cssClass) ?
                        ' class="'.$this->_cssClass.'"' : ' class="'.$this->_owner->getCssClassPrefix()
                    ).
                    ($this->_submit ? "Submit" : "").'Button"'.
                '>'."\n"
            );
        }

        function validate() {
            $this->_errCode = FP_SUCCESS;
            return true;
        }

    }

?>

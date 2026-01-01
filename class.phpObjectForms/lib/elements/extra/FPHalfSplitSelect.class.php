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
	
    class FPHalfSplitSelect extends FPElement {

        var $_size;
        var $_isMultiple = false;
        var $_options = array();

        var $_leftIDs;
        var $_rightIDs;

        var $_leftTitle;
        var $_rightTitle;
		var $_form_name;

        var $_tblPadding = 2;
        var $_tblSpacing = 2;

        function FPHalfSplitSelect($params)
        {
            FPElement::FPElement($params);
            if (isset($params["multiple"]))
                $this->_isMultiple = $params["multiple"] ? true : false
            ;
			
			if (isset($params["form_name"]))
				$this->_form_name = $params["form_name"];
            if (isset($params["size"]))
                $this->_size = $params["size"];
            /*if (!isset($this->_size))
                $this->_size = $this->_isMultiple ? 5 : 1
            ;*/

            if (isset($params["options"]))  $this->_options = $params["options"];

            if (isset($params["left_ids"])) {
                $this->_leftIDs = $params["left_ids"];
                if (!isset($params["right_ids"])) {
                    $this->_rightIDs = array();
                    foreach ($this->_options as $key => $val) {
                        if (!in_array($key, $this->_leftIDs))
                            $this->_rightIDs[] = $key;
                    }
                }
            }
            if (isset($params["right_ids"])) {
                $this->_rightIDs = $params["right_ids"];
                if (!isset($params["left_ids"])) {
                    $this->_leftIDs = array();
                    foreach ($this->_options as $key => $val) {
                        if (!in_array($key, $this->_rightIDs))
                            $this->_leftIDs[] = $key;
                    }
                }
            }

            if (isset($params["left_title"]))
                $this->_leftTitle = $params["left_title"];
            if (isset($params["right_title"]))
                $this->_rightTitle = $params["right_title"];

            if (isset($params["min_right_options_selection"]))
                $this->_minRightOptsSelection = $params["min_right_options_selection"]
            ;
            if (isset($params["max_right_options_selection"]))
                $this->_maxRightOptsSelection = $params["max_right_options_selection"]
            ;
            if (isset($params["exact_right_options_selection"]))
                $this->_exactRightOptsSelection = $params["exact_right_options_selection"]
            ;

            /*
            $this->_required =
                ($this->_minRightOptsSelection > 0  ||
                $this->_exactRightOptsSelection > 0)
            ;*/

//            $this->_title = $this->_rightTitle;
//            $this->_required = true;

            if (isset($params["table_padding"])) $this->_tblPadding = $params["table_padding"];
            if (isset($params["table_spacing"])) $this->_tblSpacing = $params["table_spacing"];
        }


        function _packValues($vals) {
            return implode("||", $vals);
        }

        function _unpackValues($packedVals) {
            return ($packedVals != "" ? explode("||", $packedVals) : array());
        }


        function setValue($semicolonSeparatedRightIDs)
        {
            $this->_rightIDs = $this->_unpackValues($semicolonSeparatedRightIDs);

            $this->_leftIDs = array();
            foreach ($this->_options as $key => $val) {
                if (!in_array($key, $this->_rightIDs))
                    $this->_leftIDs[] = $key;
            }
        }


        function getValue()
        {
            return $this->_rightIDs; //$this->_packValues($this->_rightIDs);
        }


        function validate()
        {
            return true;

            $cnt = 0;
            for ($i=0; $i<count($this->_rightIDs); $i++)
            {
                if (!isset($this->_options[$this->_rightIDs[$i]]))
                {
                    $this->_errCode = FP_ERR_CODE__INVALID_USER_DATA;
                    return false;
                }
                $cnt++;
            }

            if (isset($this->_minRightOptsSelection)  &&  $cnt < $this->_minRightOptsSelection)
            {
                $this->_errCode = FP_ERR_CODE__TOO_FEW_OPTS_SELECTED;
                return false;
            }

            if (isset($this->_maxRightOptsSelection)  &&  $cnt > $this->_maxRightOptsSelection)
            {
                $this->_errCode = FP_ERR_CODE__TOO_MANY_OPTS_SELECTED;
                return false;
            }

            if (isset($this->_exactRightOptsSelection)  &&  $cnt != $this->_exactRightOptsSelection)
            {
                $this->_errCode =
                    $cnt < $this->_exactOptsSelection ?
                        FP_ERR_CODE__TOO_FEW_OPTS_SELECTED :
                        FP_ERR_CODE__TOO_MANY_OPTS_SELECTED
                ;
                return false;
            }

            return true;
        }


        function echoSource()
        {	/* 18/03/2009 - modif SPS : chgt du lien de l'image */
            $cssPrefix = $this->_owner->getCssClassPrefix();
            $this->_append(
                '<input type="hidden" name="'.$this->_name.'"'.
                    ' value="'.$this->_packValues($this->_rightIDs).'">'."\n".
				'<table cellpadding=2 cellspacing=2><tr><td><fieldset>'.
				(isset($this->_title) ?
                    '<legend class=texteGrisBold>&nbsp;<img src="'.NIVEAU_0.'/images/icones/small_puce_fieldset.gif">&nbsp;'.
					$this->_title.
					'&nbsp;</legend>'
                 : ""
				).
                '<table'.
                    ' cellpadding="'.$this->_tblPadding.'"'.
                    ' cellspacing="'.$this->_tblSpacing.'"'.
                    ' border="0"'.
                '>'."\n".
                '<tr>'.
                '<td>'.
                    '<span class="'.$cssPrefix.'ReqTitle"><u>'.
                    $this->_leftTitle.
                    '</u></span>'.
                '</td>'."\n".
                '<td>&nbsp;</td>'.
                '<td>'.
                    '<span class="'.$cssPrefix.'ReqTitle"><u>'.
                    $this->_rightTitle.
                    '</u></span>'.
                '</td>'.
                '</tr>'."\n".

                '<tr>'
            );
            // left select box
            $this->_append(
                '<td valign=top>'."\n".
                '<font class=texteGris>Name :</font><br /> <input type="text"'.
                    ' name="'.$this->_name.'_leftBox_name"'.
                    ' size="'.$this->_size.'"'.
                    (isset($this->_cssStyle) ?
                        ' style="'.$this->_cssStyle.'"' : ''
                    ).
                '>'."\n".
				'<br /><font class=texteGris>Email :</font><br /> <input type="text"'.
                    ' name="'.$this->_name.'_leftBox_email"'.
                    ' size="'.$this->_size.'"'.
                    (isset($this->_cssStyle) ?
                        ' style="'.$this->_cssStyle.'"' : ''
                    ).
                '>'
            );


            // buttons
            $this->_append(
                '<td>'.
                    '<input type="button" value=" -&gt; "'.
                        ' onclick="'.$this->_name.'_halfsplitSelectLeftToRight()"'.
                        ' class="'.$cssPrefix.'Button">'.
                    '<br>'.
                    '<input type="button" value=" &lt;- "'.
                        ' onclick="'.$this->_name.'_halfsplitSelectRightToLeft()"'.
                        ' class="'.$cssPrefix.'Button">'.
                '</td>'
            );

            // right select box
            $this->_append(
                '<td>'."\n".
                '<select'.
                    ' name="'.$this->_name.'_rightBox"'.
                    ' size="'.$this->_size.'"'.
                    // ($this->_isMultiple ? ' multiple="multiple"' : '').
					// on ne peut pas avoir de multiple dans ce cas, parce qu'on ne pourra pas éditer plusieurs email en même temps
                    (isset($this->_cssStyle) ?
                        ' style="'.$this->_cssStyle.'"' : ''
                    ).
                '>'."\n"
            );
            foreach ($this->_rightIDs as $key)
                $this->_append(
                    '<option value="'.$key.'">'.str_replace('<','&lt;',$key).'</option>'."\n"
                );
            $this->_append(
                '</select>'."\n".'</td>'."\n".
                '</tr>'.
                ($this->getErrorMsg() ?
                '<tr>'."\n".
                    '<td colspan="3"'.
                        (isset($this->_tblFieldCellWidth) ?
                            ' width="'.$this->_tblFieldCellWidth.'"' : ''
                        ).
                    '>'."\n".
                        $this->getErrorSource()."\n".
                    '</td>'."\n".
                '</tr>'."\n"
                :
                    ''
                )."\n".
                ($this->_comment ?
                '<tr>'."\n".
                    '<td>&nbsp;</td>'.
                    '<td'.
                        (isset($this->_tblFieldCellWidth) ?
                            ' width="'.$this->_tblFieldCellWidth.'"' : ''
                        ).
                    '>'."\n".
                        $this->getCommentSource()."\n".
                    '</td>'."\n".
                '</tr>'."\n"
                :
                    ''
                ).
                '</table></fieldset></td></tr></table>'
            );

            // getting the parent form name
            // $parentForm = $this->_form_namegetParentFormObj();
            $formName = $this->_form_name;

            $nam = $this->_name;

            $this->_append(
<<<EOC

            <script language="JavaScript">
            <!--
            var ${nam}_fp = document.forms['${formName}'];
            var ${nam}_leftName = ${nam}_fp.elements['${nam}_leftBox_name'];
            var ${nam}_leftEmail = ${nam}_fp.elements['${nam}_leftBox_email'];
            var ${nam}_rightOpts = ${nam}_fp.elements['${nam}_rightBox'].options;
            var ${nam}_valueElt = ${nam}_fp.elements['${nam}'];

            function ${nam}_updateValueElt() {
                var packedRightIDs = '';
                for (var i=0; i<${nam}_rightOpts.length; i++) {
                    packedRightIDs += ${nam}_rightOpts[i].value +
                        (i < ${nam}_rightOpts.length - 1 ? "||" : "");
                }
                ${nam}_valueElt.value = packedRightIDs;
            }

            function ${nam}_halfsplitSelectRightToLeft() {
				a = ${nam}_rightOpts
				Name = '';
				Email = '';
                for (var i=0; i<a.length; i++) {
                    if (a[i].selected) {
						NameEmail = a[i].text;
						Name = NameEmail.substring(0,NameEmail.indexOf("<")-1);
						Email = NameEmail.substring(NameEmail.indexOf("<")+1,NameEmail.indexOf(">"));
                        ${nam}_leftName.value = Name;
                        ${nam}_leftEmail.value = Email;
                        a[i] = null;
						i--;
                    }
                }
                ${nam}_updateValueElt();
            }

            function ${nam}_halfsplitSelectLeftToRight() {
            	var moveToRight = ${nam}_leftEmail.value!="";//move to right only if email isn't empty
            	NameEmail = ${nam}_leftName.value + ' <' + ${nam}_leftEmail.value + '>';			
				for (var i=0; i<${nam}_rightOpts.length; i++) {
					moveToRight = moveToRight && ${nam}_rightOpts[i].value!=NameEmail;//move to right only if doesn't already exists
				}
				if(moveToRight){
	                ${nam}_rightOpts[${nam}_rightOpts.length] = new Option(NameEmail,NameEmail, false, true);
	                ${nam}_updateValueElt();
	            }
            }

            function ${nam}_splitSelectAToB(a, b) {
                for (var i=0; i<a.length; i++) {
                    if (a[i].selected) {
                        b[b.length] = new Option(
                            a[i].text, a[i].value, false, true
                        );
                        a[i] = null;
						i--;
                    }
                }
            }

            function ${nam}_splitSelectOnChangeLeft() {
                ${nam}_rightOpts.selectedIndex = -1;
            }


            // -->
            </script>
EOC
);
        }

    }

?>

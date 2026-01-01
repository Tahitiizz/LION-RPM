<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
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
<?php
    /**
     * @author Ilya Boyandin <ilyabo@gmx.net>
     */

    class FPSplitSelect extends FPElement {

        var $_size;
        var $_isMultiple = false;
        var $_options = array();
        var $_warnIDs = array();

        var $_leftIDs;
        var $_rightIDs;

        var $_leftTitle;
        var $_rightTitle;
		var $_form_name;

        var $_tblPadding = 2;
        var $_tblSpacing = 2;

        function FPSplitSelect($params)
        {
            parent::FPElement($params);
            if (isset($params["multiple"]))
                $this->_isMultiple = $params["multiple"] ? true : false
            ;

            if (isset($params["size"]))
                $this->_size = $params["size"];
            /*if (!isset($this->_size))
                $this->_size = $this->_isMultiple ? 5 : 1
            ;*/

            if (isset($params["options"]))  $this->_options = $params["options"];

            // 26/07/2011 OJT : DE SMS, gestion de la liste de warning
            if( isset($params["warning_ids"])){
                $this->_warnIDs = $params["warning_ids"];
            }
            if (isset($params["left_ids"])) {
                $this->_leftIDs = $params["left_ids"];
                if (!isset($params["right_ids"])) {
                    $this->_rightIDs = array();
                    foreach ($this->_options as $key => $val){
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
			if (isset($params["form_name"]))
                $this->_form_name = $params["form_name"]
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
        { 	/* 07/04/2009 - modif SPS : chgt du lien de l'image */
			$cssPrefix = $this->_owner->getCssClassPrefix();
            $this->_append(
                '<input type="hidden" name="'.$this->_name.'"'.
                    ' value="'.$this->_packValues($this->_rightIDs).'">'."\n".
                '<table cellpadding=2 cellspacing=2><tr><td><fieldset>'.
				(isset($this->_title) ?
                    '<legend class=texteGrisBold>&nbsp;<img src="'.NIVEAU_0.'images/icones/small_puce_fieldset.gif">&nbsp;'.
					$this->_title.
					'</legend>'
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
                '<td>'."\n".
                '<select'.
                    ' name="'.$this->_name.'_leftBox"'.
                    ' size="'.$this->_size.'"'.
                    ($this->_isMultiple ? ' multiple="multiple"' : '').
                    (isset($this->_cssStyle) ?
                        ' style="'.$this->_cssStyle.'"' : ''
                    ).
                    ' onchange="'.$this->_name.'_splitSelectOnChangeLeft()"'.
                '>'."\n"
            );

            // 26/07/2011 OJT : DE SMS, gestion du warning orange
            foreach ($this->_leftIDs as $key)
            {
                $style = "";
                if( in_array( $key, $this->_warnIDs ) )
                {
                    $style = "style='color:orange;'";
                }
                $this->_append( '<option '.$style.' value="'.$key.'">'.$this->_options[$key].'</option>'."\n" );

            }
            $this->_append(
                '</select>'."\n".'</td>'."\n"
            );

            // buttons
            $this->_append(
                '<td>'.
                    '<input type="button" value=" -&gt; "'.
                        ' onclick="'.$this->_name.'_splitSelectLeftToRight()"'.
                        ' class="'.$cssPrefix.'Button">'.
                    '<br>'.
                    '<input type="button" value=" &lt;- "'.
                        ' onclick="'.$this->_name.'_splitSelectRightToLeft()"'.
                        ' class="'.$cssPrefix.'Button">'.
                '</td>'
            );

            // right select box
            $this->_append(
                '<td>'."\n".
                '<select'.
                    ' name="'.$this->_name.'_rightBox"'.
                    ' size="'.$this->_size.'"'.
                    ($this->_isMultiple ? ' multiple="multiple"' : '').
                    (isset($this->_cssStyle) ?
                        ' style="'.$this->_cssStyle.'"' : ''
                    ).
                    ' onchange="'.$this->_name.'_splitSelectOnChangeRight()"'.
                '>'."\n"
            );

            // 26/07/2011 OJT : DE SMS, gestion du warning orange
            foreach ($this->_rightIDs as $key)
            {
                $style = "";
                if( in_array( $key, $this->_warnIDs ) )
                {
                    $style = "style='color:orange;'";
                }
                $this->_append( '<option '.$style.' value="'.$key.'">'.$this->_options[$key].'</option>'."\n" );
            }
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
            // $parentForm = $this->getParentFormObj();
			// $formName = $parentForm->getName();

			// mp 09/10/2007 - migration php4-php5 / getting the form name
            $formName = $this->_form_name;
			$nam = $this->_name;
            $this->_append(
<<<EOC

            <script language="JavaScript">
            <!--
            var ${nam}_fp = document.forms['${formName}'];
            var ${nam}_leftOpts = ${nam}_fp.elements['${nam}_leftBox'].options;
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

            function ${nam}_splitSelectRightToLeft() {
                ${nam}_splitSelectAToB(${nam}_rightOpts, ${nam}_leftOpts);
                ${nam}_updateValueElt();
            }

            function ${nam}_splitSelectLeftToRight() {
                ${nam}_splitSelectAToB(${nam}_leftOpts, ${nam}_rightOpts);
                ${nam}_updateValueElt();
            }

            function ${nam}_splitSelectAToB(a, b) {
                for (var i=0; i<a.length; i++) {
                    if (a[i].selected) {
                        b[b.length] = new Option(a[i].text, a[i].value, false, true);
                        b[b.length-1].style.color = a[i].style.color;
                        a[i] = null;
						i--;
                    }
                }
            }

            function ${nam}_splitSelectOnChangeLeft() {
                ${nam}_rightOpts.selectedIndex = -1;
            }

            function ${nam}_splitSelectOnChangeRight() {
                ${nam}_leftOpts.selectedIndex = -1;
            }
            // -->
            </script>
EOC
);
        }

    }

?>

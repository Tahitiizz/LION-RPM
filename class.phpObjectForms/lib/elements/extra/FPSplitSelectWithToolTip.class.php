<?
/*
 * @cb516@
 * 
 *	13/12/2011 ACS BZ 24853 Add the parameters "forbidLeftToRight" and "forbidRightToLeft"
 * 
 * 
 * @cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	maj 13/08/2007 - Jérémy : Ajout d'un paramètre pour cacher les lien (modify) pour le double clique
*
*	09/04/2008 : modif SPS : 
*					- ajout des methodes show et hide pour l'element 'ouvrir tooltip'   
*					- url de la popup de tooltip en dur (erreur ie6 et ie8)
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
<?
/*
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?>
<?php
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php
/**
 *
 * @author Ilya Boyandin <ilyabo@gmx.net>
 */

/*

	- maj 06/11/2006, benoit : prise en compte du parametre "dbl_click" dans le constructeur de la classe. Ce        parametre permet de determiner si les elements d'un select peuvent être double clickés ou non

*/

class FPSplitSelectWithToolTip extends FPElement {
    var $_size;
    var $_isMultiple = false;
    var $_options = array();
    var $_option_tips = array();
	var $_form_name;
    var $_leftIDs;
    var $_rightIDs;
	var $_forbidLeftToRight;
	var $_forbidRightToLeft;
	
    var $_leftTitle;
    var $_rightTitle;

    var $_tblPadding = 2;
    var $_tblSpacing = 2;

    function FPSplitSelectWithToolTip($params)
    {
        parent::FPElement($params);
        if (isset($params["multiple"]))
            $this->_isMultiple = $params["multiple"] ? true : false ;

        if (isset($params["size"]))
            $this->_size = $params["size"];
        /*if (!isset($this->_size))
                $this->_size = $this->_isMultiple ? 5 : 1
            ;*/
		

        if (isset($params["options"])) $this->_options = $params["options"];
        if (isset($params["option_tips"])) $this->_option_tips = $params["option_tips"];
		if (isset( $params["form_name"]) ) $this->_form_name = $params["form_name"];
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
            $this->_minRightOptsSelection = $params["min_right_options_selection"] ;
        if (isset($params["max_right_options_selection"]))
            $this->_maxRightOptsSelection = $params["max_right_options_selection"] ;
        if (isset($params["exact_right_options_selection"]))
            $this->_exactRightOptsSelection = $params["exact_right_options_selection"] ;

        /*
            $this->_required =
                ($this->_minRightOptsSelection > 0  ||
                $this->_exactRightOptsSelection > 0)
            ;*/
        // $this->_title = $this->_rightTitle;
        // $this->_required = true;
        if (isset($params["table_padding"])) $this->_tblPadding = $params["table_padding"];
        if (isset($params["table_spacing"])) $this->_tblSpacing = $params["table_spacing"];
		// 06/11/2006 - Modif. benoit : prise en compte du parametre "dbl_click" qui permet de determiner si les elements d'un select peuvent être double clickés ou non
		// 13/08/2007 - Ajout du paramètre _show_modify_link
		if ((isset($params["dbl_click"])) && ($params["dbl_click"] == true)) {
			$this->_dbl_click = true;
			if ((isset($params["show_modify_link"])) && ($params["show_modify_link"] == true)) {
				$this->_show_modify_link = true;
			} else {
				$this->_show_modify_link = false;
			}
		}
		else
		{
			$this->_dbl_click = false;
			$this->_show_modify_link = false;
		}

		// 13/12/2011 ACS BZ 24853 Add the parameters "forbidLeftToRight" and "forbidRightToLeft"
        if (isset($params["forbidLeftToRight"])) {
            $this->_forbidLeftToRight = $params["forbidLeftToRight"] ? true : false ;
		}
        if (isset($params["forbidRightToLeft"])) {
            $this->_forbidRightToLeft = $params["forbidRightToLeft"] ? true : false ;
		}
    }

    function _packValues($vals)
    {
        return implode("||", $vals);
    }

    function _unpackValues($packedVals)
    {
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
        for ($i = 0; $i < count($this->_rightIDs); $i++) {
            if (!isset($this->_options[$this->_rightIDs[$i]])) {
                $this->_errCode = FP_ERR_CODE__INVALID_USER_DATA;
                return false;
            }
            $cnt++;
        }

        if (isset($this->_minRightOptsSelection) && $cnt < $this->_minRightOptsSelection) {
            $this->_errCode = FP_ERR_CODE__TOO_FEW_OPTS_SELECTED;
            return false;
        }

        if (isset($this->_maxRightOptsSelection) && $cnt > $this->_maxRightOptsSelection) {
            $this->_errCode = FP_ERR_CODE__TOO_MANY_OPTS_SELECTED;
            return false;
        }

        if (isset($this->_exactRightOptsSelection) && $cnt != $this->_exactRightOptsSelection) {
            $this->_errCode = $cnt < $this->_exactOptsSelection ?
            FP_ERR_CODE__TOO_FEW_OPTS_SELECTED :
            FP_ERR_CODE__TOO_MANY_OPTS_SELECTED ;
            return false;
        }

        return true;
    }

    function echoSource()
    {
        $cssPrefix = $this->_owner->getCssClassPrefix();
        $this->_append('<input type="hidden" name="' . $this->_name . '"' . ' value="' . $this->_packValues($this->_rightIDs) . '">' . "\n" . '<table' . ' cellpadding="' . $this->_tblPadding . '"' . ' cellspacing="' . $this->_tblSpacing . '"' . ' border="0"' . '>' . "\n" .
            (isset($this->_title) ?
                '<tr><td colspan="3" align="center">' . '<span class="' . $cssPrefix . 'ReqTitle">' . $this->_title . '</span>' . '</td></tr>' . "\n"
                : ""
                ) . '<tr>' . '<td>' . '<span class="' . $cssPrefix . 'ReqTitle">' . $this->_leftTitle . '</span>' . '</td>' . "\n" . '<td>&nbsp;</td>' . '<td>' . '<span class="' . $cssPrefix . 'ReqTitle">' . $this->_rightTitle . '</span>' . '</td>' . '</tr>' . "\n" . '<tr>'
            );
        // left select box

		// 06/11/2006 - Modif. benoit : on ajoute l'action double click sur les options du select si cette action est autorisée

		$dbl_action = "";

		if ($this->_dbl_click) {
			// 13/08/2007 - Jérémy On passe le booléen dans l'appel de la fonction JS
			if ($this->_show_modify_link) { $link = "true"; } else { $link = "false"; }
			$dbl_action = 'ondblclick="'.$this->_name.'_showtip(this.options[this.selectedIndex].value,'.$link.')"';
		}

        $this->_append('<td>' . "\n" . '<select '.$dbl_action.' name="' . $this->_name . '_leftBox"' . ' size="' . $this->_size . '"' .
            ($this->_isMultiple ? ' multiple="multiple"' : '') .
            (isset($this->_cssStyle) ?
                ' style="' . $this->_cssStyle . '"' : ''
                ) . ' onchange="' . $this->_name . '_splitSelectOnChangeLeft()"' . '>' . "\n"
            );

        foreach ($this->_leftIDs as $key)
        $this->_append('<option' . ' value="' . $key . '"' . '>' . $this->_options[$key] . '</option>' . "\n"
            );
        $this->_append('</select>' . "\n" . '</td>' . "\n"
            );
        // buttons
        // 13/12/2011 ACS BZ 24853 Add the parameters "forbidLeftToRight" and "forbidRightToLeft"
        $this->_append('<td>');
		if (!$this->_forbidLeftToRight) {
			$this->_append('<input type="button" value=" -&gt; "' . ' onclick="' . $this->_name . '_splitSelectLeftToRight()"' . ' class="' . $cssPrefix . 'Button">');
		}
		if (!$this->_forbidLeftToRight && !$this->_forbidRightToLeft) {
			$this->_append('<br />');
		}
		if (!$this->_forbidRightToLeft) {
			$this->_append('<input type="button" value=" &lt;- "' . ' onclick="' . $this->_name . '_splitSelectRightToLeft()"' . ' class="' . $cssPrefix . 'Button">' . '</td>');
		}
        // right select box
        $this->_append('<td>' . "\n" . '<select '.$dbl_action.' name="' . $this->_name . '_rightBox"' . ' size="' . $this->_size . '"' .
            ($this->_isMultiple ? ' multiple="multiple"' : '') .
            (isset($this->_cssStyle) ?
                ' style="' . $this->_cssStyle . '"' : ''
                ) . ' onchange="' . $this->_name . '_splitSelectOnChangeRight()"' . '>' . "\n"
            );
        foreach ($this->_rightIDs as $key)
        $this->_append('<option' . ' value="' . $key . '"' . '>' . $this->_options[$key] . '</option>' . "\n");

		// 06/11/2006 - Modif. benoit : on ajoute le calque d'informations sur le double click si cette action est définie pour les options du select

		$div_comment = "&nbsp;";

		if ($this->_dbl_click) {
			$div_comment = '<div id="'. $this->_name . '_tooltip" style="FONT: 10px Arial;COLOR:#000000;PADDING: 0px;border:1px solid #000000;BACKGROUND-COLOR:#ffffbb;text-align:left;padding-left:5px;">Double-click on item to get info.</div>';
		}

        $this->_append('</select>' . "\n" . '</td>' . "\n" . '</tr>' .
            ($this->getErrorMsg() ?
                '<tr>' . "\n" . '<td colspan="3"' .
                (isset($this->_tblFieldCellWidth) ?
                    ' width="' . $this->_tblFieldCellWidth . '"' : ''
                    ) . '>' . "\n" . $this->getErrorSource() . "\n" . '</td>' . "\n" . '</tr>' . "\n"
                :
                ''
                ) . "\n" .
            ($this->_comment ?
                '<tr>' . "\n" . '<td>&nbsp;</td>' . '<td' .
                (isset($this->_tblFieldCellWidth) ?
                    ' width="' . $this->_tblFieldCellWidth . '"' : ''
                    ) . '>' . "\n" . $this->getCommentSource() . "\n" . '</td>' . "\n" . '</tr>' . "\n"
                :
                ''
                ) . '
				<tr><td colspan="3">'.$div_comment.'</td>
				<td>
				<span style=\'cursor:pointer\' class=\'texteGris\' onclick="ouvrir_tooltip()" id="ouvrir_tooltip" style=\'display:none\'>Modify</span>
				</td>
				</tr>

				</table>'
            );
      
		// maj 09/10/2007  -  getting the form name
        $formName = $this->_form_name;
        $nam = $this->_name;

        $this->_append('<script language="JavaScript">' . $this->_name . '_option_tips = new Array();');
        foreach ($this->_option_tips as $key => $val) {
            $this->_append($this->_name . '_option_tips[' . $key . '] = "' . $val . '";' . "\n");
        }
        $this->_append('</script>');
		
		/* 09/04/2008 : modif SPS : ajout des methodes show et hide pour l'element 'ouvrir tooltip'*/
        $this->_append(
            <<<EOC

            <script type="text/javascript">
            <!--
			var ${nam}_fp = document.forms['${formName}'];
            var ${nam}_leftOpts = ${nam}_fp.elements['${nam}_leftBox'].options;
            var ${nam}_rightOpts = ${nam}_fp.elements['${nam}_rightBox'].options;
            var ${nam}_valueElt = ${nam}_fp.elements['${nam}'];
			
			//on cache le lien pour modifier le compteur
			$('ouvrir_tooltip').hide();
			
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

            function ${nam}_splitSelectOnChangeRight() {
                ${nam}_leftOpts.selectedIndex = -1;
            }

			var element;
			function ${nam}_showtip(a,link) {
				msg = ${nam}_option_tips[a];
				if (msg != '') {
					document.getElementById('${nam}_tooltip').innerHTML = ${nam}_option_tips[a];
				} else {
					document.getElementById('${nam}_tooltip').innerHTML = 'No information on that item.';
				}
				element=a;
				if (link == true){
					//on affiche le lien pour modifier le compteur
					$('ouvrir_tooltip').show();
				}
			}

			function ouvrir_tooltip()
			{
				if (element){
					// 09/04/2009 - SPS : url de la popup de tooltip en dur (erreur ie6 et ie8)
					var url = 'mapping_raw_counters_label_comment_popup.php';
					ouvrir_fenetre(url+'?id='+element+'&product='+product,'new_window',0,0,410,290);
				}
			}

            // -->
            </script>
EOC
            );
    }
}

?>

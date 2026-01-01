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

    class FPGridLayout extends FPLayout {

        var $_tblPadding = 2;
        var $_tblSpacing = 0;
        var $_tblAlign;
        var $_tblVAlign;
        var $_tblWidth;
        var $_columns;
        var $_id;
		var $_tblStyle;

        function FPGridLayout($params = array())
        {
            FPLayout::FPLayout($params);
            if (isset($params["table_padding"])) $this->_tblPadding = $params["table_padding"];
            if (isset($params["table_spacing"])) $this->_tblSpacing = $params["table_spacing"];
            if (isset($params["table_align"])) $this->_tblAlign = $params["table_align"];
            if (isset($params["table_valign"])) $this->_tblVAlign = $params["table_valign"];
            if (isset($params["table_width"])) $this->_tblWidth = $params["table_width"];
            if (isset($params["columns"])) $this->_columns = $params["columns"];
            if (isset($params["id"])) $this->_id = $params["id"];
            if (isset($params["table_style"])) $this->_tblStyle = $params["table_style"];
        }

        function echoSource()
        {
            $this->_append(
                '<table'.
                    ' cellpadding="'.$this->_tblPadding.'"'.
                    ' cellspacing="'.$this->_tblSpacing.'"'.
                    (isset($this->_tblAlign) ?
                        ' align="'.$this->_tblAlign.'"' : ''
                    ).
                    (isset($this->_tblStyle) ?
                        ' style="'.$this->_tblStyle.'"' : ''
                    ).
                    (isset($this->_tblWidth) ?
                        ' width="'.$this->_tblWidth.'"' : ''
                    ).
                    (isset($this->_id) ?
                        ' id="'.$this->_id.'"' : ''
                    ).
                    ' border="0"'.
                '>'."\n"
            );
            for ($i=0; $i<$this->_elementsNum; $i++)
            {
                if ($i%$this->_columns == 0)
                    $this->_append('<tr'.
	                    (isset($this->_tblVAlign) ?
    	                    ' valign="'.$this->_tblVAlign.'"' : ''
        	            ).
						'>'."\n"
					);
                $this->_append('<td>'."\n");
                $this->_append($this->_elements[$i]->display());
                $this->_append('</td>'."\n");
                if (($i+1)%$this->_columns == 0  ||  $i == $this->_elementsNum - 1)
                    $this->_append('</tr>'."\n");
            }
            $this->_append('</table>'."\n");
        }
    }

?>

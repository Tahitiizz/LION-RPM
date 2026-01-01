<?php
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php
/*
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php
/*

	- maj 27/02/2007, benoit : ajout d'un parametre à la fonction 'getFieldValue()' indiquant le nombre de        caracteres autorisé dans les selects.

*/
?>
<?php
include_once($repertoire_physique_niveau0 . "php/deploy_and_compute_functions.php");

Class alarm_list {
  var $univers;

  function alarm_list($family,$alarm_id,$alarm_type,$table_alarm,$allow_modif)
  {

    $this->new_alarm=false;
    $this->database_connection=$this->connection();

    $this->allow_modif = $allow_modif;

    $this->alarm_type=$alarm_type;
    $this->alarm_id=$alarm_id;
    $this->family=$family;
    $this->table_alarm=$table_alarm;

    $this->get_kpi();
    $this->get_counter();
    $this->display();
  }

  function connection()
  {
    global $database_connection;
    return $database_connection;
  }


// we get the list of visible raw counters
function get_counter() {
	$query="
		SELECT distinct edw_field_name_label,edw_field_name
		FROM sys_field_reference a, sys_definition_group_table b
		WHERE b.edw_group_table=a.edw_group_table
			AND a.visible = 1 AND a.on_off=1
			AND b.family='$this->family'
      ORDER BY edw_field_name_label,edw_field_name";
	// echo "$query<br>";
	$resultat = pg_query($this->database_connection,$query);
	$nombre_connection = pg_num_rows($resultat);
	for ($i = 0;$i < $nombre_connection;$i++) {
		$row = pg_fetch_array($resultat, $i);
        $this->counter[]=$row["edw_field_name"];
        if ($row["edw_field_name_label"] != '')
            $this->counter_label[]=$row["edw_field_name_label"];
        else
            $this->counter_label[]=$row["edw_field_name"];
	}
}


// we get the list of visible kpi
function get_kpi() {
	$query="
		SELECT distinct kpi_label, kpi_name
		FROM sys_definition_kpi a,sys_definition_group_table b
		WHERE b.edw_group_table=a.edw_group_table
			AND a.visible = 1 AND a.on_off=1
			AND b.family='$this->family'
      ORDER BY kpi_label,kpi_name";
	//echo "$query<br>";
	$resultat = pg_query($this->database_connection,$query);
	$nombre_connection = pg_num_rows($resultat);
	for ($i = 0;$i < $nombre_connection;$i++) {
		$row = pg_fetch_array($resultat, $i);
        $this->kpi[]=$row["kpi_name"];
        if ($row["kpi_label"] != '')
            $this->kpi_label[]=$row["kpi_label"];
        else
            $this->kpi_label[]=$row["kpi_name"];
	}
}


  function display_info_global_bloc()
  {
  global $niveau0;

  $query = "SELECT DISTINCT ON (alarm_trigger_data_field)"
          ." *"
          ." FROM $this->table_alarm"
          ." WHERE alarm_id = $this->alarm_id"
          ." AND additional_field is null";
  $result = pg_query($query);

  $row = pg_fetch_array($result);
  ?>
    <table>

			<tr><td class="texteGris" colspan="2"><li>Sort condition</td></tr>

<!--------------------------------------------------->

<? if (!$this->allow_modif) { ?>
      <td><input class="zoneTexteStyleXPFondGris" name="sort_type" style="width:100px" value="<?=$row['list_sort_field_type']?>" readonly></td>
<? } else { ?>
		  <tr>
      <td>
	  <?
		// 27/02/2007 - Modif. benoit : ajout d'un parametre sur la taille max du label dans 'getFieldValue()'
	  ?>
	  <select class="zoneTexteStyleXP" name="sort_type" style="width:100px" onchange="getFieldValue(this.value,'<?="sort_field"?>','<?=$this->family?>',40)">
          <option value='makeSelection'>Type</option>
          <?
          $array_sort_type = Array('kpi','raw');
          $array_sort_type_label = Array('KPI','Raw Counter');
        for($i=0;$i<count($array_sort_type);$i++)
        {
          $selected="";
          if ($array_sort_type[$i]==$row['list_sort_field_type'])
            $selected="selected='selected'";
          ?>
            <option value='<?=$array_sort_type[$i]?>'  <?=$selected?>><?=$array_sort_type_label[$i]?></option>
            <?
        }
        ?> </select></td>
<? } ?>

<!--------------------------------------------------->

<? if (!$this->allow_modif) { ?>
      <td><input class="zoneTexteStyleXPFondGris" name="sort_field" style="width:300px" value="<?=$row['list_sort_field']?>" readonly></td>
<? } else { ?>
      <td><select class="zoneTexteStyleXP" name="sort_field" style="width:300px" onchange="remove_choice(this)">
      <? if ($row['list_sort_field'] == '') {?>
      <option value='makeSelection'>Make a selection</option>
      <?
          }
          if ($row['list_sort_field_type'] == "kpi") {
              $array_list_sort_field = $this->kpi;
              $array_list_sort_field_label = $this->kpi_label;
          }
          if ($row['list_sort_field_type'] == "raw") {
              $array_list_sort_field = $this->counter;
              $array_list_sort_field_label = $this->counter_label;
          }

          for($i=0;$i<count($array_list_sort_field);$i++)
          {
            $selected="";
            if ($array_list_sort_field[$i]==$row['list_sort_field'])
              $selected="selected='selected'";
            ?>
              <option value='<?=$array_list_sort_field[$i]?>' <?=$selected?>><?=$array_list_sort_field_label[$i]?></option>
              <?
          }
      ?>
          </select></td>
<? } ?>

<!--------------------------------------------------->

<? if (!$this->allow_modif) { ?>
      <td><input class="zoneTexteStyleXPFondGris" name="sort_by" style="width:60px" value="<?=$row['list_sort_asc_desc']?>" readonly></td>
<? } else { ?>
        <td><select class="zoneTexteStyleXP" name="sort_by" style="width:60px">

          <?
          $array_sort_by = Array('asc','desc');
        for($i=0;$i<count($array_sort_by);$i++)
        {
          $selected="";
          if ($array_sort_by[$i]==$row['list_sort_asc_desc'])
            $selected="selected='selected'";
          ?>
            <option value='<?=$array_sort_by[$i]?>'  <?=$selected?>><?=$array_sort_by[$i]?></option>
            <?
        }
        ?> </select></td>
        <td>&nbsp;&nbsp;<img src='<?=$niveau0?>images/icones/drop.gif' style='cursor:pointer' onclick='vider_sort_list()'></td>
<? } ?>

<!--------------------------------------------------->

      </tr>
			<tr><td class="texteGris" colspan="2"><li>Trigger</td>
		  </tr>
      <tr>

<!--------------------------------------------------->

<? if (!$this->allow_modif) { ?>
      <td><input class="zoneTexteStyleXPFondGris" name="trigger_type" style="width:100px" value="<?=$row['alarm_trigger_type']?>" readonly></td>
<? } else { ?>
      <td><select class="zoneTexteStyleXP" name="trigger_type" style="width:100px" onchange="getFieldValue(this.value,'<?="trigger_field"?>','<?=$this->family?>')">
          <option value='makeSelection'>Type</option>
          <?
          $array_trigger_type = Array('kpi','raw');
          $array_trigger_type_label = Array('KPI','Raw Counter');
        for($i=0;$i<count($array_trigger_type);$i++)
        {
          $selected="";
          if ($array_trigger_type[$i]==$row['alarm_trigger_type'])
            $selected="selected='selected'";
          ?>
            <option value='<?=$array_trigger_type[$i]?>'  <?=$selected?>><?=$array_trigger_type_label[$i]?></option>
            <?
        }
        ?> </select></td>
<? } ?>

<!--------------------------------------------------->

<? if (!$this->allow_modif) { ?>
      <td><input class="zoneTexteStyleXPFondGris" name="trigger_field" style="width:300px" value="<?=$row['alarm_trigger_data_field']?>" readonly></td>
<? } else { ?>
      <td><select class="zoneTexteStyleXP" name="trigger_field" style="width:300px" onchange="remove_choice(this)">
      <? if ($row['alarm_trigger_data_field'] == '') {?>
      <option value='makeSelection'>Make a selection</option>
      <?
          }
          if ($row['alarm_trigger_type'] == "kpi") {
              $array_trigger_field = $this->kpi;
              $array_trigger_field_label = $this->kpi_label;
          }
          if ($row['alarm_trigger_type'] == "raw") {
              $array_trigger_field = $this->counter;
              $array_trigger_field_label = $this->counter_label;
          }

          for($i=0;$i<count($array_trigger_field);$i++)
          {
            $selected="";
            if ($array_trigger_field[$i]==$row['alarm_trigger_data_field'])
              $selected="selected='selected'";
            ?>
              <option value='<?=$array_trigger_field[$i]?>' <?=$selected?>><?=$array_trigger_field_label[$i]?></option>
              <?
          }

      ?>
          </select></td>
<? } ?>

<!--------------------------------------------------->

<? if (!$this->allow_modif) { ?>
      <td><input class="zoneTexteStyleXPFondGris" name="trigger_operand" style="width:60px" value="<?=$row['alarm_trigger_operand']?>" readonly></td>
<? } else { ?>
        <td><select class="zoneTexteStyleXP" name="trigger_operand" style="width:60px">

          <?
          $operand_possible[0]='none';
          $operand_possible[1]='=';
          $operand_possible[2]='<=';
          $operand_possible[3]='>=';
          $operand_possible[4]='<';
          $operand_possible[5]='>';
        for($i=0;$i<count($operand_possible);$i++)
        {
          $selected="";
          if ($operand_possible[$i]==$row['alarm_trigger_operand'])
            $selected="selected='selected'";
          ?>
            <option value='<?=$operand_possible[$i]?>' <?=$selected?>><?=$operand_possible[$i]?></option>
            <?
        }
        ?> </select></td>
<? } ?>

<!--------------------------------------------------->

<? if (!$this->allow_modif) { ?>
      <td><input class="zoneTexteStyleXPFondGris" name="trigger_value" style="width:45px" value="<?=$row['alarm_trigger_value']?>" readonly></td>
<? } else { ?>
      <td><input class="zoneTexteStyleXP" name="trigger_value" style="width:45px" value="<?=$row['alarm_trigger_value']?>"></td>
      <td>&nbsp;&nbsp;<img src='<?=$niveau0?>images/icones/drop.gif' style='cursor:pointer' onclick='vider_trigger_list()'></td>
<? } ?>
    </tr>
    </table>
      <?
  }


  function display_bloc_info_global()
  {?>
      <table width=100%>
      <tr>
      <td align=left>
      <fieldset>
      <legend class="texteGrisBold">&nbsp;Trigger list&nbsp;&nbsp;</legend>
      <?$this->display_info_global_bloc();?>
      </fieldset>
      </td>
      </tr>
      </table>

      <?
  }


  function display()
  {?>
      <table width="550" align="center" border=0 cellpadding="0" cellspacing="0">

      <tr>
      <td>
      <?$this->display_bloc_info_global();?>
      </td>
      </tr>
      </table>
	  <script language="JavaScript">

	  /**
       *  vérifie que les champs obligatoires ont bien été remplis.
       *  les champs facultatifs partiellement remplis seront ignorés.
       */
      function check_form () {
        nomChamp = false;
        if (document.getElementById('alarm_name').value == '') {
            document.getElementById('alarm_name').focus();
            nomChamp = 'Alarm name';
        } else {
            if (document.getElementById('net_to_sel').value == 'makeSelection') {
                document.getElementById('net_to_sel').focus();
                nomChamp = 'Network level';
            } else {
                if (document.getElementById('time_to_sel').value == 'makeSelection') {
                    document.getElementById('time_to_sel').focus();
                    nomChamp = 'Time resolution';
                } else {

                    temoin=0; // aucun counter ou kpi sélectionné

                        if (document.getElementById('sort_field').value != 'makeSelection') {

                            temoin=1; // au moins un raw ou kpi sélectionné mais aucun sort défini

                            if (temoin==1) return true;
                        }
                    if (temoin==0) {
                        alert('Please, select a sort by element');
                        document.getElementById('sort_type').focus();
                    }
                }
            }
        }
        if (nomChamp) alert("Please, fill in the '"+nomChamp+"' field");
        return false;
      }

      function vider_sort_list() {
      document.getElementById('sort_field').options[0].value='makeSelection';
      document.getElementById('sort_field').options[0].text='Make a selection';
      document.getElementById('sort_field').length=1;
      document.getElementById('sort_field').selectedIndex=0;
      document.getElementById('sort_type').selectedIndex=0;
      document.getElementById('sort_by').selectedIndex=0;
      vider_trigger_list();
    }

    function vider_trigger_list() {
      document.getElementById('trigger_field').options[0].value='makeSelection';
      document.getElementById('trigger_field').options[0].text='Make a selection';
      document.getElementById('trigger_field').length=1;
      document.getElementById('trigger_field').selectedIndex=0;
      document.getElementById('trigger_type').selectedIndex=0;
      document.getElementById('trigger_operand').selectedIndex=0;
      document.getElementById('trigger_value').value='';
    }

    window.focus();

    </script>
      <?
  }

}//fin class
?>

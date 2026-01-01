<?php
/**
*	Ce fichier génère la boite "Time" du sélecteur.
*
*	Les différents éléments de cette boite sont :
*		- ta_level
*		- date
*		- hour
*		- period
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*  30/9/10 MMT problème d'affichage sous IE du GIS, la selection TA se place l'input date et time
*
*	09/03/2010 NSE bz 13648, 13650, 14642
*		- ajout paramètre week_starts_on_monday
*
*   17/08/2010 MMT
*     - bz 16749 Firefox compatibility use getAttribute for popalt(alt_on_over)
*     - bz 16753 changement de calendrier pour utiliser le mode datePicker
*
*  14/09/2010 MMT bz 17791 calendrier ne se ferme pas dans 'user statistics mode'
*     - ajoute condition d'appel sur changeSelecteurTALevel() et convertDateToTALevel()
*
*  14/02/2011 OJT : Modification et réindentation d'un partie du Script pour la DE Sélecteur/Historique
*/

// $to_hide est une chaine qui contient tous les éléments à NE PAS afficher dans la boite.
// ex: $params = array('hide' => 'ta_level date hour period')
$to_hide = ' '.$params['hide'];

//		==========	DATA		==========
//	Les données qui servent à alimenter la boite "Time" du selecteur

// TA levels
$ta_levels = isset($selecteur_values[0]) ? $selecteur_values[0] : Array();

// defaults values for this box
$defaults = isset($selecteur_values[1]) ? $selecteur_values[1] : Array();

$this->setDefaults($defaults);


//		==========	DISPLAY selecteur		==========

?>
<div style="display:none;" id="message_SELECTEUR_FILTER_NOT_SET"><?= __T('SELECTEUR_FILTER_NOT_SET') ?></div>
<div style="display:none;" id="message_SELECTEUR_FILTER_EMPTY"><?= __T('SELECTEUR_FILTER_EMPTY') ?></div>
<div style="display:none;" id="message_SELECTEUR_FILTER_NOT_NUMERIC"><?= __T('SELECTEUR_FILTER_NOT_NUMERIC') ?></div>

<?php if (!strpos($to_hide,'hour')) { ?>
	<!-- pour l'horloge -->
	<link rel='stylesheet' href='<?=URL_CLOCK?>css/prototype_window/default.css' type='text/css'/>
	<link rel='stylesheet' href='<?=URL_CLOCK?>css/prototype_window/alphacube.css' type='text/css'/>
	<link rel='stylesheet' href='<?=URL_CLOCK?>css/clock_style.css' type='text/css'/>
	<script type='text/javascript' src='<?=URL_CLOCK?>js/clock.js'></script>
<? } ?>

<div style="float:left;">
    <!-- 09/03/2010 NSE bz 13648, 13650, 14642 ajout param week_starts -->
    <input type="hidden" id="week_starts_on_monday" name="week_starts_on_monday" value="<?=get_sys_global_parameters('week_starts_on_monday')?>" />
    <!-- ta_level -->
    <?php if (!strpos($to_hide,'ta_level')) { ?>

    <!--  30/9/10 MMT problème d'affichage sous IE du GIS, la selection TA se place l'input date et time -->
    <div id="selecteur_ta_level_div" style="float:left;vertical-align:top">
        <select name="selecteur[ta_level]" id="selecteur_ta_level">
            <?php foreach ($ta_levels as $ta => $ta_label) {?>
                    <option value="<?= $ta ?>" <?php if ($ta == $this->selecteur['ta_level']) echo "selected='selected'"; ?>><?= $ta_label ?></option>
            <?php } ?>
        </select>
    </div>
    <? } ?>

    <!-- autorefresh -->
    <?php if (!strpos($to_hide,'autorefresh')) { ?>
	<br /><br />
	<div id="selecteur_autorefresh">
		<?php
                // 22/11/2011 BBX
                // BZ 24764 : correction des notices php
		$autorefresh_checked = (!empty($this->selecteur['autorefresh'])) ? 'checked' : '';
		?>
		<label for="selecteur_autorefresh_chk">
                    <span class="texteSelecteur" style="vertical-align:middle"><?= __T('SELECTEUR_AUTOREFRESH')?></span>
                </label>
		<input type="checkbox" id="selecteur_autorefresh_chk" name="selecteur[autorefresh]" value="1" onClick="updateAutoRefresh(this.id)" <?=$autorefresh_checked?>>
		<input type="hidden" id="selecteur_autorefresh_delay" name="selecteur[autorefresh_delay]" value="<?php if(isset($this->selecteur['refresh_delay'])) echo $this->selecteur['refresh_delay']; else echo get_sys_global_parameters('autorefresh_delay'); ?>" />
		<input type="hidden" id="selecteur_status" name="selecteur[status]" value="<?=$this->selecteur['status']?>" />
	</div>
	<script type="text/javascript" src="<?= URL_SELECTEUR ?>js/dashboard_time_autorefresh.js"></script>
<? } ?>
</div>
<div style="float:right;width:120px;">
	<!-- date -->
	<?php if (!strpos($to_hide,'date'))
        {
          // 17/08/2010 MMT bz 16753 Utilise JQueryui datePicker pour compatibilité navigateurs
          include_once(REP_PHYSIQUE_NIVEAU_0 . "modules/datePicker_v1.0.0/class/DatePicker.class.php");

          $dp = new DatePicker('selecteur_date');
          $dp->setDate($this->selecteur['date']);
          // affecte variable de date affin de récuperer la date selectionnée apres display
          $dp->setInputName("selecteur[date]");
          // 14/09/2010 MMT bz 17791 calendrier ne se ferme pas dans 'user statistics mode'
          // ajoute condition d'appel sur changeSelecteurTALevel()
          // execute changeSelecteurTALevel() si necessaire apres chaque changement de date pour reformater le champ par rapport au TA
          $onSelectJS = 'function(dateText, inst){
                            $("selecteur_date").value = dateText;
                            if($("selecteur_ta_level")){
                               changeSelecteurTALevel();
                            }
                         }';
          $dp->setOption("onSelect", $onSelectJS);

          // genere HTML
          echo $dp->generateHTML();
        ?>
          <script type='text/javascript' src='<?=URL_DATEPICKER?>js/date_functions.js'></script>
          <script type='text/javascript' src='<?=URL_SELECTEUR?>js/dashboard_time_date.js' ></script>
          <script type='text/javascript' >
             J(function(){
                // necesssite de mettre la valeure de champ cache du datePicker au format dd/mm/yyyy pour
                // qu'il s'initialyse avec la bonne date'
                // 14/09/2010 MMT bz 17791 calendrier ne se ferme pas dans 'user statistics mode'
                if($("selecteur_ta_level")){
                   setDatePickerValue('<?=$dp->getHiddenInputId()?>',convertDateToTALevel('<?=$this->selecteur['date']?>','day'));
                }
             });

          </script>

        <?php
        }
        // Fin changement 17/08/2010 MMT bz 16753
        ?>

	<!-- hour -->
	<?php if (!strpos($to_hide,'hour')) { ?>
		<div id="selecteur_hour_div">
                    <!-- 17/08/2010 MMT bz 16749 Firefox compatibility use getAttribute for popalt(this.alt_on_over) -->
                    <img id='clock_selecteur_hour' align='absmiddle' src='<?=URL_CLOCK?>images/icones/mini_clock.gif'
					style='visibility:visible; cursor:pointer;'
                                    onclick="new Clock('selecteur_hour','selecteur_date');"
                                    onmouseover="popalt(this.getAttribute('alt_on_over'))"
                                    onmouseout="kill()"
                                    alt_on_over="<?= __T('SELECTEUR_HOUR_SELECTOR')?>"
                                    />
                    <input type='text' size='8' value='<?=$this->selecteur['hour']?>' id='selecteur_hour' name='selecteur[hour]' />
		</div>
		<script type="text/javascript" src="<?= URL_SELECTEUR ?>js/dashboard_time_hour.js"></script>
	<? } ?>

    <!-- Fixed Hour Checkbox -->
    <?php if ( $this->fixedHourMode ){?>
        <div id="selecteur_fh_checkbox">
            <label for="selecteur_fh_mode">
                <span class="texteSelecteur" style="float:right;margin-top:3px;">Fixed Hour from BH</span>
            </label>
            <input type="checkbox" id="selecteur_fh_mode" style="float:right;" name='selecteur[fh_mode]'/>
            <input type="hidden" id="selecteur_fh_mode_ini" value="<?php echo intval( $this->selecteur['fh_mode'] ); ?>" />
</div>
    <?php } ?>
</div>
<!-- Fixed Hour BH selection Form -->
<?php if ( $this->fixedHourMode ){?>
    <div id="selecteur_fh_form" style="text-align:right;float:right;width:100%;">
        <select id="selecteur_fh_form_na" style="height:17px;width:40%;float:left;" name='selecteur[fh_na]'></select>
        <select id="selecteur_fh_form_ne" style="height:17px;width:55%" name='selecteur[fh_ne]'></select>
        <select id="selecteur_fh_form_na3" style="height:17px;width:40%;float:left;margin-top:1px;" name='selecteur[fh_na_axe3]'></select>
        <select id="selecteur_fh_form_ne3" style="height:17px;width:55%;margin-top:1px;" name='selecteur[fh_ne_axe3]'></select>
        <select id="selecteur_fh_form_kpi" style="height:17px;width:100%;margin-top:1px" name='selecteur[fh_kpi]'></select>
        <span class ="texteSelecteur" id="selecteur_fh_form_info"
              style="float:right;width:100%;height:15px;line-height:15px;overflow:hidden;color:#C03000;">
        </span>
        <input type="hidden" id="selecteur_fh_form_na_ini" value="<?php echo $this->selecteur['fh_na']; ?>" />
        <input type="hidden" id="selecteur_fh_form_ne_ini" value="<?php echo $this->selecteur['fh_ne']; ?>" />
        <input type="hidden" id="selecteur_fh_form_na3_ini" value="<?php echo $this->selecteur['fh_na_axe3']; ?>" />
        <input type="hidden" id="selecteur_fh_form_ne3_ini" value="<?php echo $this->selecteur['fh_ne_axe3']; ?>"/>
        <input type="hidden" id="selecteur_fh_form_kpi_ini" value="<?php echo $this->selecteur['fh_product_bh'].'||'.$this->selecteur['fh_family_bh']; ?>"/>
    </div>
    <span class ="texteSelecteur" id="selecteur_fh_info" style="text-align:right;float:right;width:100%;height:15px;line-height:15px;overflow:hidden;color:#C03000;"></span>
<?php } ?>

<!-- period -->
<?php
if (!strpos($to_hide,'period'))
{
?>
    <div id="selecteur_period_div">
        <div id="selecteur_period_div_warn" onmouseover="popalt( '<?= __T('SELECTEUR_PERIOD_WARNING')?>' );" onmouseout="kill();"></div>
        <div style="float:right;">
            <?= __T('SELECTEUR_PERIOD')?>&nbsp;(max&nbsp;<span id="selPerMaxVal"><?php echo $this->max_period; ?></span>):
            <?php
                foreach ($ta_levels as $ta => $ta_label)
                {
                    if( isset( $this->max_periods[$ta] ) ){
                        echo "<input type='hidden' id='maxHist_{$ta}' value='{$this->max_periods[$ta]}'/>";
                    }
                }
            ?>
            <input name="selecteur[period]" id="selecteur_period" value="<?php echo $this->selecteur['period'] ?>" size="3" />
        </div>
    </div>
    <script type="text/javascript">
        var selecteur_max_period = <?php echo $this->max_period; ?>;
    </script>
    <script type="text/javascript" src="<?= URL_SELECTEUR ?>js/dashboard_time_period.js"></script>
<?php
}
?>
<?php if (!strpos($to_hide,'ta_level')) { ?>
    <script type="text/javascript" src="<?= URL_SELECTEUR ?>js/dashboard_time.js"></script>
<? }
    if ( $this->fixedHourMode ){?>
    <script type="text/javascript">
        var urlSelcector = "<?= URL_SELECTEUR ?>";
    </script>
    <script type="text/javascript" src="<?= URL_SELECTEUR ?>js/dashboard_time_fh.js"></script>
<?php } ?>

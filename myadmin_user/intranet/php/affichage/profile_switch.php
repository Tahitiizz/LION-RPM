<?php
/**
 * 
 *  CB 5.2
 * 
 * Cette page permet de changer de Profile
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 * 24/04/2012 NSE bz 26636 : utilisation de l'API Portail v 1.0.0.04 pour la gestion de Astellia Administrator
 */
?><?php
session_start();

include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/PAAAuthenticationService.php');

// supprimer le fichier de conf
$PAAAuthentication = PAAAuthenticationService::getAuthenticationService(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/conf/PAA.inc');
// récupère la liste des droits / profiles
$rights = $PAAAuthentication->getUserRights($_SESSION['login'],APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME);
// 27/04/2012 NSE bz 27026 : traduction du Guid en Id
$rights = array_map('ProfileModel::getIdFromPaaGuid',$rights);
// 24/04/2012 NSE bz 26636 : utilisation de l'API Portail v 1.0.0.04
// Utilisateur spécial astellia_support du Portail (remplaçant astellia_admin)
// 30/08/2013 MGO bz 34678 : Profil Astellia administrator en doublon en mode standalone suite à la correction de la fonction isSupportUser
// Appel à isSupportUser seulement en mode Portail
if(PAA_SERVICE == PAAAuthenticationService::$TYPE_CAS && $PAAAuthentication->isSupportUser($_SESSION['login'])){
    // on ajoute le Profile Astellia Administrator en premier
    array_unshift($rights,ProfileModel::getAstelliaAdminProfile());
}
// si affiché en page
if($int==1){
    include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
}
else{
    // si affiché en pop-up
    ?>
<html>
<link rel="stylesheet" href="<?=NIVEAU_0?>css/global_interface.css" type="text/css">
<script language="JavaScript1.2" src="<?=NIVEAU_0?>js/prototype/prototype.js"></script>
<script language="JavaScript1.2" src="<?=NIVEAU_0?>js/fenetres_volantes.js"></script>
<body><?php
    }
?>
    <style>
	#globala{
		margin-left: auto;
		margin-right: auto;
		width: 370px;
		border:0px solid;
		padding: 5px;
	}
	#maina {
		margin: 0;
		text-align: center;
    }

	.div_link{
		text-decoration:underline; cursor:hand; padding:3px;
	}
	.div_link_2{
		text-decoration:none;  padding:3px;
	}
	.div_link:hover{ font-weight:bold; }
</style>
    <div id="maina">
	<div id="globala">
		<div>
			<img src="<?=NIVEAU_0?>images/titres/profile_selection.gif"/>
		</div>
		<script>
			function chg(obj){
				if(obj.style.fontWeight=='bold')
					obj.style.fontWeight='normal';
				else
					obj.style.fontWeight='bold';
			}
		</script>
		<div class="tabPrincipal" style="margin-top:15px; padding:4px;">
			<fieldset class="texteGris" style="text-align:left;">
				<legend class="texteGrisBold">&nbsp;Select your right / profile&nbsp;</legend>
				<?php
                                    // 27/04/2012 NSE bz 27026 : correction déplacée dans ProfileModel::getIdFromPaaGuid
                                    foreach ($rights as $right) {
                                        $ONE_link = NIVEAU_0."acces_intranet.php?fProfile=".$right;
                                        if($int==1){
                                            $link = "window.location='{$ONE_link}';";
                                        }
                                        else{
                                            $link = "window.opener.location='{$ONE_link}'; self.close();";
                                        }
                                        ?>
                                        <div class="div_link" onclick="<?=$link?>" onMouseOver="chg(this)" onMouseOut='chg(this)'>
                                                <img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/>&nbsp;<?php echo ProfileModel::getNameFromRightGuid($right); ?>
                                        </div>
                                        <?php
                                    }
				?>
			</fieldset>
		</div>
	</div>
	</div>
    <?php
    if($int!=1){?>
	</body>
</html><?php
}?>

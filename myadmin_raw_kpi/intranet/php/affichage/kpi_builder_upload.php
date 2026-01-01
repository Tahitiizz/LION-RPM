<?php
include_once REP_PHYSIQUE_NIVEAU_0.'class/UploadFile.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'class/kpi/KpiUpload.class.php';

/**
 * Modifier le texte du loading page
 * @author GHX
 * @param string $txt chaine de caractère à affiché dans le loading page
 */
function loader ($txt)
{
	echo "<script>parent.document.getElementById('texteLoader').innerHTML='".$txt."';</script>";
	flush();
}
?>
<script>
parent.document.getElementById('loader_container').style.display='block';
parent.document.getElementById('loader_container').style.visibility='visible';

var t_id = setInterval(animate,20);
var pos=0;
var dir=2;
var len=0;

function animate(){
	var elem = parent.document.getElementById('progress');
	if(elem != null) {
		if (pos==0) len += dir;
		if (len>32 || pos>79) pos += dir;
		if (pos>79) len -= dir;
		if (pos>79 && len==0) pos=0;
		elem.style.left = pos;
		elem.style.width = len;
	}
}
</script>
<?php
loader('Upload File');

$errorUpload = false;
$errorKPI = false;
$uploadFile = new UploadFile($_FILES['fichier']);


loader('Check file');
// 17/08/2010 OJT : Correction bz16856, ajout du type de fichier pour Firefox
// 15/09/2010 NSE bz 17046 : suppression du test sur le type mime
$uploadFile->deleteEmptyLines();
if ( $uploadFile->checkCSVColumns() )
{
        if ( $uploadFile->moveTo(REP_PHYSIQUE_NIVEAU_0.'upload', null, true) )
        {
                $KpiUpload = new KpiUpload($_POST['idProduct']);
                $KpiUpload->setFamily($_POST['family']);
                $KpiUpload->setFile($uploadFile->getFilename());
                loader('Check data');
                if ( $KpiUpload->check() )
                {
                        loader('Load data');
                        $KpiUpload->loadFile();
                                // maj 24/09/2010 - MPR
                                // On corrige les formules(%) en ajoutant le CASE WHEN formule > 100 THEN 100 ELSE formule END
                                // si ce n'est pas défini
                                $KpiUpload->CorrectFormulaPourcentage();
                        $KpiUpload->launchCleanTablesStructure();
                }
                else
                {
                        $errorKPI = true;
                }
        }
        else
        {
                $errorUpload = true;
        }
}
else
{
        $errorUpload = true;
}

if ( $errorUpload )
{
	echo '<div class="errorMsg">'.$uploadFile->getError().'</div>';
}
else
{
	if ( $errorKPI )
	{
		echo '<div class="errorMsg">'.$KpiUpload->getMessageError().'</div>';
	}
	else
	{
		echo '<div class="okMsg">'.__T('A_SETUP_SYSTEM_ALERTS_UPDATE_SUCCESS').'</div>';
		echo '<div class="infoBox">'.$KpiUpload->getMessageInfo().'</div>';
	}
}
?>
<script>
function remove_loading() {
	this.clearInterval(t_id);
	var targelem = parent.document.getElementById('loader_container');
	targelem.style.display='none';
	targelem.style.visibility='hidden';
	var targelem = parent.document.getElementById('loader_background');
	targelem.style.display='none';
	targelem.style.visibility='hidden';
}
remove_loading();
// Recharge la liste des KPI
parent.kpi_list.location.reload();
</script>
<?
/*
*	@cb50000@
*
*	25/03/2010 NSE bz 14683 : on indique l'adresse correspondante au produit présente en base en signature du mail
*   13/09/2010 - MPR : Correction du bz16214 -  Utilisation de la fonction get_adr_server(true)
*	09/12/2011 ACS Mantis 837 DE HTTPS support
*/
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 10/06/2008, benoit : correction du bug 6224
	- maj 17/06/2008, benoit : recorrection du bug 6224
*/
?>
<?
/*
*	@cb22014@
*
*	02/08/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	02/08/2007 - JL : 	MàJ Insertion du paramètre $mail_type qui est par défaut à "PLAIN" si le paramètre n'est pas renseigné lors de l'appel du constructeur mail()
*					Pour les différents appels déjà éxistant de la class LibMail.class.php, cela ne changera rien.
*					L'intérêt ici est de pouvoir modifier ce type de mail en "HTML" afin de pouvoir intégrer des balises HTML dans le mail 
*				et de pouvoir créer un tableau HTML pour la génération d'alarme sur l'absence de données (cf.  /class/flat_fileçupload.class.php)
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
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

/*
	- maj 21/04/2006, christophe : ajout de la fonction AttachFileExist( $filename ) qui vérifie si un fichier a déjà été attaché au mail. (ligne 279)
  - maj DELTA christophe 26 04 2006.
	this class encapsulates the PHP mail() function.
	implements CC, Bcc, Priority headers


@version	1.3

- added ReplyTo( $address ) method
- added Receipt() method - to add a mail receipt
- added optionnal charset parameter to Body() method. this should fix charset problem on some mail clients

@example

	include "libmail.php";

	$m= new Mail; // create the mail
	$m->From( "leo@isp.com" );
	$m->To( "destination@somewhere.fr" );
	$m->Subject( "the subject of the mail" );

	$message= "Hello world!\nthis is a test of the Mail class\nplease ignore\nThanks.";
	$m->Body( $message);	// set the body
	$m->Cc( "someone@somewhere.fr");
	$m->Bcc( "someoneelse@somewhere.fr");
	$m->Priority(4) ;	// set the priority to Low
	$m->Attach( "/home/leo/toto.gif", "image/gif" ) ;	// attach a file of type image/gif
	$m->Send();	// send the mail
	echo "the mail below has been sent:<br><pre>", $m->Get(), "</pre>";


LASTMOD
	Fri Oct  6 15:46:12 UTC 2000

@author	Leo West - lwest@free.fr

*/

// 10/06/2008 - Modif. benoit : correction du bug 6224. Ajout des inclusions permettant d'accéder aux fonctions de 'edw_function.php'

include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once $repertoire_physique_niveau0."php/database_connection.php";
include_once $repertoire_physique_niveau0.'php/edw_function.php';

class Mail
{
	/*
	list of To addresses
	@var	array
	*/
	var $sendto = array();
	/*
	@var	array
	*/
	var $acc = array();
	/*
	@var	array
	*/
	var $abcc = array();
	/*
	paths of attached files
	@var array
	*/
	var $aattach = array();
	/*
	list of message headers
	@var array
	*/
	var $xheaders = array();
	/*
	message priorities referential
	@var array
	*/
	var $priorities = array( '1 (Highest)', '2 (High)', '3 (Normal)', '4 (Low)', '5 (Lowest)' );
	/*
	character set of message
	@var string
	*/
	var $charset = "us-ascii";
	var $ctencoding = "7bit";
	var $receipt = 0;


/*

	Mail contructor
	02/08/2007 - JL : Lors de la création de l'objet mail, si aucun paramètre n'est précisé, alors on va créer un mail de type plain, 
	dans le cas contraire si c'est HTML qui est saisi on aura un mail qui pourra accepter les balise HTML
	
*/

function Mail($mail_type="plain")
{
	// 02/08/2007 - JL : Si le paramètre n'est pas reconnu, alors on le force sur le mode PLAIN
	if ($mail_type == "html" or $mail_type == "plain"){
		$this->mail_type = $mail_type;
	} else {
		$this->mail_type = "plain";
	}
	$this->autoCheck( true );
	$this->boundary= "--" . md5( uniqid("myboundary") );
}


/*

activate or desactivate the email addresses validator
ex: autoCheck( true ) turn the validator on
by default autoCheck feature is on

@param boolean	$bool set to true to turn on the auto validation
@access public
*/
function autoCheck( $bool )
{
	if( $bool )
		$this->checkAddress = true;
	else
		$this->checkAddress = false;
}


/*

Define the subject line of the email
@param string $subject any monoline string

*/
function Subject( $subject )
{
	$this->xheaders['Subject'] = strtr( $subject, "\r\n" , "  " );
}


/*

set the sender of the mail
@param string $from should be an email address

*/

function From( $from )
{

	if( ! is_string($from) ) {
		echo "Class Mail: error, From is not a string";
		//exit;
	}
	$this->xheaders['From'] = $from;
}

/*
 set the Reply-to header
 @param string $email should be an email address

*/
function ReplyTo( $address )
{

	if( ! is_string($address) )
		return false;

	$this->xheaders["Reply-To"] = $address;

}


/*
add a receipt to the mail ie.  a confirmation is returned to the "From" address (or "ReplyTo" if defined)
when the receiver opens the message.

@warning this functionality is *not* a standard, thus only some mail clients are compliants.

*/

function Receipt()
{
	$this->receipt = 1;
}


/*
set the mail recipient
@param string $to email address, accept both a single address or an array of addresses

*/

function To( $to )
{

	// TODO : test validité sur to
	if( is_array( $to ) )
		$this->sendto= $to;
	else
		$this->sendto[] = $to;

	if( $this->checkAddress == true )
		$this->CheckAdresses( $this->sendto );

}


/*		Cc()
 *		set the CC headers ( carbon copy )
 *		$cc : email address(es), accept both array and string
 */

function Cc( $cc )
{
	if( is_array($cc) )
		$this->acc= $cc;
	else
		$this->acc[]= $cc;

	if( $this->checkAddress == true )
		$this->CheckAdresses( $this->acc );

}



/*		Bcc()
 *		set the Bcc headers ( blank carbon copy ).
 *		$bcc : email address(es), accept both array and string
 */

function Bcc( $bcc )
{
	if( is_array($bcc) ) {
		$this->abcc = $bcc;
	} else {
		$this->abcc[]= $bcc;
	}

	if( $this->checkAddress == true )
		$this->CheckAdresses( $this->abcc );
}


/*		Body( text [, charset] )
 *		set the body (message) of the mail
 *		define the charset if the message contains extended characters (accents)
 *		default to us-ascii
 *		$mail->Body( "mél en français avec des accents", "iso-8859-1" );
 */
function Body( $body, $charset="" )
{
	// 10/06/2008 - Modif. benoit : correction du bug 6224. Utilisation de la méthode 'EndBody()' pour ajouter l'url à la fin de l'email

	$this->body = $body.$this->EndBody();

	if( $charset != "" ) {
		$this->charset = strtolower($charset);
		if( $this->charset != "us-ascii" )
			$this->ctencoding = "8bit";
	}
}

// 10/06/2008 - Modif. benoit : correction du bug 6224. Ajout de la méthode permettant d'ajouter l'url de la version au corps de l'email
// 25/03/2010 NSE bz 14683 : on indique l'adresse correspondante au produit présente en base en signature du mail

function EndBody()
{
	// 17/06/2008 - Modif. benoit : correction du bug 6224. Ajout du titre de la version en plus de l'url

	// 19/06/2008 - Modif. benoit : correction du bug 6224. Suivant le type du mail, on met le message en HTML ou non

	// 25/03/2010 NSE bz 14683 : on récupère l'adresse
	// maj 13/09/2010 - MPR : Correction du bz16214 -  Utilisation de la fonction get_adr_server(true)
	// 09/12/2011 ACS Mantis 837 DE HTTPS support
	$productModel = new ProductModel(ProductModel::getIdMaster());
	$link_to_version = $productModel->getCompleteUrl('', true);

	if ($this->mail_type == "html") 
	{
		return "\n<p style='font : normal 10pt Verdana, Arial, sans-serif; color : #585858;' align='center'>".__T('MAIL_GENERATED_BY', get_sys_global_parameters('product_name')." - ".reduce_num_version(get_sys_global_parameters('product_version')), "<a href='".$link_to_version."'>".$link_to_version."</a>")."</p>";		
	}
	else 
	{
		return "\n".__T('MAIL_GENERATED_BY', get_sys_global_parameters('product_name')." - ".reduce_num_version(get_sys_global_parameters('product_version')), $link_to_version);		
	}
}

/*		Organization( $org )
 *		set the Organization header
 */

function Organization( $org )
{
	if( trim( $org != "" )  )
		$this->xheaders['Organization'] = $org;
}


/*		Priority( $priority )
 *		set the mail priority
 *		$priority : integer taken between 1 (highest) and 5 ( lowest )
 *		ex: $mail->Priority(1) ; => Highest
 */

function Priority( $priority )
{
	if( ! intval( $priority ) )
		return false;

	if( ! isset( $this->priorities[$priority-1]) )
		return false;

	$this->xheaders["X-Priority"] = $this->priorities[$priority-1];

	return true;

}

/*
	Rajout christophe
  Permet de vérifier si un fichier a déjà été attaché au mail
*/
function AttachFileExist( $filename ){
	for($i = 0; $i < count($this->aattach); $i++){
		if($this->aattach[$i] == $filename) return true;
	}
	return false;
}

/*
 Attach a file to the mail

 @param string $filename : path of the file to attach
 @param string $filetype : MIME-type of the file. default to 'application/x-unknown-content-type'
 @param string $disposition : instruct the Mailclient to display the file if possible ("inline") or always as a link ("attachment") possible values are "inline", "attachment"
 */

function Attach( $filename, $filetype = "", $disposition = "inline" )
{
	// TODO : si filetype="", alors chercher dans un tablo de MT connus / extension du fichier
	if( $filetype == "" )
		$filetype = "application/x-unknown-content-type";

	$this->aattach[] = $filename;
	$this->actype[] = $filetype;
	$this->adispo[] = $disposition;
}

/*

Build the email message

@access protected

*/
function BuildMail()
{

	// build the headers
	$this->headers = "";
//	$this->xheaders['To'] = implode( ", ", $this->sendto );
	// JL - Déplacement de cette attribution de valeur à la variable der classe : $this->xheaders['To'] , afin de pouvoir récupérer les mail destinateir à partir de la méthode get.
	$this->strTo = implode( ", ", $this->sendto );

	if( count($this->acc) > 0 )
		$this->xheaders['CC'] = implode( ", ", $this->acc );

	if( count($this->abcc) > 0 )
		$this->xheaders['BCC'] = implode( ", ", $this->abcc );


	if( $this->receipt ) {
		if( isset($this->xheaders["Reply-To"] ) )
			$this->xheaders["Disposition-Notification-To"] = $this->xheaders["Reply-To"];
		else
			$this->xheaders["Disposition-Notification-To"] = $this->xheaders['From'];
	}

	// 02/08/2007 - JL : Modification du "content-type". A la place de 'text/plain' on a ajouter une variable de classe '$this->mail_type' qui est créée lors 
	//			de l'appel du constructeur. En fonction du choix de l'utilisation de la classe, on peut mettre soit PLAIN soit HTML
	if( $this->charset != "" ) {
		$this->xheaders["Mime-Version"] = "1.0";
		$this->xheaders["Content-Type"] = "text/$this->mail_type; charset=$this->charset";
		$this->xheaders["Content-Transfer-Encoding"] = $this->ctencoding;
	}

	$this->xheaders["X-Mailer"] = "Php/libMailv1.3";

	// include attached files
	if( count( $this->aattach ) > 0 ) {
		$this->_build_attachement();
	} else {
		$this->fullBody = $this->body;
	}

	reset($this->xheaders);
	while( list( $hdr,$value ) = each( $this->xheaders )  ) {
		if( $hdr != "Subject" )
			$this->headers .= "$hdr: $value\n";
	}


}

/*
	fornat and send the mail
	@access public

*/
function Send()
{
	$this->BuildMail();

	// JL - Si l'on ne récupère les noms des destinataire que ici, le mail leur sera bien envoyé, mais la méthode get ne pourra pas les afficher.
	//$this->strTo = implode( ", ", $this->sendto );

	// envoie du mail
	$res = @mail( $this->strTo, $this->xheaders['Subject'], $this->fullBody, $this->headers );
	__debug($res,"send mail");

}



/*
 *		return the whole e-mail , headers + message
 *		can be used for displaying the message in plain text or logging it
 */

function Get()
{
	$this->BuildMail();
	$mail = "To: " . $this->strTo . "\n";
	$mail .= $this->headers . "\n";
	$mail .= $this->fullBody;
	return $mail;
}


/*
	check an email address validity
	@access public
	@param string $address : email address to check
	@return true if email adress is ok
 */

function ValidEmail($address)
{
	// 2010/08/11 - MGD - BZ 16883 - les adresses précédées d'un nom (par exemple "aa <bb@cc.com") doivent être nettoyées
	$addr_clean = preg_replace("/(^.*<\s*)|(\s*>\s*$)/", '', $address);
	if(filter_var($addr_clean, FILTER_VALIDATE_EMAIL))
	// 2010/08/11 - MGD - BZ 16883 - Fin correction
 		return true;
 	else
 		return false;
}


/*

	check validity of email addresses
	@param	array $aad -
	@return if unvalid, output an error message and exit, this may -should- be customized

 */

function CheckAdresses( $aad )
{
	for($i=0;$i< count( $aad); $i++ ) {
		if( ! $this->ValidEmail( $aad[$i]) ) {
			echo "Class Mail, method Mail : invalid address $aad[$i]";
			//exit;
		}
	}
}


/*
 check and encode attach file(s) . internal use only
 @access private
*/

function _build_attachement()
{

	$this->xheaders["Content-Type"] = "multipart/mixed;\n boundary=\"$this->boundary\"";

	$this->fullBody = "This is a multi-part message in MIME format.\n--$this->boundary\n";
	$this->fullBody .= "Content-Type: text/plain; charset=$this->charset\nContent-Transfer-Encoding: $this->ctencoding\n\n" . $this->body ."\n";

	$sep= chr(10);

	$ata= array();
	$k=0;

	// for each attached file, do...
	for( $i=0; $i < count( $this->aattach); $i++ ) {

		$filename = $this->aattach[$i];
		$basename = basename($filename);
		$ctype = $this->actype[$i];	// content-type
		$disposition = $this->adispo[$i];

		if( ! file_exists( $filename) ) {
			echo "Class Mail, method attach : file $filename can't be found";
			//exit;
		}
		$subhdr= "--$this->boundary\nContent-type: $ctype;\n name=\"$basename\"\nContent-Transfer-Encoding: base64\nContent-Disposition: $disposition;\n  filename=\"$basename\"\n";
		$ata[$k++] = $subhdr;
		// non encoded line length
		$linesz= filesize( $filename)+1;
		$fp= fopen( $filename, 'r' );
		$ata[$k++] = chunk_split(base64_encode(fread( $fp, $linesz)));
		fclose($fp);
	}
	$this->fullBody .= implode($sep, $ata);
	if(count( $this->aattach)>0){//close last attachement
		$this->fullBody .= "--$this->boundary--";		
	}
}


} // class Mail


?>

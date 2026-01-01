<?php
/* 
 * 17/08/2010 création de la classe dans le cadre du bug bz 16753
 */

/**
 * La classe Datepicker fait l'encapsulation du calendrier/selectionner de date JQuery
 * Cette classe permet la factorization et simplifie l'appel au calendrier
 * Elle effectue la gestion des imports css et javascript, html de l'input date et icone
 * mais egalement offre une interface pour gerer les options
 *
 * Le format HTML du datepicker est:
 *
 * <imports JS + css>
 * <div >
 *    <img  -icone>
 *    <input type="hidden" date_pour_jquery>
 *    <input date_a_afficher>
 * </div>
 *
 * @author m.monfort
 */
class DatePicker {
    //put your code here


   // permet d'avoir un import unique lorsqu'il y a plusieures instances
   private static $importsGenerated = false;

   // id de l'input Text qui contient la date à afficher
   private $inputId;

   // id de l'input qui contient la date lue par le datePicker JQuery
   private $inputHiddenId;

   // id de l'image de l'icone
   private $imageId;

   // nom optionel de l'input
   private $inputName;

   // options du datePicker JQuery voir http://jqueryui.com/demos/datepicker  pour doc
   private $options = array();

   // valeure par default de la date
   private $date;

   //si vrai l'utilisateur peux changer la date dans l'input lui meme au clavier
   private $inputEditable = true;

   // html a inserer apres l'input (optionel)
   private $postInputHtml = "";


   /**
    * Construit instance de DatePicker
    * @param <type> $inputId id de l'input text qui contiendra la date - requis
    * @param <type> $imageId id de l'image de l'icone - optionel
    */
   public function __construct($inputId, $imageId="")
	{
     $this->inputId = $inputId;
     $this->inputName = $inputId;
     $this->inputHiddenId = $inputId."_hidden";

     $this->imageId = $imageId;
     if($imageId == ""){
        $this->imageId = $this->inputId."_img";
     }
     $this->loadEnvSettings();

   }

   /**
    * Get the HiddenInputId used by the datePicker to store the date always in readable format
    * by the datePicker (dd/mm/yyyy)
    * @return String
    */
   public function getHiddenInputId()
   {
      return $this->inputHiddenId;
   }

   /**
    * set name of the inputField if necessary
    * @param <type> $inputName 
    */
   public function setInputName($inputName)
   {
      $this->inputName = $inputName;
   }


   /**
    * set datePicker options depending on environement parameters
    * TODO extend this functionality with other parameters
    */
   private function loadEnvSettings()
   {
      // FDE: set week beginning depending on parameter (0=sunday to 6=satturday)
      $weekStartDay = get_sys_global_parameters('week_starts_on_monday');
      if($weekStartDay){
        $this->setOption("firstDay",$weekStartDay);
     }
   }

   /**
    * Ajouter une option JQueryui au datepicker
    * voir http://jqueryui.com/demos/datepicker  pour doc
    * @param <type> $name nom de l'option
    * @param <type> $value valeure
    */
   public function setOption($name, $value)
   {
      $this->options[$name] = $value;
   }

   /**
    * Specifie la date par defaut du datePicker et affichée dans l'input
    * si rien n'est specifier, il utilise la date d'aujourd'hui
    * @param <type> $date
    */
   public function setDate($date)
   {
      $this->date = $date;
   }

   /**
    * Permet d'inserer du code html apres la déclaration de l'input et de l'image icone
    * @param <type> $html HTML code a ajouter sous forme de chaine
    */
   public function addPostInputHTML($html)
   {
      $this->postInputHtml .= $html;
   }

   /**
    * permet de specifier que l'input date n'est pas modifiable par l'utilsateur
    * directement et doit passer par le datepicker
    */
   public function setInputReadOnly()
   {
      $this->inputEditable = false;
   }

   /**
    * retourne tous les imports CSS et JS nécéssaires au datePicker
    * @return string imports format HTML
    */
   private function generateImports()
   {
      $html = "<link type='text/css' href='".URL_DATEPICKER."css/smoothness/jquery-ui-1.8.2.custom.css' rel='Stylesheet' />\n";
      $html.= "<link type='text/css' href='".URL_DATEPICKER."css/datePicker.css' rel='Stylesheet' />\n";
      $html.= "<script type='text/javascript' src='".NIVEAU_0. "js/jQuery/jquery-1.4.2.min.js' ></script>\n";
      $html.= "<script type='text/javascript' src='".NIVEAU_0. "js/jQuery/jquery-ui-1.8.2.custom.min.js' ></script>\n";
      $html.= "<script type='text/javascript' src='".URL_DATEPICKER."js/datePicker.js' ></script>\n";
      
      return  $html;
   }

   /**
    * retourne HTML optionel a placer apres l'input
    * @return string imports format HTML
    */
   private function generatePostInput()
   {
      return  $this->postInputHtml;
   }


   /**
    * retourne code Javascript pour appel des fonctions de js/datePicker.js
    * @return string
    */
   private function generateJSCall()
   {

      $html = "<script type='text/javascript'>";
      // appelle fonction creation datePicker JS
      $html.= "addDatePicker('".$this->inputHiddenId."','".$this->imageId."');";
      
      // ajout optionel d'options avec methode setDatePickerOptions
      if(!empty($this->options)){
         $optsStr = "{";
         foreach($this->options as $key => $value)
         {
            $optsStr.= $key." : ".$value.", ";
         }
         $optsStr = substr($optsStr,0,-2)."}";
         
         $html.= "setDatePickerOptions('".$this->inputHiddenId."',". $optsStr.");";
      }

      // specifie date par default
      if($this->date){
         $html.= "setDatePickerValue('".$this->inputHiddenId."','".$this->date."');";
      }

      $html.= "</script>";
      return $html;

   }

   /**
    * retourne code HTLM contenant la division contenant l'image et l'input
    * @return string
    */
   private function generateInputDiv()
   {
      $html = "<div >";

      $html.= "  <img id='".$this->imageId."' border='0' align='absmiddle' src='".NIVEAU_0."images/icones/bouton_calendrier.gif'";
      $html.= "     valign='top' style='cursor:pointer;'  onmouseover=\"popalt(this.getAttribute('alt_on_over'))\" alt_on_over='".__T('SELECTEUR_CALENDAR')."' />";
      $html.= "  <input id='".$this->inputHiddenId."' type='hidden' onchange='\$(".$this->inputId.").value = this.value' />";
      $html.= "  <input id='".$this->inputId."' name='".$this->inputName."' class='zoneTexteStyleXP' maxLength='10' size='10' ";
      // date par default
      if($this->date){
         $html.= " value='".$this->date."' ";
      }
      // si l'input n'est pas editable
      if(!$this->inputEditable){
         $html.= " readonly ";
      }
      $html.= " /></div>";
      return $html;
   }
   


   /**
    *Genere l'HTML total nécéssaire suivant la configuration de l'utilisateur
    * @return <type>
    */
	public function generateHTML()
	{
      $html = "";
      // un seul import par page
      if(!self::$importsGenerated){
         $html.= $this->generateImports();
         self::$importsGenerated = true;
      }
      $html.= $this->generateJSCall();
      $html.= $this->generateInputDiv();
      if($this->postInputHtml){
         $html.= $this->generatePostInput();
      }

      return $html;
	}

}
?>

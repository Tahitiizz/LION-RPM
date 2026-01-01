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
	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");

	global $niveau0;
?>
<html>
<link rel="stylesheet" href="<?=$niveau0?>css/pauto.css" />
<body>

<?
class pagedisplay {


 function pagedisplay($id_page)
          {
            $this->id_page=$id_page;
            $this->include_class();
            $this->get_page_mysql();
            $this->connection();
            $this->display_page();
          }

 function include_class()
       {
        global $database_connection;
        $query="select distinct class_object from sys_pauto_config where id_page=".$this->id_page;
        $result=pg_query($database_connection,$query);
		$result_nb = pg_num_rows($result);

        for ($k = 0;$k < $result_nb;$k++)
    		{
    		   $result_array= pg_fetch_array($result, $k);
               $class=$result_array["class_object"];
               include_once($class.".class.php");
              }

       }

 function connection()
       {
        /*$DBHost = "localhost";
        $DBport = "5432";
        $DBUser = "root";
        $DBPass = "";
        $DBName = "pauto";
        $database_connection = mysql_connect($DBHost,$DBUser,$DBPass);
        mysql_select_db($DBName);*/
      }

 function get_page_mysql()
         {
          global $database_connection;
          $query="select * FROM sys_pauto_config where  id_page=".$this->id_page;
          $result=pg_query($database_connection,$query);
		  $result_nb = pg_num_rows($result);
          //echo $query."<br>";

          for ($k = 0;$k < $result_nb;$k++)
    		{
    			  $result_array= pg_fetch_array($result, $k);
                  $index_pos=						$result_array["ligne"].":".$result_array["colonne"];
                  $this->position_ligne[]=			$result_array["ligne"];
                  $this->position_colonne[]=		$result_array["colonne"];
                  $this->class_object["$index_pos"]=$result_array["class_object"];
                  $this->id_elem["$index_pos"]=		$result_array["id_elem"];
                }
          }

	function get_max_colonne(){
			global $database_connection;
			$query="select max(colonne)as nb_colonne FROM sys_pauto_config where  id_page=".$this->id_page;
			$result=pg_query($database_connection,$query);
			$result_nb = pg_num_rows($result);
			for ($k = 0;$k < $result_nb;$k++){
				$result_array= pg_fetch_array($result, $k);
				$this->position_max_colonne=$result_array["nb_colonne"]+1;
			}
	}

 function get_max_ligne()
          {
		   global $database_connection;
           $query="select max(ligne)as nb_ligne FROM sys_pauto_config where  id_page=".$this->id_page;
           $result=pg_query($database_connection,$query);
			$result_nb = pg_num_rows($result);
           //echo $query."<br>";

           for ($k = 0;$k < $result_nb;$k++)
    			{
    			  $result_array= pg_fetch_array($result, $k);
                  $this->position_max_ligne=$result_array["nb_ligne"]+1;
                 }
          }



  function display_page()
         {?>
                <table align="center" cellpadding="10">
                 <div class="tree">
                  <tr>
                    <td>
                      <?$this->display_page_contenu();?>
                    </td>
                  </tr>
                </div>
                </table>
         <?}

 function get_class_element($index_elem)
         {
          switch ($this->class_object["$index_elem"])
          {
           case 'graph' : $class_element= new  Graph($this->id_elem["$index_elem"]);break;
           case 'selecteur' : $class_element= new  Selecteur($this->id_elem["$index_elem"]);break;
         }
        }


 function display_page_contenu()
         {
          echo "<table class=\"tabPrincipalAffichagePauto\" align=\"center\" cellpadding=\"2\" cellspacing=\"3\">";

          $this->get_max_ligne();
          $this->get_max_colonne();

          if ($this->position_max_colonne<1){$this->position_max_colonne=1;}
          if ($this->position_max_ligne<1){$this->position_max_ligne=1;}




           //for ($k=0;$k<$this->position_max_ligne;$k++)
		   for ($k=($this->position_max_ligne - 1);$k!=-1;$k--)
              {
                     echo "<tr>";
                     //for ($j=0;$j<$this->position_max_colonne;$j++)
					 for ($j=($this->position_max_colonne - 1);$j!=-1;$j--)
                          {
                           $contenu='';
                           $index_contenu="$k:$j";
                           if (isset($this->id_elem["$index_contenu"]))
                              {?>
                               <td id='$k:$j'>
                                    <?=$this->get_class_element($index_contenu)?>
                                </td>
                              <?}
                              else
                              {?>
                               <td id='$k:$j' class="fondCaseVide">
                                    <font class="texteGris">Empty</font>
                                </td>

                              <?}
                              }

                 echo "</tr>";
              }
          echo "</table>";
         }

} //fin class


?>
<body>
<table align="center">
	<tr>
		<td align="center">
				<img src="<?=$niveau0?>images/icones/icone_wip.gif"/>
		</td>
		<td valign="center">
				<font class="texteGris">
				  	This is an example of your page-setting
				</font>
		</td>
	</tr>
</table>
<?
//echo $_GET['id_page'];
$page= new pagedisplay($_GET['id_page']);
?>
</body>
</html>

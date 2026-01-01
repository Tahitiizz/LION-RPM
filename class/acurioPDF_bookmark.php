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
Class PDF_Bookmark
Cette extension ajoute le support des signets. La méthode pour ajouter un signet est la suivante :

function Bookmark(string txt [, int level [, float y]])

txt :	titre du signet.
level : niveau du signet (0 pour le plus haut niveau, 1 juste en dessous, etc). Valeur par défaut : 0.
y :		ordonnée de la destination du signet dans la page. -1 désigne la position courante. Valeur par défaut : 0.

Tirée de http://www.fpdf.org/fr/script/script1.php


EXEMPLE :

define('FPDF_FONTPATH','font/');
require('bookmark.php');

$pdf=new PDF_Bookmark();
$pdf->Open();
$pdf->SetFont('Arial','',15);
//Page 1
$pdf->AddPage();
$pdf->Bookmark('Page 1');
$pdf->Bookmark('Paragraphe 1',1,-1);
$pdf->Cell(0,6,'Paragraphe 1');
$pdf->Ln(50);
$pdf->Bookmark('Paragraphe 2',1,-1);
$pdf->Cell(0,6,'Paragraphe 2');
//Page 2
$pdf->AddPage();
$pdf->Bookmark('Page 2');
$pdf->Bookmark('Paragraphe 3',1,-1);
$pdf->Cell(0,6,'Paragraphe 3');
$pdf->Output();

*/

class PDF_Bookmark extends FPDF
{
var $outlines=array();
var $OutlineRoot;

function Bookmark($txt,$level=0,$y=0)
{
    if($y==-1)
        $y=$this->GetY();
    $this->outlines[]=array('t'=>$txt,'l'=>$level,'y'=>$y,'p'=>$this->PageNo());
}

function _putbookmarks()
{
    $nb=count($this->outlines);
    if($nb==0)
        return;
    $lru=array();
    $level=0;
    foreach($this->outlines as $i=>$o)
    {
        if($o['l']>0)
        {
            $parent=$lru[$o['l']-1];
            //Set parent and last pointers
            $this->outlines[$i]['parent']=$parent;
            $this->outlines[$parent]['last']=$i;
            if($o['l']>$level)
            {
                //Level increasing: set first pointer
                $this->outlines[$parent]['first']=$i;
            }
        }
        else
            $this->outlines[$i]['parent']=$nb;
        if($o['l']<=$level and $i>0)
        {
            //Set prev and next pointers
            $prev=$lru[$o['l']];
            $this->outlines[$prev]['next']=$i;
            $this->outlines[$i]['prev']=$prev;
        }
        $lru[$o['l']]=$i;
        $level=$o['l'];
    }
    //Outline items
    $n=$this->n+1;
    foreach($this->outlines as $i=>$o)
    {
        $this->_newobj();
        $this->_out('<</Title '.$this->_textstring($o['t']));
        $this->_out('/Parent '.($n+$o['parent']).' 0 R');
        if(isset($o['prev']))
            $this->_out('/Prev '.($n+$o['prev']).' 0 R');
        if(isset($o['next']))
            $this->_out('/Next '.($n+$o['next']).' 0 R');
        if(isset($o['first']))
            $this->_out('/First '.($n+$o['first']).' 0 R');
        if(isset($o['last']))
            $this->_out('/Last '.($n+$o['last']).' 0 R');
        $this->_out(sprintf('/Dest [%d 0 R /XYZ 0 %.2f null]',1+2*$o['p'],($this->h-$o['y'])*$this->k));
        $this->_out('/Count 0>>');
        $this->_out('endobj');
    }
    //Outline root
    $this->_newobj();
    $this->OutlineRoot=$this->n;
    $this->_out('<</Type /Outlines /First '.$n.' 0 R');
    $this->_out('/Last '.($n+$lru[0]).' 0 R>>');
    $this->_out('endobj');
}

function _putresources()
{
    parent::_putresources();
    $this->_putbookmarks();
}

function _putcatalog()
{
    parent::_putcatalog();
    if(count($this->outlines)>0)
    {
        $this->_out('/Outlines '.$this->OutlineRoot.' 0 R');
        $this->_out('/PageMode /UseOutlines');
    }
}
}

?>

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
Class PDF_Index

Cette classe imprime un index à partir des bookmarks créés. Elle nécessite donc l'extension bookmark.

Prise ici :
http://www.fpdf.org/fr/script/script13.php

EXEMPLE :

define('FPDF_FONTPATH','font/');
require('createindex.php');

$pdf=new PDF_Index();
$pdf->Open();
$pdf->SetFont('Arial','',15);

//Page 1
$pdf->AddPage();
$pdf->Bookmark('Section 1');
$pdf->Cell(0,6,'Section 1',0,1);
$pdf->Bookmark('Sous-section 1',1,-1);
$pdf->Cell(0,6,'Sous-section 1');
$pdf->Ln(50);
$pdf->Bookmark('Sous-section 2',1,-1);
$pdf->Cell(0,6,'Sous-section 2');

//Page 2
$pdf->AddPage();
$pdf->Bookmark('Section 2');
$pdf->Cell(0,6,'Section 2',0,1);
$pdf->Bookmark('Sous-section 1',1,-1);
$pdf->Cell(0,6,'Sous-section 1');

//Index
$pdf->AddPage();
$pdf->Bookmark('Index');
$pdf->CreateIndex();
$pdf->Output();

*/
class PDF_Index extends PDF_Bookmark
{
function CreateIndex(){
    //Titre
    $this->SetFontSize(20);
    $this->Cell(0,5,'Index',0,1,'C');
    $this->SetFontSize(15);
    $this->Ln(10);

    $size=sizeof($this->outlines);
    $PageCellSize=$this->GetStringWidth('p. '.$this->outlines[$size-1]['p'])+2;
    for ($i=0;$i<$size;$i++){
        //Décalage
        $level=$this->outlines[$i]['l'];
        if($level>0)
            $this->Cell($level*8);

        //Libellé
        $str=$this->outlines[$i]['t'];
        $strsize=$this->GetStringWidth($str);
        $avail_size=$this->w-$this->lMargin-$this->rMargin-$PageCellSize-($level*8)-4;
        while ($strsize>=$avail_size){
            $str=substr($str,0,-1);
            $strsize=$this->GetStringWidth($str);
        }
        $this->Cell($strsize+2,$this->FontSize+2,$str);

        //Points
        $w=$this->w-$this->lMargin-$this->rMargin-$PageCellSize-($level*8)-($strsize+2);
        $nb=$w/$this->GetStringWidth('.');
        $dots=str_repeat('.',$nb);
        $this->Cell($w,$this->FontSize+2,$dots,0,0,'R');

        //Numéro de page
        $this->Cell($PageCellSize,$this->FontSize+2,'p. '.$this->outlines[$i]['p'],0,1,'R');
    }
}
}

?>

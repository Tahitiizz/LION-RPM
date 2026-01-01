/*********************************************************************************************************************************************
Fonctions qui permettent de gérer l'affichage d'une fenêtre volante
**********************************************************************************************************************************************/
//affiche un layer
window.document.write("<DIV id='topdeck' style='POSITION: absolute; VISIBILITY: visible; Z-INDEX: 100; '></DIV>");

function popalt(msg)
{
	var style		= "STYLE=' FONT: 10px Arial; COLOR:#000000;PADDING: 0px; border:1pt solid #000000;BACKGROUND-COLOR: #ffffbb'";
	var content		= "<TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0 style='filter: shadow(color=#C0C0C0, direction=135);'><TR><TD "+style+">&nbsp;"+msg+"&nbsp;</TD></TR></TABLE>";
	var nomlayer	= document.getElementById("topdeck");
	
	nomlayer.innerHTML = content;
	nomlayer.style.visibility = "visible";
	
	positionTip();
}

var offX = 10;	// how far from mouse to show tip
var offY = -5;

var mouseX, mouseY;

function positionTip(evt) {
	mouseX = window.event.clientX + document.body.scrollLeft;
	mouseY = window.event.clientY + document.body.scrollTop;

	var nomlayer = document.getElementById("topdeck");
	// nomlayer width and height
	var tpWd = nomlayer.clientWidth;
	var tpHt = nomlayer.clientHeight;
	// document area in view (subtract scrollbar width for ns)
	var winWd = document.body.clientWidth+document.body.scrollLeft;
	var winHt = document.body.clientHeight+document.body.scrollTop;

	// check mouse position, tip and window dimensions
	// and position the nomlayer
	if ((mouseX+offX+tpWd)>winWd){
		nomlayer.style.left = mouseX-(tpWd+offX)+"px";
	}
	else
	{
		nomlayer.style.left = mouseX+offX+"px";
	}
	if ((mouseY+offY+tpHt)>winHt){
		nomlayer.style.top = mouseY-(tpHt+offY)+"px";
	}
	else
	{
		nomlayer.style.top = mouseY+offY+"px";
	}
}

function kill()
{
	var nomlayer = document.getElementById("topdeck");
	nomlayer.style.visibility = "hidden";
}
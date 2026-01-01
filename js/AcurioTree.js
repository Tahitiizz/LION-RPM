
var imgPlus	= new Image();
var imgMinus	= new Image();
imgPlus.src	= chemin+"plus.png";
imgMinus.src	= chemin+"minus.png";


function showNode(Node) {
	switch(navigator.family){
		case 'nn4':
			// Nav 4.x code fork...
	var oTable = document.layers["span" + Node];
	var oImg = document.layers["img" + Node];
			break;
		case 'ie4':
			// IE 4/5 code fork...
	var oTable = document.all["span" + Node];
	var oImg = document.all["img" + Node];
			break;
		case 'gecko':
			// Standards Compliant code fork...
	var oTable = document.getElementById("span" + Node);
	var oImg = document.getElementById("img" + Node);
			break;
	}
	oImg.src = imgMinus.src;
	oTable.style.display = "block";
}


function hideNode(Node){
	switch(navigator.family){
		case 'nn4':
			// Nav 4.x code fork...
	var oTable = document.layers["span" + Node];
	var oImg = document.layers["img" + Node];
			break;
		case 'ie4':
			// IE 4/5 code fork...
	var oTable = document.all["span" + Node];
	var oImg = document.all["img" + Node];
			break;
		case 'gecko':
			// Standards Compliant code fork...
	var oTable = document.getElementById("span" + Node);
	var oImg = document.getElementById("img" + Node);
			break;
	}
	oImg.src = imgPlus.src;
	oTable.style.display = "none";
}


function nodeIsVisible(Node){
	switch(navigator.family){
		case 'nn4':
			// Nav 4.x code fork...
	var oTable = document.layers["span" + Node];
			break;
		case 'ie4':
			// IE 4/5 code fork...
	var oTable = document.all["span" + Node];
			break;
		case 'gecko':
			// Standards Compliant code fork...
	var oTable = document.getElementById("span" + Node);
			break;
	}
	return (oTable && oTable.style.display == "block");
}


function toggleNodeVisibility(Node){
	if (nodeIsVisible(Node)) {
		hideNode(Node);
	} else {
		showNode(Node);
	}
}


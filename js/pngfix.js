/*
* fonction qui gere la transparence des png pour IE < 7
* 
* 27/04/2009 - SPS : modification de la methode pour appeler la fonction sur le "load" de la page
*
* @source http://homepage.ntlworld.com/bobosola
*/

var arVersion = navigator.appVersion.split("MSIE");
var version = parseFloat(arVersion[1]);

function correctPNG(img) // correctly handle PNG transparency in Win IE 5.5 and 6.
{
	if ((version >= 5.5) && (version < 7) && (document.body.filters)) 
	{	
		//on recupere les images avec la classe 'pngfix'
		var tImg = $$('img.pngfix');
		for(var i=0;i < tImg.length; i++) {
			var img = tImg[i];
			var imgName = img.src.toUpperCase();
			if (imgName.substring(imgName.length-3, imgName.length) == "PNG")
			{
				var imgID = (img.id) ? "id='" + img.id + "' " : "";
				var imgClass = (img.className) ? "class='" + img.className + "' " : "";
				var imgTitle = (img.title) ? "title='" + img.title + "' " : "title='" + img.alt + "' ";
				var imgStyle = "display:block;" + img.style.cssText ;
				var imgAttribs = img.attributes;;
				for (var j=0; j<imgAttribs.length; j++)
				{
					var imgAttrib = imgAttribs[j];
					if (imgAttrib.nodeName == "align")
					{		  
						if (imgAttrib.nodeValue == "left") imgStyle = "float:left;" + imgStyle;
						if (imgAttrib.nodeValue == "right") imgStyle = "float:right;" + imgStyle;
						break
					}
				}
				var strNewHTML = "<span " + imgID + imgClass + imgTitle;
				strNewHTML += " style=\"" + "width:" + img.width + "px; height:" + img.height + "px;" + imgStyle + ";";
				strNewHTML += "filter:progid:DXImageTransform.Microsoft.AlphaImageLoader";
				strNewHTML += "(src='" + img.src + "', sizingMethod='scale');\"";
				strNewHTML += "></span>" ;
				img.outerHTML = strNewHTML;
			}
		}
	}
}
/* 27/04/2009 - SPS : modification de la methode pour appeler la fonction sur le "load" de la page*/
Event.observe(window, 'load', correctPNG);
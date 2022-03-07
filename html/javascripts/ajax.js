/* Recuperation de l'objet XMLHttpRequest */
function getXMLHttpRequest()
{
	var xhr = null;	
	if (window.XMLHttpRequest || window.ActiveXObject) 
	{
		if (window.ActiveXObject)  // IE
		{
			try {
				xhr = new ActiveXObject("Msxml2.XMLHTTP");
			} catch(e) {
				xhr = new ActiveXObject("Microsoft.XMLHTTP");
			}
		} 
		else   // Firefox et autres 
		{
			xhr = new XMLHttpRequest(); 
		}
	} 
	else    // XMLHttpRequest non supporte par le navigateur
	{
		// alert("Votre navigateur ne supporte pas l'objet XMLHTTPRequest...");
		return null;
	}
	return xhr;
}

/* ajoute une ligne dans une liste deroulante */
function ajouteLigneSelect (select, text, val)
{
	var oOption, oInner;
	oOption = document.createElement("option");
	oInner  = document.createTextNode(text);
	oOption.value = val;
	oOption.setAttribute("selected", true);
	oOption.appendChild(oInner);
	select.appendChild(oOption);	
	return select;
}

/* Met a jour les composantes possibles en fonction de la structure referente */
function majComposante(select)
{
	var structure = document.getElementById("structure1").value;
	var xhr = getXMLHttpRequest();
	var params = "structure="+structure;//+"&type_ip="+codStatut;
	xhr.open("POST", "xml_ajax_composante.php", true);
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0)) 
		{
			readListeComposantes(xhr.responseXML);			
		}
	    /*else
		if (xhr.readyState < 4) 
		{			
			document.getElementById("composantecod1").innerHTML = "";
			ajouteLigneSelect (document.getElementById("composantecod1"), "Chargement en cours...", "");					
		}		*/
	}	
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xhr.send(params);
}

/* Recupere et cree la liste des composantes a partir de la reponse xml */
function readListeComposantes(data) 
{
	var composantes = data.getElementsByTagName("item");
	var listeComposantes = document.getElementById("composantecod1");
	if (composantes.length > 0)
	{
		listeComposantes.innerHTML = "";
		var listeRes = ajouteLigneSelect (listeComposantes, "", "");
		for (var i=0, c=composantes.length; i<c; i++) 
		{
			listeRes = ajouteLigneSelect (listeRes, composantes[i].getAttribute("libelle"), composantes[i].getAttribute("id"));
		}
	}
}
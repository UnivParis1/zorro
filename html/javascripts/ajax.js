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
function ajouteLigneSelect (select, text, val, selected=false)
{
	var oOption, oInner;
	oOption = document.createElement("option");
	oInner  = document.createTextNode(text);
	oOption.value = val;
	if (selected)
	{
		oOption.setAttribute("selected", true);
	}
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
		if (listeComposantes !== null)
		{
			listeComposantes.innerHTML = "";
			var oInput, oDiv;
			oDiv = document.getElementById("composantecod_div");
			//alert(infos[i].getAttribute("id")+"_div");
			if (oDiv != null)
			{
				//oDiv.setAttribute("style", "display:block;");
				oInput = document.getElementById("affichecomposante");
				if (oInput == null)
				{
					oInput = document.createElement("input");
					oInput.setAttribute("id", "affichecomposante");
					oInput.setAttribute("type", "text");
					oInput.setAttribute("readonly", true);
					oInput.setAttribute("value", composantes[0].getAttribute("libelle"));
					oDiv.appendChild(oInput);
				}
				else
				{
					oInput.setAttribute("value", composantes[0].getAttribute("libelle"));
				}
				listeComposantes.setAttribute("value", composantes[0].getAttribute("id"));
				listeComposantes.setAttribute("type", "hidden");
				listeComposantes.setAttribute("readonly", true);
			}
			majDomaine(listeComposantes);
			majMention(listeComposantes);
			majSpecialite(listeComposantes);
		}
	}
}

/* Met a jour le domaine */
function majDomaine(select,valeur='')
{
	//alert("maj domaine "+valeur);
	var cod = document.getElementById("composantecod1").value;
	var id = document.getElementById("selectarrete").value;
	var xhr = getXMLHttpRequest();
	if (valeur != '')
	{
		var params = "cod_cmp_dom="+cod+"&idmodel="+id+"&iddecree="+valeur;
		//alert(params);
	}
	else
	{
		var params = "cod_cmp_dom="+cod+"&idmodel="+id;
		//alert(params);
	}
	//alert("cod_cmp_dom="+cod+"&idmodel="+id);
	xhr.open("POST", "xml_ajax_composante.php", true);
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0))
		{
			readListDomaines(xhr.responseXML);
			majMention('', valeur);
			majSpecialite('', valeur);
		}
	}
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xhr.send(params);
}


/* Recupere et cree la liste des domaines lies a la composante a partir de la reponse xml */
function readListDomaines(data)
{
	var domaines = data.getElementsByTagName("item");
	var listeDomaines = document.getElementById("domaine1");
	var domaine_div = document.getElementById("domaine_div");
	if (domaine_div !== null)
	{
		domaine_div.setAttribute("style", "display:block;");
		listeDomaines.innerHTML = "";
		if (domaines.length > 0)
		{
			if (domaines.length == 1)
			{
				var listeRes = ajouteLigneSelect (listeDomaines, "", "");
			}
			else
			{
				var listeRes = ajouteLigneSelect (listeDomaines, "", "", true);
			}
			for (var i=0, c=domaines.length; i<c; i++)
			{
				if (domaines[i].getAttribute("selected") == "true")
				{
					selected = true;
				}
				else
				{
					selected = false;
				}
				//alert(domaines[i].getAttribute("id")+' '+domaines[i].getAttribute("libelle"));
				listeRes = ajouteLigneSelect (listeRes, domaines[i].getAttribute("libelle"), domaines[i].getAttribute("id"), selected);
			}
		}
	}
}

/* Met a jour le domaine */
function majMention(select, valeur='')
{
	//alert("maj mention "+valeur);
	var cod = document.getElementById("composantecod1");
	if (cod === null)
	{
		cod = '';
	}
	else
	{
		cod = cod.value;
	}
	var id = document.getElementById("selectarrete").value;
	var dom = document.getElementById("domaine1");
	if (dom === null)
	{
		dom = '';
	}
	else
	{
		dom = dom.value;
	}
	var xhr = getXMLHttpRequest();
	if (valeur != '')
	{
		var params = "cod_cmp_dom="+cod+"&idmodel="+id+"&coddfd="+dom+"&iddecree="+valeur;
		//alert(params);
	}
	else
	{
		var params = "cod_cmp_dom="+cod+"&idmodel="+id+"&coddfd="+dom;
		//alert(params);
	}
	//alert("cod_cmp_dom="+cod+"&idmodel="+id+"&coddfd="+dom);
	xhr.open("POST", "xml_ajax_composante.php", true);
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0))
		{
			readListMentions(xhr.responseXML);
			majSpecialite('', valeur);
		}
	}
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xhr.send(params);
}


/* Recupere et cree la liste des mentions liees a la composante et au domaine a partir de la reponse xml */
function readListMentions(data)
{
	var mentions = data.getElementsByTagName("item");
	var listementions = document.getElementById("mention1");
	var mention_div = document.getElementById("mention_div");
	mention_div.setAttribute("style", "display:block;");
	listementions.innerHTML = "";
	if (mentions.length > 0)
	{
		if (mentions.length == 1)
		{
			var listeRes = ajouteLigneSelect (listementions, "", "");
		}
		else
		{
			var listeRes = ajouteLigneSelect (listementions, "", "", true);
		}
		for (var i=0, c=mentions.length; i<c; i++)
		{
			if (mentions[i].getAttribute("selected") == "true")
			{
				selected = true;
			}
			else
			{
				selected = false;
			}
			//alert(mentions[i].getAttribute("id")+' '+mentions[i].getAttribute("libelle"));
			listeRes = ajouteLigneSelect (listeRes, mentions[i].getAttribute("libelle"), mentions[i].getAttribute("id"), selected);
		}
	}
}

/* Met a jour la specialite */
function majSpecialite(select, valeur='')
{
	//alert("maj mention "+valeur);
	var cod = document.getElementById("composantecod1");
	if (cod === null)
	{
		cod = '';
	}
	else
	{
		cod = cod.value;
	}
	var id = document.getElementById("selectarrete").value;
	var mention = document.getElementById("mention1");
	if (mention === null)
	{
		mention = '';
	}
	else
	{
		mention = mention.value;
	}
	var xhr = getXMLHttpRequest();
	if (valeur != '')
	{
		var params = "cod_cmp_dom="+cod+"&idmodel="+id+"&mention="+mention+"&iddecree="+valeur;
		//alert(params);
	}
	else
	{
		var params = "cod_cmp_dom="+cod+"&idmodel="+id+"&mention="+mention;
		//alert(params);
	}
	//alert("cod_cmp_dom="+cod+"&idmodel="+id+"&coddfd="+dom);
	xhr.open("POST", "xml_ajax_composante.php", true);
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0))
		{
			readListSpecialites(xhr.responseXML);
		}
	}
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xhr.send(params);
}


/* Recupere et cree la liste des specialites liees a la composante et a la mention a partir de la reponse xml */
function readListSpecialites(data)
{
	var specialites = data.getElementsByTagName("item");
	var listespecialites = document.getElementById("parcours1");
	var specialite_div = document.getElementById("parcours_div");
	if (specialite_div !== null)
	{
		specialite_div.setAttribute("style", "display:block;");
		listespecialites.innerHTML = "";
		if (specialites.length > 0)
		{
			if (specialites.length == 1)
			{
				var listeRes = ajouteLigneSelect (listespecialites, "", "");
			}
			else
			{
				var listeRes = ajouteLigneSelect (listespecialites, "", "", true);
			}
			for (var i=0, c=specialites.length; i<c; i++)
			{
				if (specialites[i].getAttribute("selected") == "true")
				{
					selected = true;
				}
				else
				{
					selected = false;
				}
				//alert(mentions[i].getAttribute("id")+' '+mentions[i].getAttribute("libelle"));
				listeRes = ajouteLigneSelect (listeRes, specialites[i].getAttribute("libelle"), specialites[i].getAttribute("id"), selected);
			}
		}
	}
}


/* Met a jour l'etudiant' */
function majEtudiant(select)
{
	var etu = document.getElementById("rechetu1").value;
	var xhr = getXMLHttpRequest();
	var params = "uid="+etu;
	xhr.open("POST", "xml_ajax_etudiant.php", true);
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0))
		{
			readInfosEtu(xhr.responseXML);
		}
	}
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xhr.send(params);
}


/* Recupere et cree la liste des infos de l etudiant a partir de la reponse xml */
function readInfosEtu(data)
{
	var infos = data.getElementsByTagName("item");
	//var listeinfos = document.getElementById("rechetu1_ref");
	//if (listeinfos.length > 0)
	//{
	//	listeinfos.innerHTML = "";
	//	var listeRes = ajouteLigneSelect (listeinfos, "", "");
		for (var i=0, c=infos.length; i<c; i++)
		{
			var elem = document.getElementById(infos[i].getAttribute("id")+"1");
			if (elem == null)
			{
				var oInput, oDiv;
				oDiv = document.getElementById(infos[i].getAttribute("id")+"_div");
				//alert(infos[i].getAttribute("id")+"_div");
				if (oDiv != null)
				{
					oDiv.setAttribute("style", "display:block;");
					oInput = document.createElement("input");
					//oInner  = document.createTextNode(text);
					oInput.setAttribute("id", infos[i].getAttribute("id")+"1");
					oInput.setAttribute("name", infos[i].getAttribute("id")+"1");
					oInput.setAttribute("type", "text");
					oInput.setAttribute("value", infos[i].getAttribute("libelle"));
					oInput.setAttribute("readonly", true);
					//oInput.appendChild(oInner);
					oDiv.appendChild(oInput);
				}
			}
			else
			{
				elem.setAttribute("value", infos[i].getAttribute("libelle"));
			}
			//listeRes = ajouteLigneSelect (listeRes, infos[i].getAttribute("libelle"), infos[i].getAttribute("id"));
		}
	//}
}

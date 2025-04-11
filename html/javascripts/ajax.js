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
			majMention2(listeComposantes);
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
			majCodeMention();
			majMention2('', valeur);
			majCodeMention2();
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

/* Met a jour la mention */
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
			majMention2('', valeur);
			majCodeMention();
			majCodeMention2();
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
	var listecodes = document.getElementById("codemention1");
	var mention_div = document.getElementById("codemention_div");
	mention_div.setAttribute("style", "display:none;");
	listementions.innerHTML = "";
	if (mentions.length > 0)
	{
		if (listecodes == null)
		{
			var oSelect;
			oSelect = document.createElement("select");
			oSelect.setAttribute("id", "codemention1");
			oSelect.setAttribute("name", "codemention1");
			mention_div.appendChild(oSelect);
			listecodes = document.getElementById("codemention1");
		}
		listecodes.innerHTML = "";
		if (mentions.length == 1)
		{
			var listeRes = ajouteLigneSelect (listementions, "", "");
			var listeCod = ajouteLigneSelect (listecodes, "", "");
		}
		else
		{
			var listeRes = ajouteLigneSelect (listementions, "", "", true);
			var listeCod = ajouteLigneSelect (listecodes, "", "", true);
		}
		for (var i=0, c=mentions.length; i<c; i++)
		{
			if (mentions[i].getAttribute("selected") == "true")
			{
				var selected = true;
				var elem = document.getElementById("codemention1");
				elem.setAttribute("value", mentions[i].getAttribute("code"));
				elem.setAttribute("name", "codemention1");
			}
			else
			{
				var selected = false;
			}
			//alert(mentions[i].getAttribute("id")+' '+mentions[i].getAttribute("libelle"));
			listeRes = ajouteLigneSelect (listeRes, mentions[i].getAttribute("libelle"), mentions[i].getAttribute("id"), selected);
			listeCod = ajouteLigneSelect (listeCod, mentions[i].getAttribute("libelle"), mentions[i].getAttribute("code"), selected);
		}
	}
}

function majCodeMention(valeur='')
{
	var code = document.getElementById("codemention1");
	if (valeur !='')
	{
		var codementiondiv = document.getElementById("codemention_div");
		codementiondiv.setAttribute("style", "display:none;");
		if (code == null)
			{
				var oSelect;
				oSelect = document.createElement("input");
				oSelect.setAttribute("id", "codemention1");
				oSelect.setAttribute("name", "codemention1");
				codementiondiv.appendChild(oSelect);
			}
			code = document.getElementById("codemention1");
			code.setAttribute("value", valeur);
	} else {
		var etp = document.getElementById("mention1");
		if (code !== null && etp !== null)
		{
			var options = code.options;
			var mod = false;
			for (var i=1, c=options.length; i<c; i++)
			{
				if (i == etp.selectedIndex)
				{
					options[i].setAttribute("selected", true);
					code.setAttribute("value", options[i].value);
					mod = true;
				}
				else
				{
					options[i].removeAttribute("selected");
				}
			}
			if (mod == false)
			{
				code.removeAttribute("value");
			}
		}
	}
}

function majCodeMention2(valeur='')
{
	var code = document.getElementById("codemention21");
	if (valeur !='')
	{
		var codementiondiv = document.getElementById("codemention2_div");
		codementiondiv.setAttribute("style", "display:none;");
		if (code == null)
			{
				var oSelect;
				oSelect = document.createElement("input");
				oSelect.setAttribute("id", "codemention21");
				oSelect.setAttribute("name", "codemention21");
				codementiondiv.appendChild(oSelect);
			}
			code = document.getElementById("codemention21");
			code.setAttribute("value", valeur);
	} else {
		var etp = document.getElementById("mention21");
		if (code !== null && etp !== null)
		{
			var options = code.options;
			var mod = false;
			for (var i=1, c=options.length; i<c; i++)
			{
				if (i == etp.selectedIndex)
				{
					options[i].setAttribute("selected", true);
					code.setAttribute("value", options[i].value);
					mod = true;
				}
				else
				{
					options[i].removeAttribute("selected");
				}
			}
			if (mod == false)
			{
				code.removeAttribute("value");
			}
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
	if (listespecialites !== null)
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

/* Met a jour la 2e mention */
function majMention2(select, valeur='')
{
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
	var etp = document.getElementById("mention1");
	if (etp === null)
	{
		etp = '';
	}
	else
	{
		var code = document.getElementById("codemention1");
		if (code !== null)
		{
			code.selectedIndex = etp.selectedIndex;
		}
		etp = etp.value;
	}
	var xhr = getXMLHttpRequest();
	if (valeur != '')
	{
		var params = "cod_cmp_dom="+cod+"&idmodel="+id+"&coddfd="+dom+"&iddecree="+valeur+"&etp="+etp;
	}
	else
	{
		var params = "cod_cmp_dom="+cod+"&idmodel="+id+"&coddfd="+dom+"&etp="+etp;
	}
	xhr.open("POST", "xml_ajax_composante.php", true);
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0))
		{
			readListMentions2(xhr.responseXML);
		}
	}
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xhr.send(params);
}


/* Recupere et cree la liste des mentions liees a la composante et au domaine a partir de la reponse xml */
function readListMentions2(data)
{
	var mentions = data.getElementsByTagName("item");
	var listementions = document.getElementById("mention21");
	var mention_div = document.getElementById("mention2_div");
	var listecodes = document.getElementById("codemention21");
	var mention_div = document.getElementById("codemention2_div");
	mention_div.setAttribute("style", "display:none;");
	if (mention_div !== null)
	{
		if (listecodes == null)
		{
			var oSelect;
			oSelect = document.createElement("select");
			oSelect.setAttribute("id", "codemention21");
			oSelect.setAttribute("name", "codemention21");
			mention_div.appendChild(oSelect);
			listecodes = document.getElementById("codemention21");
		}
		listecodes.innerHTML = "";
		listementions.innerHTML = "";
		if (mentions.length > 0)
		{
			if (mentions.length == 1)
			{
				var listeRes = ajouteLigneSelect (listementions, "", "");
				var listeCod = ajouteLigneSelect (listecodes, "", "");
			}
			else
			{
				var listeRes = ajouteLigneSelect (listementions, "", "", true);
				var listeCod = ajouteLigneSelect (listecodes, "", "", true);
			}
			for (var i=0, c=mentions.length; i<c; i++)
			{
				if (mentions[i].getAttribute("selected") == "true")
				{
					var selected = true;
				}
				else
				{
					var selected = false;
				}
				listeRes = ajouteLigneSelect (listeRes, mentions[i].getAttribute("libelle"), mentions[i].getAttribute("id"), selected);
				listeCod = ajouteLigneSelect (listeCod, mentions[i].getAttribute("libelle"), mentions[i].getAttribute("code"), selected);
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

// infinite scroll manage_decree
var scrollLoad = true;
$(window).scroll(function() {
    if(scrollLoad && $(window).scrollTop() + $(window).height() > $(document).height()-100) {
        var nbaff = parseInt(document.getElementById('nbaff').value);
		var userid = document.getElementById('userid').value;
		var orderby = document.getElementById('orderby').value;
		var desc = document.getElementById('desc').value;
		var status = document.getElementById('status').value;
		var idmodel = document.getElementById('idmodel').value;
		var contenu = document.getElementById('contenu').value;
		var findnum = document.getElementById('findnum').value;
		var year = document.getElementById('findannee').value;
		if (document.getElementById('allcomp').checked == true) {
			var allcomp = document.getElementById('allcomp').value;
		} else {
			var allcomp = '';
		}
		loadMore(nbaff, userid, orderby, desc, idmodel, status, contenu, findnum, year, allcomp);
		scrollLoad = false;
    }
});

function loadMore(last_id, userid, orderby, desc, idmodel, status, contenu, num, year, allcomp){
  $.ajax({
      url: 'load-more.php?last_id=' + last_id + '&userid=' + userid + '&orderby=' + orderby + '&desc=' + desc + '&status=' + status + '&idmodel=' + idmodel + '&contenu=' + contenu + '&number=' + num + '&year=' + year + '&allcomp=' + allcomp ,
      type: "get",
      beforeSend: function(){
          $('#ajax-load').show();
      }
  }).done(function(data){
      $('#ajax-load').hide();
      $("#post-data").append(data);
	  var nbaff = document.getElementById("nbaff");
	  nbaff.setAttribute("value", parseInt(nbaff.value)+20);
	  scrollLoad = true;
  }).fail(function(jqXHR, ajaxOptions, thrownError){
      alert('server not responding...');
  });
}

function refreshtab(){
	$("#post-data").empty();
	scrollLoad = false;
	var userid = document.getElementById('userid').value;
	var orderby = document.getElementById('orderby').value;
	var desc = document.getElementById('desc').value;
	var nbaff = parseInt(document.getElementById("nbaff").value);
	var status = document.getElementById('status').value;
	var idmodel = document.getElementById('idmodel').value;
	var contenu = document.getElementById('contenu').value;
	var findnum = document.getElementById('findnum').value;
	var year = document.getElementById('findannee').value;
	if (document.getElementById('allcomp').checked == true) {
		var allcomp = document.getElementById('allcomp').value;
	} else {
		var allcomp = '';
	}
	loadMore(nbaff, userid, orderby, desc, idmodel, status, contenu, findnum, year, allcomp);
	scrollLoad = false;
}
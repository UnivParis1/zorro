<?php 
    require_once ('CAS.php');
    include './include/casconnection.php';
    require_once ('./include/fonctions.php');
    require_once ('./class/decree.php');
    require_once ('./include/dbconnection.php');
    require_once ('./class/user.php');
    require_once ('./class/model.php');
    require_once ('./class/reference.php');
    require_once ('./class/ldap.php');
	
    
    /*if (isset($_POST["userid"]))
        $userid = $_POST["userid"];
    else
        $userid = null;*/
    $ref = new reference($dbcon, $rdbApo);
    $userid = $ref->getUserUid();
    //echo $_SESSION['uid'];
    if (is_null($userid) or ($userid == "")) 
    {
        elog("Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        header('Location: index.php');
        exit();
    }
    
    if (isset($_GET['id']))
    {
    	$mode = 'modif';
    	$mod_decree_id = intval($_GET['id']);
    }
    elseif (isset($_POST['mod_id']))
    {    	
    	$mode = 'modif';
    	$mod_decree_id = intval($_POST['mod_id']);
    }
    else 
    {
    	$mode = 'create';
    }
    if (isset($_POST['arrete'])) 
    {
    	$post_arrete = $_POST['arrete'];
    }
    if (isset($_POST['selectarrete']))
    {
    	$post_selectarrete = $_POST['selectarrete'];
    }
    if (isset($_POST['valide']))
    {
    	$post_valide = $_POST['valide'];
    }
    elseif (isset($_POST['duplique']))
    {
    	$post_duplique = $_POST['duplique'];
    }
    
    /*if (isset($_POST['mod_year']) && isset($_POST['mod_num']))
    {
    	$mode = 'modif';
    	$mod_year = intval($_POST['mod_year']);
    	$mod_num = intval($_POST['mod_num']);
    }*/

    //if ((isset($_POST[$modelfield['name'].$i])))
    //{
    	
    //}
    // Récupération des modeles auxquels à accès l'utilisateur
    $user = new user($dbcon, $userid);
    if ($user->isSuperAdmin())
    {
    	// donner accès à tous les modèles
    	$models = $ref->getListModel();
    	foreach ($models as $idmodel => $infos)
    	{
    		$model = new model($dbcon, $idmodel);
    		$listModels[] = $model->getModelInfo();
    	}
    }
    else 
    {
    	$roles = $user->getGroupeRoles($_SESSION['groupes']); // roles actifs de l'utilisateur
    	//print_r2($_SESSION['groupes']);
	    $listModels = array();
	    foreach ($roles as $role)
	    {
	    	$model = new model($dbcon, $role['idmodel']);
	    	$listModels[] = $model->getModelInfo();
	    }
    }
    
    require ("include/menu.php");
    if ($mode == 'modif') 
    {
    	// RÉCUPÉRATION DU DOCUMENT ET DE SES PARAMÈTRES
		$mod_decree = new decree($dbcon, null, null, $mod_decree_id);
		$mod_num = $mod_decree->getNumber();
		$mod_year = $mod_decree->getYear();
		if ($mod_decree_id != NULL)
		{
			$mod_select_decree = $mod_decree->getDecree();
			$mod_decree_fields = $mod_decree->getFields();
		}
	}
	
	if (isset($_POST['sign']) && isset($mod_decree) && $mod_decree->getStatus() == 'b') {
		$ldap = new ldap();
		elog('on est dans la signature...');
		if (isset($_POST["composantecod1"]))
		{
			$supannCodeEntite = $ldap->getSupannCodeEntiteFromAPO($_POST["composantecod1"]);
			if ($supannCodeEntite != NULL)
			{
				$responsables = $ldap->getStructureResp($supannCodeEntite);
				$filename = $mod_decree->getFileName();
				if ($filename != "" && file_exists(PDF_PATH.$filename))
				{
					if (sizeof($responsables) > 0)
					{
						$curl = curl_init();
						$params = array
						(
								'createByEppn' => "system",
								//'targetEmails' => $ref->getUserMail()
								//'targetUrls' => array()
						);
						elog("mail du créateur : ".$ref->getUserMail());
						$params['recipientEmails'] = '';
						foreach ($responsables as $responsable)
						{
							elog("mail du responsable : ".$responsable['mail']);
							//$params['recipientEmails'] .= "1*".$responsable['mail'].",";
						}
						$params['recipientEmails'] = "1*elodie.briere@univ-paris1.fr,2*elodie.briere@univ-paris1.fr,";//,"1*canica.sar@univ-paris1.fr","2*canica.sar@univ-paris1.fr");
						$params['recipientEmails'] = rtrim($params['recipientEmails'], ',');
						elog($params['recipientEmails']);
						$params['targetEmails'] = "elodie.briere@univ-paris1.fr";
						$params['targetUrls'] = array(TARGET_URL."arreteMaitrise");
						$params['multipartFiles'] = curl_file_create(realpath(APPLI_PATH.PDF_PATH.$filename), "application/pdf", $filename);
						$opts = [
								CURLOPT_URL => ESIGNATURE_CURLOPT_URL."286037".ESIGNATURE_CURLOPT_URL2,
								CURLOPT_CUSTOMREQUEST => "POST",
								CURLOPT_VERBOSE => true,
								CURLOPT_POST => true,
								CURLOPT_POSTFIELDS => $params,
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_SSL_VERIFYPEER => false
						];
						curl_setopt_array($curl, $opts);
						//curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
						$json = curl_exec($curl);
						//print_r2($json);
						$info = curl_getinfo($curl);
						//echo "code: ${info['http_code']}";
						
						//print_r2($info);
						$error = curl_error ($curl);
						curl_close($curl);
						if ($error != "")
						{
							elog( "Erreur Curl = " . $error . "<br><br>");
						}
						//echo "<br>" . print_r($json,true) . "<br>";
						$id = json_decode($json, true);
						elog(var_export($opts, true));
						elog(" -- RETOUR ESIGNATURE CREATION ARRETE -- " . var_export($id, true));
						if (is_int($id))
						{
							$mod_decree->setIdEsignature($id);
							$message = "La demande a été envoyée à eSignature.";
						}
						else 
						{
							"Echec de création dans eSignature.";
						}
					}
					else
					{
						elog("pas de responsable de structure.");
					}
				}
				else 
				{
					elog ("fichier pdf absent ".PDF_PATH.$filename);
				}
			}
		}
		else
		{
			elog("pas de code composante.");
		}
	}
    elseif (isset($post_selectarrete) && $post_selectarrete != '' || isset($mod_select_decree))
    {
    	$selectarrete = isset($mod_select_decree) ? $mod_select_decree['idmodel'] : $post_selectarrete;
    	$modelselected = new model($dbcon, $selectarrete);
    	$urlselected = $modelselected->getfile();
    	$modelfields = $modelselected->getModelFields();
    	
    	//if (isset($_POST['user'])) echo "<br>".htmlspecialchars($_POST['user'])."<br>";
    	
    	$year = date('Y');
    	if (isset($_POST['annee1']))
    	{
    		$year = intval($_POST['annee1']);
    	}
    	$numero_dispo = $ref->getNumDispo($year);
    	
    	$decreefields = array();
    	if (isset($post_valide)||isset($post_duplique))
    	{
    		// Si le document est en mode modif et qu'il n'est pas validé dans esignature on supprime le numero d'arrêté et on crée un nouveau
    		if (isset($mod_year) && isset($mod_num) && isset($post_valide) && $post_valide == "Remplacer")
    		{
    			$mod_decree = new decree($dbcon, null, null, $mod_decree_id);
    			$mod_decree_infos = $mod_decree->getDecree();
    			if ($mod_decree_infos != NULL && $mod_decree->getStatus() != 'v')
    			{
    				elog("Suppression du numero...");
    				$mod_decree->unsetNumber($user->getId());
    				$numero_dispo = $ref->getNumDispo($year);
    				// TODO : Supprimer le PDF qui avait été créé
    			}
    			// TODO : Si l'année n'a pas changé, conserver le numéro de l'arrêté
    		}
    		$idmodel_field_numero = $modelselected->getNumeroId();
    		$decreefields[] = array('idmodel_field' => $idmodel_field_numero, 'value' => $numero_dispo);
    		foreach ($modelfields as $modelfield)
    		{
    			if ($modelfield['auto'] == 'N')
    			{
    				if ($modelfield['number'] == '+')
    				{
    					//print_r2($_POST);
    					$valeurs = isset($_POST[$modelfield['name']]) ? $_POST[$modelfield['name']] : array();
    					foreach($valeurs as $valeur)
    					{
    						$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => htmlspecialchars($valeur));
    					}
    					if (isset($_POST[$modelfield['name']."1"]) && $_POST[$modelfield['name']."1"] != '')
    						$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => htmlspecialchars($_POST[$modelfield['name']."1"]));
    				}
    				else
    				{
    					for($i = 1; $i <= $modelfield['number']; $i++)
    					{
    						//echo $modelfield['name'].$i." : ".$_POST[$modelfield['name'].$i]."<br>";
    						if (isset($_POST[$modelfield['name'].$i]) && $_POST[$modelfield['name'].$i] != '')
    						{
    							$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => htmlspecialchars($_POST[$modelfield['name'].$i]));
    						}
    					}
    				}
    			}
    		}
    		
    		$idmodel = $post_selectarrete;
    		$decree = new decree($dbcon, $year, $numero_dispo);
    		$structure = htmlspecialchars($_POST['composantecod1']);
    		$decree->save($user->getid(), $idmodel, $structure);
    		$decree->setFields($decreefields);
    		$modelselected = new model($dbcon, $idmodel);
    		
    		$modelfile = new ZipArchive();
    		if (file_exists("./models/".$modelselected->getfile()))
    		{
    			$fieldstoinsert = $decree->getFields();
    			// echo "fieldstoinsert <br><br>";print_r2($fieldstoinsert);
    			$modelfields = $modelselected->getModelFields();
    			//echo "<br>modelfields <br><br>"; print_r2($modelfields);
    			$modelfieldsarrange = array_column($modelfields, 'idmodel_field', 'name');
    			$modelfieldstype = array_column($modelfields, 'datatype', 'idmodel_field');
    			//echo "<br>modelfieldsarrange <br><br>"; print_r2($modelfieldsarrange);
    			//echo "<br>modelfieldstype <br><br>"; print_r2($modelfieldstype);
    			// copie du modele pour l'arrêté
    			$odtfilename = $decree->getFileName("odt");
    			copy("./models/".$modelselected->getfile(), PDF_PATH.$odtfilename);
    			// ouverture du modele pour l'arrêté
    			$modelfile->open(PDF_PATH.$odtfilename);
    			// extraction du content.xml dans le dossier temporaire pour l'arrêté
    			$modelfile->extractTo(PDF_PATH.$year.'_'.$numero_dispo."/", array('content.xml'));
    			// ouverture du content.xml extrait
    			$content = fopen(PDF_PATH.$year.'_'.$numero_dispo."/content.xml", 'r+');
    			// lecture du content.xml extrait
    			$contenu = fread($content, filesize(PDF_PATH.$year.'_'.$numero_dispo."/content.xml"));
    			$doc = new DOMDocument('1.0', 'utf-8');
    			$doc->preserveWhiteSpace = false;
    			$doc->formatOutput = true;
    			$doc->loadXML($contenu);
    			$x = $doc->documentElement;
    			$body = $x->getElementsByTagName('body')->item(0);
    			// echo "BODY 1 : <br>"; print_r2($body);
    			foreach ($fieldstoinsert as $idmodel_field => $field)
    			{
    				// dupliquer les champs multiples
    				$nbChamps = sizeof($field);
    				$champ = array_keys($modelfieldsarrange, $idmodel_field)[0];
    				if ($nbChamps > 1)
    				{
    					// echo "Champs à multiplier : ";print_r2($field);
    					// trouver le champs dans le xml
    					$noeudcourant = $body; // le dernier noeud contenant le champ
    					$noeudpere = $body; // le noeud où raccrocher le clone du champ
    					$noeudadupliquer = $body; // le noeud à cloner
    					$positiondunoeudadupliquer = 0; // la position où raccrocher le clone du champ sous le noeud père
    					while (strpos($noeudcourant->textContent, '$$$'.$champ.'$$$') !== false)
    					{
    						if ($noeudcourant->hasChildNodes())
    						{
    							if ($noeudcourant->nextSibling != null || $noeudcourant->previousSibling != null)
    							{
    								$noeudadupliquer = $noeudcourant;
    							}
    							if ($noeudcourant->childNodes->count() > 1)
    							{
    								$noeudpere = $noeudcourant;
    								$positiondunoeudadupliquer = 0;
    							}
    							foreach($noeudcourant->childNodes as $node)
    							{
    								if (strpos($node->textContent, '$$$'.$champ.'$$$') !== false)
    								{
    									$noeudcourant = $node;
    									// echo "Noeud contenant le champ : <br>"; print_r2($node);
    									break;
    								}
    								if ($noeudpere == $noeudcourant)
    								{
    									$positiondunoeudadupliquer++;
    								}
    							}
    						}
    						else
    						{
    							// echo "Dernier noeud contenant le champ : <br>"; print_r2($node);
    							break;
    						}
    					}
    					// echo "Noeud à dupliquer  : <br>"; print_r2($noeudadupliquer);
    					// echo "Noeud père où raccrocher la copie : <br>"; print_r2($noeudpere);
    					// echo "Position où raccrocher sous le père : <br>"; print_r2($positiondunoeudadupliquer);
    					// echo "Noeud à la position où raccrocher sous le père : <br>"; print_r2($noeudpere->childNodes->item($positiondunoeudadupliquer));
    					for ($i = 1; $i <= $nbChamps; $i++)
    					{
    						// dupliquer le noeud
    						$clone = $noeudadupliquer->cloneNode(true);
    						// insérer le noeud
    						$noeudpere->insertBefore($clone, $noeudpere->childNodes->item($positiondunoeudadupliquer));
    						// echo "Noeud père après $i ème insert : <br>"; print_r2($noeudpere);
    					}
    				}
    			}
    			// print_r2($body);// TODO : Affichage de la création réussie
    			// enregistrement du xml modifié
    			$doc->save(PDF_PATH.$year.'_'.$numero_dispo."/content2.xml");
    			fclose($content);
    			$content = fopen(PDF_PATH.$year.'_'.$numero_dispo."/content2.xml", 'r+');
    			// lecture du content2.xml extrait
    			$contenu = fread($content, filesize(PDF_PATH.$year.'_'.$numero_dispo."/content2.xml"));
    			// copie du contenu extrait
    			$contenu2 = $contenu;
    			//echo "<br>contenu <br><br>"; print_r2($contenu);echo "<br><br>";
    			$position1 = strpos($contenu, '$$$'); // position de la balise de début d'un champ paramétrable
    			$position2 = strpos($contenu, '$$$', $position1+1); // position de la balise de fin d'un champ paramétrable
    			//print_r2(strlen($contenu));
    			$nb_field = array();
    			$champsamodif = array();
    			while ($position1 < strlen($contenu) && substr($contenu, $position1 + 3, $position2 - $position1 - 3) && $position1 !== false && $position2 !== false)
    			{
    				$field = substr($contenu, $position1 + 3, $position2 - $position1 - 3); // le nom du champ est entre les balises
    				if (!key_exists($field, $nb_field))
    				{
    					$nb_field[$field] = 0;
    				}
    				if (key_exists($field, $modelfieldsarrange) && key_exists($modelfieldsarrange[$field], $fieldstoinsert) && key_exists($nb_field[$field], $fieldstoinsert[$modelfieldsarrange[$field]]))
    				{
    					// echo "($position1 - $position2) à remplacer : $$$".$field."$$$ par : ".$fieldstoinsert[$modelfieldsarrange[$field]][$nb_field[$field]]['value']."<br>";
    					if ($modelfieldstype[$modelfieldsarrange[$field]] == 'user')
    					{
    						$champsamodif[] = array("valeur" => "- ".$fieldstoinsert[$modelfieldsarrange[$field]][$nb_field[$field]]['value'], "position" => $position1, "longueur" => (strlen($field)+6));
    					}
    					else
    					{
    						$champsamodif[] = array("valeur" => $fieldstoinsert[$modelfieldsarrange[$field]][$nb_field[$field]]['value'], "position" => $position1, "longueur" => (strlen($field)+6));
    					}
    				}
    				else
    				{
    					//echo "($position1 - $position2) à remplacer : $$$".$field."$$$ par : vide <br>";
    					$champsamodif[] = array("valeur" => '', "position" => $position1, "longueur" => (strlen($field)+6));
    				}
    				$nb_field[$field] += 1;
    				$position1 = strpos($contenu, '$$$', $position2 + 4);
    				$position2 = strpos($contenu, '$$$', $position1 + 1);
    			}
    			fclose($content);
    			$content = fopen(PDF_PATH.$year.'_'.$numero_dispo."/content2.xml", 'w');
    			$champsamodiffromlast = array_reverse($champsamodif);
    			// remplacement des champs à partir de la fin du fichier
    			foreach ($champsamodiffromlast as $champ)
    			{
    				$contenu2 = substr_replace($contenu2, $champ['valeur'], $champ['position'], $champ['longueur']);
    			}
    			// écriture du contenu modifié dans le fichier
    			fwrite($content, $contenu2);
    			// Ajout du fichier dans le document
    			$modelfile->addFile(PDF_PATH.$year.'_'.$numero_dispo."/content2.xml", 'content.xml');
    			//print_r2($fieldstoinsert);
    			$modelfile->close();
    			
    			// CONVERSION EN PDF
    			$descriptorspec = array(
    					0 => array("pipe", "r"),  // stdin
    					1 => array("pipe", "w"),  // stdout
    					2 => array("pipe", "w"),  // stderr
    			);
    			$process = proc_open("unoconv --doctype=document --format=pdf ".PDF_PATH.$decree->getFileName("odt"), $descriptorspec, $pipes);
    			$stdout = stream_get_contents($pipes[1]);
    			fclose($pipes[1]);
    			
    			$stderr = stream_get_contents($pipes[2]);
    			fclose($pipes[2]);
    			if ($stdout != "")
    			{
    				elog( "stdout : \n");
    				elog($stdout);
    				elog( "La création du document PDF a échoué. <br>");
    			}
    			if ($stderr != "")
    			{
    				elog( "stderr :\n");
    				elog($stderr);
    				elog( "La création du document PDF a échoué. <br>");
    			}
    			?>
		<?php }
		if ($mode == 'create' || (isset($post_valide) && $post_valide == "Remplacer") || isset($post_duplique))
			{
				$mod_num = $numero_dispo;
				$mod_year = $year;
				$mod_decree_id = $decree->getId();
				$mode = 'modif';
			}
		}
		?>
	
	<br><br>
	<?php } else {
	} ?>
	
<?php // ------------------------------------------------------- AFFICHAGE ------------------------------------------------------- ?>

<script>
function ajouterValeur(divname, value='') 
{
	var table = document.getElementById("table_"+divname);
	var row = table.insertRow(-1);
	var rowindex = row.rowIndex;
	var nameindex = parseInt(rowindex+2,10);
	var cell0 = row.insertCell(0);
	var name = document.createElement("input");
	name.setAttribute("type", "text");	
	name.setAttribute("id", divname+"[]");
	name.setAttribute("name", divname+"[]");
	if(value != '') {
		name.setAttribute("value", value);
	} else {	
		name.setAttribute("value", document.getElementById(divname+"1").nextSibling.innerText);
	}
	name.setAttribute("readonly", true);
	document.getElementById(divname+"1").nextSibling.innerText = '';
	document.getElementById(divname+"1").value = '';
	cell0.appendChild(name);
	var cell1 = row.insertCell(1);
	row.setAttribute("id", "row"+divname+nameindex);
	var moins = document.createElement("button");
	moins.innerText = "-";
	moins.setAttribute("onclick", "return supprimerValeur('moins"+divname+nameindex+"');");
	cell1.setAttribute("id", "moins"+divname+nameindex);
	cell1.appendChild(moins);
	return false;
}

function supprimerValeur(cellid)
{
	var cell = document.getElementById(cellid);
	var row = document.getElementById(cell.parentNode.id);
	var rowindex = row.rowIndex;
	var table = row.parentNode;
	table.deleteRow(rowindex);
	return false;
}
</script>
<div id="contenu1">
<?php 
if ($mode == 'modif') { 
	// RÉCUPÉRATION DU DOCUMENT ET DE SES PARAMÈTRES
	$mod_decree = new decree($dbcon, null, null, $mod_decree_id);
	//print_r2($mod_decree);
	if ($mod_decree_id != NULL)
	{
		$mod_select_decree = $mod_decree->getDecree();
		// USER AUTORISÉ ?
		if ($user->hasAccessDecree($mod_select_decree))
		{
			$mod_decree_fields = $mod_decree->getFields();
			$access = true;
			?>
			<p style="color:DodgerBlue;"><b>MODIFICATION DU DOCUMENT <?php echo $mod_year.'/'.$mod_num;?></b></p>
			<?php 
			//print_r2($mod_decree_fields);
		}
		else 
		{
			elog("Utilisateur non autorisé à modifier le document.");
			$access = false;
			unset($mod_decree);
			unset($mod_select_decree);
			$mode = 'create';
		}
	} else {
		echo "Erreur de paramètres : annee $mod_year et numero $mod_num.";
		$access = false;
		unset($mod_decree);
		$mode = 'create';
	}
}?>
	<div class="gauche">
	<?php if (sizeof($listModels) == 0 ) { ?>
		Vous n'avez accès à aucun modèle de document. <br>
	<?php } else { ?>
	<form class ="form-zorro" name="formselectdecree" action="create_decree.php" method="post">

	<input type="hidden" name='userid' value='<?php echo $userid;?>'>
	<select style="width:26em" name="selectarrete" id="selectarrete" onchange="this.form.submit()">			             		
	        <?php 
	        if (!isset($post_arrete)) { ?>
	        <option value="" selected="selected">&nbsp;</option>
	        <?php } else { ?>
	            <option value="">&nbsp;</option>
	        <?php } 
	        $type = 0;
	        foreach ($listModels as $model) { 
	        	if ($model['iddecree_type'] != $type) { 
	        		if ($type != 0) { ?>
	        			</optgroup> 
	        		<?php } $type = $model['iddecree_type']; ?>
		        	<optgroup label="<?php echo $model['namedecree_type'];?>">
	        	<?php } if ((isset($post_selectarrete) && $post_selectarrete == $model['idmodel']) || (isset($mod_select_decree) && $access && $mod_select_decree['idmodel'] == $model['idmodel'])) { ?>
		            	<option value="<?php echo $model['idmodel'];?>" selected="selected"><?php echo $model['name'];?></option>
		            	<?php } else { ?>
		            	<option value="<?php echo $model['idmodel'];?>"><?php echo $model['name'];?></option>
			<?php } } ?>
			</optgroup> 
	</select>
	</form>
	<?php } ?>
	<?php if (isset($post_selectarrete) && $post_selectarrete != '' || (isset($mod_select_decree) && $access)) 
		{ 
			$selectarrete = isset($mod_select_decree) ? $mod_select_decree['idmodel'] : $post_selectarrete;
			$modelselected = new model($dbcon, $selectarrete); 
			$urlselected = $modelselected->getfile();
		?>
		<br>
		<b>Paramétrage du document</b>
		<br><br>
		<?php $modelfields = $modelselected->getModelFields();
		 ?>
		<form name='find_person' method='post' action='create_decree.php'>
		<input type="hidden" name='userid' value='<?php echo $userid;?>'>
		<input type="hidden" name='selectarrete' value='<?php echo isset($post_selectarrete) ? $post_selectarrete : $mod_select_decree['idmodel'];?>'>
		<?php foreach ($modelfields as $modelfield)
		{
			if ($modelfield['auto'] != 'O')
				echo $modelfield['web_name']." : ";//." (".$modelfield['datatype'].") nombre d'occurrences : ".$modelfield['number'];?> 
			<div id='<?php echo $modelfield['name'].'_div';?>'>
			<input type="hidden" id='<?php echo $modelfield['name'].'_number';?>' value=1>
			<?php 
			switch ($modelfield['number']) {
				case '+': $nb_field = "1";
					?>

					<?php break;
					
				default: $nb_field = $modelfield['number'];
						;?>
						
					<?php break;
				}
				for ($i=1; $i <= $nb_field; $i++)
			{
				if ($modelfield['auto'] == 'O')
				{?>
					<label><?php echo $modelfield['web_name'];?> : </label>Automatique
				<?php }
				else {
					switch ($modelfield['datatype']) {
						case 'user':
						?>
						
				<?php findPerson($modelfield['name'],$i);
				if (isset($mod_decree_fields) && key_exists($modelfield['idmodel_field'], $mod_decree_fields))
				{
					echo "<script>document.getElementById('".$modelfield['name']."1').value = '".$mod_decree_fields[$modelfield['idmodel_field']][0]['value']."';</script>";
					echo "<script>document.getElementById('".$modelfield['name']."1').nextSibling.innerText = '".$mod_decree_fields[$modelfield['idmodel_field']][0]['value']."';</script>";

				}?>
		
						<?php break;
						case 'year':
							$defaultyear = (isset($mod_year)) ? date('Y', mktime(0,0,0,1,1,$mod_year)): date('Y'); ?>
							<select style="width:26em" name="<?php echo $modelfield['name'].$i;?>" id="<?php echo $modelfield['name'].$i;?>">
								<option value="<?php echo $defaultyear - 1;?>"><?php echo $defaultyear - 1;?></option>
								<option value="<?php echo $defaultyear;?>" selected="selected"><?php echo $defaultyear;?></option>
								<option value="<?php echo $defaultyear + 1;?>"><?php echo $defaultyear + 1;?></option>
							</select>
							<?php break;
						case 'query':
							// récupérer et exécuter la requête 
							$query = $modelselected->getQueryField($modelfield['idfield_type']);
							$result = $ref->executeQuery($query);
							if ($modelfield['idfield_type'] == 10)
							{ // C'est le choix de la composante
								$comps = array();
								foreach ($result as $value)
								{
									$comps[$value] = $ref->executeQuery(array('schema'=>'APOGEE',
																		'query' => "SELECT cmp.lib_web_cmp FROM composante cmp WHERE cmp.tem_en_sve_cmp = 'O' AND cmp.cod_cmp = '".$value."'"))[0];
								}
								$structuser = $ref->getUserStructureCodeAPO();?>
								<select style="width:26em" name="<?php echo $modelfield['name'].$i;?>" id="<?php echo $modelfield['name'].$i;?>">
								<?php
								foreach ($comps as $num => $comp)
								{
									if ((!isset($mod_select_decree) && $structuser == $num) || (isset($mod_select_decree) && $mod_select_decree['structure'] == $num))
									{ ?>
										<option value="<?php echo $num;?>" selected="selected"><?php echo $comp;?></option>
									<?php } else { ?>
										<option value="<?php echo $num;?>"><?php echo $comp;?></option>
									<?php } 
								}?>
								</select>
							<?php } else {
							// liste déroulante
							?>
							<select style="width:26em" name="<?php echo $modelfield['name'].$i;?>" id="<?php echo $modelfield['name'].$i;?>">
							<?php foreach($result as $value)
							{ 
								if (isset($mod_decree_fields) && $mod_decree_fields[$modelfield['idmodel_field']][$i-1]['value'] == $value) 
								{?>
									<option value="<?php echo $value;?>" selected="selected"><?php echo $value;?></option>
								<?php } else { ?>
									<option value="<?php echo $value;?>"><?php echo $value;?></option>
								<?php } 
							} ?>
							</select>
							<?php }
							break;
						default:
							$value = (isset($_POST[$modelfield['name'].$i])) ? "value='".$_POST[$modelfield['name'].$i]."'" : '';
							$value = (isset($mod_decree_fields)) ? "value='".$mod_decree_fields[$modelfield['idmodel_field']][$i-1]['value']."'" : '';?>
							<input type='text' id='<?php echo $modelfield['name'].$i;?>' name='<?php echo $modelfield['name'].$i;?>' <?php echo $value;?>>
						<?php break;
					}
				}
			} 
			if ($modelfield['number'] == '+')
			{ ?>
				<button onclick="return ajouterValeur('<?php echo $modelfield['name'];?>');">+</button>
				<table id='<?php echo "table_".$modelfield['name'];?>'></table>
				<br>
				<?php if (isset($mod_decree_fields) && key_exists($modelfield['idmodel_field'], $mod_decree_fields) && sizeof($mod_decree_fields[$modelfield['idmodel_field']]) > 1)
					{
						for($i = 1; $i < sizeof($mod_decree_fields[$modelfield['idmodel_field']]); $i++)
						{
							echo "<script>ajouterValeur('".$modelfield['name']."')</script>";
							echo "<script>document.getElementById('".$modelfield['name']."1').value = '".$mod_decree_fields[$modelfield['idmodel_field']][$i]['value']."';</script>";
							echo "<script>document.getElementById('".$modelfield['name']."1').nextSibling.innerText = '".$mod_decree_fields[$modelfield['idmodel_field']][$i]['value']."';</script>";
						}
					}
			 	}
			
			?>
			</div>
		<?php } ?>
		</div>
		<div class="droite">
		<?php if (isset($mod_year) && isset($mod_num))
		{ ?>
			<input type="hidden" id='mod_year' name='mod_year' value='<?php echo $mod_year;?>'>
			<input type="hidden" id='mod_num' name='mod_num' value='<?php echo $mod_num;?>'>
			<input type="hidden" id='mod_id' name='mod_id' value='<?php echo $mod_decree_id;?>'>
		<br>
		<?php // TODO : Contrôler l'état de la demande dans esignature 
		if (isset($mod_decree))
		{
			$status = $mod_decree->getStatus(); 
			if ($status != 'v') {?>
				<input type='submit' name='valide' value='Remplacer' onclick="return confirm('Êtes-vous sûr de vouloir supprimer la demande initiale ?')">
			<?php } ?>
			<input type='submit' name='duplique' value='Dupliquer'>
		<?php if ($status == 'b') { ?>
			<input type="submit" name='sign' onclick="return confirm('Envoyer à la signature ?')" value="Poursuivre la signature">
		<?php } ?>
		<?php if (isset($message)) { ?>
		<p><?php echo $message;?></p>
		<?php } ?>
		<br>
		<?php } } else {?>
		<br><input type='submit' name='valide' value='Valider'><br>
		<?php } ?>
		</div>
		</form>
		<div class="contenu2">
		<?php 
		if (isset($mod_decree))
		{
			$filename = PDF_PATH.$mod_decree->getFileName();
			//print_r2($filename);
			if (file_exists($filename))
			{ 
				$doc_pdf = fopen($filename, 'r');
				$contenu_pdf = fread($doc_pdf, filesize($filename));
				$encodage = base64_encode($contenu_pdf); 
				?>
				<?php echo '<iframe src=data:application/pdf;base64,' . $encodage . ' width="100%" height="500px">';
				echo "</iframe>";?>
	
				<br><br>
					            
			<?php }
			else {	?>
				<p> pas de document PDF.</p>
			<?php }
		}
		?>
		</div>
<?php } 
else 
{ ?>
	<p> Vous n'avez pas accès à ce document. </p>
<?php }?>

</div>
</body>
</html>


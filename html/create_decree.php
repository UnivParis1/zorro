<?php 
    require_once ('CAS.php');
    include './include/casconnection.php';
    require_once ('./include/fonctions.php');
    require_once ('./class/decree.php');
    require_once ('./include/dbconnection.php');
    require_once ('./class/user.php');
    require_once ('./class/model.php');
	require_once ('./class/reference.php');
	
    /*if (isset($_POST["userid"]))
        $userid = $_POST["userid"];
    else
        $userid = null;*/
    $ref = new reference($dbcon, $rdbApo);
    $userid = $ref->getUserUid();
    echo $_SESSION['uid'];
    if (is_null($userid) or ($userid == "")) 
    {
        elog("Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        header('Location: index.php');
        exit();
    }
    
    if (isset($_GET['num']) && isset($_GET['year']))
    {
    	$mode = 'modif';
    	$mod_year = $_GET['year'];
    	$mod_num = $_GET['num'];
    }
    else 
    {
    	$mode = 'create';
    }
     
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
	    $roles = $user->getRoles(); // roles actifs de l'utilisateur
	    $listModels = array();
	    foreach ($roles as $role)
	    {
	    	$model = new model($dbcon, $role['idmodel']);
	    	$listModels[] = $model->getModelInfo();
	    }
    }
    
    require ("include/menu.php");
?>

<script>
function ajouterValeur(divname, value='') 
{
	//alert("divname "+divname);
	//alert("inputbase "+inputbase.id);
	//var container = inputbase.parentElement;
	var table = document.getElementById("table_"+divname);
	var row = table.insertRow(-1);
	var rowindex = row.rowIndex;
	var nameindex = parseInt(rowindex+2,10);
	var cell0 = row.insertCell(0);
	//var name = document.createTextNode(document.getElementById(divname+"1").nextSibling.innerText);
	var name = document.createElement("input");
	name.setAttribute("type", "text");	
	name.setAttribute("id", divname+"[]");
	//alert(divname+nameindex);
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
	//alert("nbinput "+nbinput.id);
	//nbinput.setAttribute("value", parseInt(nbinput.getAttribute("value"),10)+parseInt(1,10));
	//var input = document.getElementById("test");
	//alert("input "+input.id);
	//input.innerHTML += "<br>"+document.getElementById(divname+"1").nextSibling.innerText;
	//input.setAttribute("type", "text");	
	//input.setAttribute("id", divname+nbinput.getAttribute("value"));
	//input.setAttribute("name", input.getAttribute("id"));
	//input.setAttribute("value", document.getElementById(divname+"1").nextSibling.innerText);
	//input.setAttribute("readonly", true);
	//inputbase.appendChild(input);
	//container.innerHTML += "<br>";
	return false;
}

function supprimerValeur(cellid)
{
	var cell = document.getElementById(cellid);
	var row = document.getElementById(cell.parentNode.id);
	var rowindex = row.rowIndex;
	//alert(rowindex);
	var table = row.parentNode;
	table.deleteRow(rowindex);
	return false;
}
</script>

<?php 
if ($mode == 'modif') { ?>
	<p style="color:DodgerBlue;"><b>MODIFICATION DU DOCUMENT <?php echo $mod_year.'/'.$mod_num;?></b></p>
	<?php 
	// RÉCUPÉRATION DU DOCUMENT ET DE SES PARAMÈTRES
	$mod_year = intval($mod_year);
	$mod_num = intval($mod_num);
	$mod_decree = new decree($dbcon, $mod_year, $mod_num);
	$mod_decree_id = $mod_decree->getId();
	if ($mod_decree_id != NULL)
	{
		$mod_select_decree = $mod_decree->getDecree();
		// USER AUTORISÉ ?
		if ($user->hasAccessDecree($mod_select_decree))
		{
			$mod_decree_fields = $mod_decree->getFields();
			print_r2($mod_decree_fields);
		}
		else 
		{
			echo "Utilisateur non autorisé à modifier le document.";
			$mode = 'create';
		}
	} else {
		echo "Erreur de paramètres : annee $mod_year et numero $mod_num.";
		$mode = 'create';
	}
}?>
<?php 
//if ($mode == 'create') { ?>
	<div><b> Sélection du modèle </b></div>
	<br>
	<?php if (sizeof($listModels) == 0 ) { ?>
		Vous n'avez accès à aucun modèle de document. <br>
	<?php } else { ?>
	<form name="formselectdecree" action="create_decree.php" method="post">
	<input type="hidden" name='userid' value='<?php echo $userid;?>'>
	<select style="width:26em" name="selectarrete" id="selectarrete" onchange="this.form.submit()">			             		
	        <?php 
	        if (!isset($_POST['arrete'])) { ?>
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
	        	<?php } if ((isset($_POST['selectarrete']) && $_POST['selectarrete'] == $model['idmodel']) || (isset($mod_select_decree) && $mod_select_decree['idmodel'] == $model['idmodel'])) { ?>
		            	<option value="<?php echo $model['idmodel'];?>" selected="selected"><?php echo $model['name'];?></option>
		            	<?php } else { ?>
		            	<option value="<?php echo $model['idmodel'];?>"><?php echo $model['name'];?></option>
			<?php } } ?>
			</optgroup> 
	</select>
	</form>
	<?php } ?>
	<?php if (isset($_POST['selectarrete']) && $_POST['selectarrete'] != '' || isset($mod_select_decree)) 
		{ 
			$selectarrete = isset($mod_select_decree) ? $mod_select_decree['idmodel'] : $_POST['selectarrete'];
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
		<input type="hidden" name='selectarrete' value='<?php echo isset($_POST['selectarrete']) ? $_POST['selectarrete'] : $mod_select_decree['idmodel'];?>'>
		<?php foreach ($modelfields as $modelfield)
		{
			if ($modelfield['auto'] != 'O')
				echo $modelfield['name']." : ";//." (".$modelfield['datatype'].") nombre d'occurrences : ".$modelfield['number'];?> 
			<div id='<?php echo $modelfield['name'].'_div';?>'>
			<input type="hidden" id='<?php echo $modelfield['name'].'_number';?>' value=1>
			<?php 
			switch ($modelfield['number']) {
				case '+': $nb_field = "1";
					?>
					<!-- <button onclick="return ajouterValeur('<?php echo $modelfield['name'];?>');">+</button>
					<table id='<?php echo "table_".$modelfield['name'];?>'></table>
			<br> -->
					<?php break;
					
				default: $nb_field = $modelfield['number'];
						;?>
						
					<?php break;
				}
				for ($i=1; $i <= $nb_field; $i++)
			{
				if ($modelfield['auto'] == 'O')
				{?>
					<label><?php echo $modelfield['name'];?> : </label>Automatique
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
		<?php if (isset($mod_year) && isset($mod_num))
		{ ?>
			<input type="hidden" id='mod_year' name='mod_year' value=<?php echo $mod_year;?>>
			<input type="hidden" id='mod_num' name='mod_num' value=<?php echo $mod_num;?>>
		<br><input type='submit' name='valide' value='Remplacer' onclick="return confirm('Êtes-vous sûr de vouloir supprimer la demande initiale ?')"><input type='submit' name='duplique' value='Dupliquer'><br>
		<?php } else {?>
		<br><input type='submit' name='valide' value='Valider'><br>
		<?php } ?>
		</form>
		<?php if (isset($_POST['user'])) echo "<br>".$_POST['user']."<br>";
		
		$year = date('Y');
		if (isset($_POST['annee1']))
		{
			$year = $_POST['annee1'];
		}
		$numero_dispo = $ref->getNumDispo($year);
		/*$sql_num_dispo = "SELECT 
							number + 1 AS numero_dispo 
						FROM (SELECT number FROM decree WHERE year = $year UNION SELECT 0 AS number FROM dual) AS d
						WHERE NOT EXISTS (SELECT d2.number FROM decree d2 WHERE d2.number = d.number + 1 AND (d2.status <> 'a' OR d2.status IS NULL) AND d2.year = $year) 
						ORDER BY number LIMIT 1";
		$result = mysqli_query($dbcon, $sql_num_dispo);
		$numero_dispo = -1;
		if (mysqli_error($dbcon))
		{
			elog("Erreur a l'execution de la requete du prochain numero d'arrete.");
		}
		else {
			
			if ($res = mysqli_fetch_assoc($result))
			{
				$numero_dispo = $res['numero_dispo'];
			}
		}*/
		$decreefields = array();
		if (isset($_POST['valide'])||isset($_POST['duplique'])) 
		{
			// Si le document est en mode modif et qu'il n'est pas validé dans esignature on supprime le numero d'arrêté et on crée un nouveau
			if (isset($_POST['mod_year']) && isset($_POST['mod_num']) && !isset($_POST['duplique']))
			{
				$mod_decree = new decree($dbcon, intval($_POST['mod_year']), intval($_POST['mod_num']));
				$mod_decree_infos = $mod_decree->getDecree();
				if ($mod_decree_infos != NULL && $mod_decree_infos['status'] != 'v')
				{
					$mod_decree->unsetNumber($user->getId());
					elog("Suppression du numero");
					// TODO : Supprimer le PDF qui avait été créé
				}
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
						$valeurs = $_POST[$modelfield['name']];
						foreach($valeurs as $valeur)
						{
							$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => $valeur);
						}
						if ($_POST[$modelfield['name']."1"] != '')
							$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => $_POST[$modelfield['name']."1"]);
					}
					else 
					{
						for($i = 1; $i <= $modelfield['number']; $i++)
						{
							//echo $modelfield['name'].$i." : ".$_POST[$modelfield['name'].$i]."<br>";
							if (isset($_POST[$modelfield['name'].$i]) && $_POST[$modelfield['name'].$i] != '')
							{
								$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => $_POST[$modelfield['name'].$i]);
							}
						}
					}
				}
			}
		
			$idmodel = $_POST['selectarrete'];
			$decree = new decree($dbcon, $year, $numero_dispo);
			$structure = $_POST['composante1'];
			$decree->save($user->getid(), $idmodel, $structure);
			$decree->setFields($decreefields);
			$modelselected = new model($dbcon, $idmodel);
		
			$modelfile = new ZipArchive();
			if (file_exists("./models/".$modelselected->getfile()))
			{
				$fieldstoinsert = $decree->getFields(); //echo "fieldstoinsert <br><br>";print_r2($fieldstoinsert);
				$modelfields = $modelselected->getModelFields();
				//echo "<br>modelfields <br><br>"; print_r2($modelfields);
				$modelfieldsarrange = array_column($modelfields, 'idmodel_field', 'name');
				//echo "<br>modelfieldsarrange <br><br>"; print_r2($modelfieldsarrange);
				// copie du modele pour l'arrêté
				copy("./models/".$modelselected->getfile(), "./files/".substr($modelselected->getfile(), 0, -4).$year.'_'.$numero_dispo.substr($modelselected->getfile(), -4));
				// ouverture du modele pour l'arrêté
				$modelfile->open("./files/".substr($modelselected->getfile(), 0, -4).$year.'_'.$numero_dispo.substr($modelselected->getfile(), -4));
				//$modelfile->open("./models/".$modelselected->getfile());
				//$contenu = $modelfile->getFromName('content.xml');
				// extraction du content.xml dans le dossier temporaire pour l'arrêté
				$modelfile->extractTo("./files/".$year.'_'.$numero_dispo."/", array('content.xml'));
				// ouverture du content.xml extrait
				$content = fopen("./files/".$year.'_'.$numero_dispo."/content.xml", 'r+');
				// lecture du content.xml extrait
				$contenu = fread($content, filesize("./files/".$year.'_'.$numero_dispo."/content.xml"));
				// copie du contenu extrait
				$contenu2 = $contenu;
				//$contenu = $modelfile->getFromName('content.xml');
				//echo "<br>contenu <br><br>"; print_r2($contenu);echo "<br><br>";
				$position1 = strpos($contenu, '$$$');
				$position2 = strpos($contenu, '$$$', $position1+1);
				//var_dump(strlen($contenu));
				$nb_field = array();
				$champsamodif = array();
				while ($position1 < strlen($contenu) && substr($contenu, $position1 + 3, $position2 - $position1 - 3) && $position1 !== false && $position2 !== false)
				{
					$field = substr($contenu, $position1 + 3, $position2 - $position1 - 3);
					if (!key_exists($field, $nb_field))
					{
						$nb_field[$field] = 0;
					}
					if (key_exists($field, $modelfieldsarrange) && key_exists($modelfieldsarrange[$field], $fieldstoinsert) && key_exists($nb_field[$field], $fieldstoinsert[$modelfieldsarrange[$field]]))
					{ 
						//echo "($position1 - $position2) à remplacer : $$$".$field."$$$ par : ".$fieldstoinsert[$modelfieldsarrange[$field]][$nb_field[$field]]['value']."<br>";
						$champsamodif[] = array("valeur" => $fieldstoinsert[$modelfieldsarrange[$field]][$nb_field[$field]]['value'], "position" => $position1, "longueur" => (strlen($field)+6));
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
				$content = fopen("./files/".$year.'_'.$numero_dispo."/content.xml", 'w');
				$champsamodiffromlast = array_reverse($champsamodif);
				// remplacement des champs à partir de la fin du fichier
				foreach ($champsamodiffromlast as $champ)
				{
					$contenu2 = substr_replace($contenu2, $champ['valeur'], $champ['position'], $champ['longueur']);
				}
				// écriture du contenu modifié dans le fichier
				fwrite($content, $contenu2);
				//$index = $modelfile->locateName('content.xml');
				$modelfile->addFile("./files/".$year.'_'.$numero_dispo."/content.xml", 'content.xml');
				//var_dump($fieldstoinsert);
				$modelfile->close();
				
				// CONVERSION EN PDF
				$descriptorspec = array(
						0 => array("pipe", "r"),  // stdin
						1 => array("pipe", "w"),  // stdout
						2 => array("pipe", "w"),  // stderr
				);
				$process = proc_open("unoconv --doctype=document --format=pdf files/".substr($modelselected->getfile(), 0, -4).$year.'_'.$numero_dispo.substr($modelselected->getfile(), -4), $descriptorspec, $pipes);
				$stdout = stream_get_contents($pipes[1]);
				fclose($pipes[1]);
				
				$stderr = stream_get_contents($pipes[2]);
				fclose($pipes[2]);
				if ($stdout != "")
				{
					elog( "stdout : \n");
					var_dump($stdout);
				}
				if ($stderr != "")
				{
					elog( "stderr :\n");
					var_dump($stderr);
				}
				?>
				<br>Prévisualisation du document : <br>
		<?php 
				if (file_exists("files/".substr($modelselected->getfile(), 0, -4).$year.'_'.$numero_dispo.".pdf"))
				{ 
					$doc_pdf = fopen("files/".substr($modelselected->getfile(), 0, -4).$year.'_'.$numero_dispo.".pdf", 'r');
					$contenu_pdf = fread($doc_pdf, filesize("files/".substr($modelselected->getfile(), 0, -4).$year.'_'.$numero_dispo.".pdf"));
					$encodage = base64_encode($contenu_pdf); 
					?>
					<!-- <a href="files/<?php echo substr($modelselected->getfile(), 0, -4).$year.'_'.$numero_dispo.".pdf";?>" target="_blank" ><b>ICI</b></a><br> -->
					<?php echo '<iframe src=data:application/pdf;base64,' . $encodage . ' width="80%" height="500px">';
					echo "</iframe>";?>
		
					<br><br>
					<input type="button" onclick="alert('Hello World!')" value="Poursuivre la signature">
					            
			<?php }
		?>
		<?php }
			
		}
		?>
	
	<br><br>
	<?php } else { ?>
	
	<?php } ?>
<?php //} ?>

</body>
</html>


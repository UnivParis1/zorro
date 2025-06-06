<?php
	include './include/casconnection.php';
	require_once ('./include/fonctions.php');
	require_once ('./include/dbconnection.php');
	require_once('./class/reference.php');
	require_once('./class/user.php');

	// Initialisation de l'utilisateur
	$ref = new reference($dbcon, '');
	$userid = $ref->getUserUid();
	//echo $_SESSION['uid'];
	if (is_null($userid) or ($userid == ""))
	{
		elog("Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
		header('Location: index.php');
		exit();
	}
	$liste_year = $ref->getCreationYears();
	if (isset($_POST['selectyear']) && $_POST['selectyear'] != '')
	{
		$post_selectyear = $_POST['selectyear'];
	}
	else
	{
		$post_selectyear = $ref->getAnneeUni();
	}
	$anneeuni = explode("-", $post_selectyear);
	$annee = $anneeuni[0];
	$anneeplusun = $anneeuni[1];
	if (isset($_POST['formation_president']))
	{
		$ref = new reference($dbcon, $rdbApo);
		$allmentions = $ref->getAllMentionsCommissions($annee);
		if (isset($_POST['depuis']) && $_POST['depuis'] != '')
		{
			$donnees = $ref->getCommissionsPresidents($annee, $anneeplusun, $_POST['depuis']);
		}
		else
		{
			$donnees = $ref->getCommissionsPresidents($annee, $anneeplusun);
		}
	
		$csv = "mention,code;email;president\n";
		$mentions_decode = array();
		foreach ($donnees as $ligne)
		{
			$md = html_entity_decode($ligne['mention']);
			$csv .= "\"".$allmentions[$md]['value'].", ".$md."\";".html_entity_decode($ligne['email']).";".html_entity_decode($ligne['president'])."\n";
			$mentions_decode[] = $md;
		}
		foreach ($allmentions as $codemention => $value)
		{
			if (!in_array($codemention, $mentions_decode))
			{
				$csv .= "\"".html_entity_decode($value['value']).", ".$codemention."\";;\n";
			}
		}
		$doc = fopen(PDF_PATH."presidents_commissions.csv", 'w+');
		fputs($doc, $csv);
		fclose($doc);
		header("Content-Type: text/csv");
		header("Content-disposition: attachment; filename=\"presidents_commissions".date("Ymd").".csv\"");
		readfile(PDF_PATH."presidents_commissions.csv");
		exit();
	}

	if (isset($_POST['formation_liste']))
	{
		$ref = new reference($dbcon, $rdbApo);
		$allmentions = $ref->getAllMentionsCommissions($annee);
		$list_comp = array_column($ref->getListCompHorsIAE(), 'value', 'code');
		$list_mention_comp = "code;mention;composante\n";
		foreach ($allmentions as $codemention => $detail)
		{
			$list_mention_comp .= "\"".$codemention."\";\"".html_entity_decode($detail['value'])."\";\"".$list_comp[$detail['cmp']]."\"\n";
		}
		$doc = fopen(PDF_PATH."mentions_composantes.csv", 'w+');
		fputs($doc, $list_mention_comp);
		fclose($doc);
		header("Content-Type: text/csv");
		header("Content-disposition: attachment; filename=\"mentions_composantes".date("Ymd").".csv\"");
		readfile(PDF_PATH."mentions_composantes.csv");
		exit();
	}

	if (isset($_POST['formation_responsables']))
	{
		$ref = new reference($dbcon, $rdbApo);
		$ldap = new ldap();
		$allmentions = $ref->getAllMentionsCommissions($annee);
		$list_comp = array_column($ref->getListCompHorsIAE(), 'value', 'code');
		$list_mention_resp = "mention,code;email;responsable\n";
		foreach ($list_comp as $cle => $codcomp)
		{
			$supann = $ldap->getSupannCodeEntiteFromAPO($cle);
			$resp = $ldap->getStructureResp($supann);
			// Traitement spécial pour l'EDS
			if (substr($supann, 0, 2) == 'DS' && $supann != 'DS21')
			{
				$resp = array_merge($resp, $ldap->getStructureResp('DS'));
			}
			$list_resp[$cle] = $resp;
		}
		foreach ($allmentions as $codemention => $detail)
		{
			if (sizeof($list_resp[$detail['cmp']]) == 0)
			{
				$list_mention_resp .= "\"".html_entity_decode($detail['value']).", ".$codemention."\";;\n";
			}
			foreach($list_resp[$detail['cmp']] as $login => $contact)
			{
				$list_mention_resp .= "\"".html_entity_decode($detail['value']).", ".$codemention."\";".$contact['mail'].";".$contact['name']."\n";
			}
		}
		$doc = fopen(PDF_PATH."mentions_responsables.csv", 'w+');
		fputs($doc, $list_mention_resp);
		fclose($doc);
		header("Content-Type: text/csv");
		header("Content-disposition: attachment; filename=\"mentions_responsables".date("Ymd").".csv\"");
		readfile(PDF_PATH."mentions_responsables.csv");
		exit();
	}

	$menuItem = 'menu_export';
	require ("include/menu.php");
	if (isset($_SESSION['phpCAS']) && array_key_exists('user', $_SESSION['phpCAS']))
	{
		$userCAS = new user($dbcon, $_SESSION['phpCAS']['user']);
		if ($userCAS->isSuperAdmin(false))
		{ ?>
			<div id="contenu1">
				<h2>Exports</h2>
					<form name='annee_uni' method='post' action='export.php'>
						<label>Pour l'année de référence :</label>
						<select name="selectyear" id="selectyear" onchange="this.form.submit()">
							<?php
								if (!isset($post_selectyear)) {
									$post_selectyear = COD_ANU.'-'.(COD_ANU+1);
								}
								foreach ($liste_year as $year) {
									if ((isset($post_selectyear) && $post_selectyear == $year)) { ?>
										<option value="<?php echo $year;?>" selected="selected"><?php echo $year;?></option>
									<?php } else { ?>
										<option value="<?php echo $year;?>"><?php echo $year;?></option>
									<?php }
								} ?>
						</select>
					</form>
				<form name='formation_president' method='post' action='export.php'>
					<label>Formation / Président de jury pour Gestion des recours</label>
					<label title="laisser vide pour la totalité">Arrêtés validés depuis</label> <input type='number' name='depuis'> jour(s)
					<a href="#" target="_blank"><input type='submit' name='formation_president' value='csv'></a>
					<input type="hidden" name="selectyear" id="selectyear" value=<?php echo $post_selectyear;?>>
				</form>
				<form name='formation_responsables' method='post' action='export.php'>
					<label>Formation / Responsables de composante</label>
					<a href="#" target="_blank"><input type='submit' name='formation_responsables' value='csv'></a>
					<input type="hidden" name="selectyear" id="selectyear" value=<?php echo $post_selectyear;?>>
				</form>
				<form name='formation_liste' method='post' action='export.php'>
					<label>Liste des formations pour la DEVE</label>
					<a href="#" target="_blank"><input type='submit' name='formation_liste' value='csv'></a>
					<input type="hidden" name="selectyear" id="selectyear" value=<?php echo $post_selectyear;?>>
				</form>
			</div>
		<?php 
		} else { ?>
			<div id="contenu1">
				<h2> Accès interdit </h2>
			</div>
		<?php 
		} 
	} else { ?>
	<div id="contenu1">
		<h2> Accès interdit </h2>
	</div>
<?php } ?>
</body>
</html>


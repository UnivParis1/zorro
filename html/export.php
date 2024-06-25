<?php
	include './include/casconnection.php';
	require_once ('./include/fonctions.php');
	require_once ('./include/dbconnection.php');
	require_once('./class/reference.php');
	require_once('./class/user.php');

	// Initialisation de l'utilisateur
	$ref = new reference('', '');
	$userid = $ref->getUserUid();
	//echo $_SESSION['uid'];
	if (is_null($userid) or ($userid == ""))
	{
		elog("Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
		header('Location: index.php');
		exit();
	}

	if (isset($_POST['formation_president']))
	{
		$ref = new reference($dbcon, $rdbApo);
		$allmentions = $ref->getAllMentionsCommissions();
		$anneeuni = explode("-", $ref->getAnneeUni());
		$annee = $anneeuni[0];
		$anneeplusun = $anneeuni[1];
		if (isset($_POST['depuis']) && $_POST['depuis'] != '')
		{
			$donnees = $ref->getCommissionsPresidents($annee, $anneeplusun, $_POST['depuis']);
		}
		else
		{
			$donnees = $ref->getCommissionsPresidents($annee, $anneeplusun);
		}
	
		$csv = "president;mention\n";
		$mentions_decode = array();
		foreach ($donnees as $ligne)
		{
			$md = html_entity_decode($ligne['mention']);
			$csv .= "\"".html_entity_decode($ligne['president'])."\";\"".$md."\"\n";
			$mentions_decode[] = $md;
		}
		foreach ($allmentions as $mention => $value)
		{
			if (!in_array(html_entity_decode($mention), $mentions_decode))
			{
				$csv .= "\"\";\"".html_entity_decode($mention)."\"\n";
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
		$allmentions = $ref->getAllMentionsCommissions();
		$list_comp = array_column($ref->getListComp(), 'value', 'code');
		$list_mention_comp = "mention;composante\n";
		foreach ($allmentions as $mention => $detail)
		{
			$list_mention_comp .= "\"".html_entity_decode($mention)."\";\"".$list_comp[$detail['cmp']]."\"\n";
		}
		$doc = fopen(PDF_PATH."mentions_composantes.csv", 'w+');
		fputs($doc, $list_mention_comp);
		fclose($doc);
		header("Content-Type: text/csv");
		header("Content-disposition: attachment; filename=\"mentions_composantes".date("Ymd").".csv\"");
		readfile(PDF_PATH."mentions_composantes.csv");
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
				<form name='formation_president' method='post' action='export.php'>
					<label>Formation / Président de jury pour Gestion des recours</label>
					<label title="laisser vide pour la totalité">Arrêtés validés depuis</label> <input type='number' name='depuis'> jour(s)
					<a href="#" target="_blank"><input type='submit' name='formation_president' value='csv'></a>
				</form>
				<form name='formation_liste' method='post' action='export.php'>
					<label>Liste des formations pour la DEVE</label>
					<a href="#" target="_blank"><input type='submit' name='formation_liste' value='csv'></a>
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


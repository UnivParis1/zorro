<?php
require_once ('CAS.php');
include './include/casconnection.php';
require_once ('./include/fonctions.php');

if (isset($_POST["userid"]))
	$userid = $_POST["userid"];
	else
		$userid = null;
		if (is_null($userid) or ($userid == "")) {
			elog("Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
			header('Location: index.php');
			exit();
		}
		
		// Récupération des modeles auxquels à accès l'utilisateur
		$menuItem = 'menu_model';
		require ("include/menu.php");
?>
<div id="contenu1">
	<h2> Gestion des modèles </h2>
</div>
</body>
</html>


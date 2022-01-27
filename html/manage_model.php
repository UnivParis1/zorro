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
		
		require ("include/menu.php");
?>

<div> Gestion des modèles </div>

</body>
</html>


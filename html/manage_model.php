<?php
include './include/casconnection.php';
require_once ('./include/fonctions.php');
require_once ('./include/dbconnection.php');
require_once ('./class/reference.php');

$ref = new reference($dbcon, $rdbApo);
$userid = $ref->getUserUid();
//echo $_SESSION['uid'];
if (is_null($userid) or ($userid == ""))
{
	elog("Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
	header('Location: index.php');
	exit();
}
		
// Récupération des modeles auxquels à accès l'utilisateur
$menuItem = 'menu_model';
require ("include/menu.php");
if (isset($_SESSION['phpCAS']) && array_key_exists('user', $_SESSION['phpCAS']))
{
	$userCAS = new user($dbcon, $_SESSION['phpCAS']['user']);
	if ($userCAS->isSuperAdmin(false))
	{
?>
<div id="contenu1">
	<h2> Gestion des modèles </h2>
</div>
<?php } else { ?>
<div id="contenu1">
	<h2> Accès interdit </h2>
</div>
<?php } } else { ?>
<div id="contenu1">
	<h2> Accès interdit </h2>
</div>
<?php } ?>
</body>
</html>


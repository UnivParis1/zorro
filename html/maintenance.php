<?php
    include './include/casconnection.php';
    include './class/ldap.php';
    require_once('./class/reference.php');
    require_once('./class/user.php');
	require_once "./include/dbconnection.php";

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
	if (isset($_POST['mode']))
	{
		$modeactuel = $ref->getModeMaintenance();
		$modemodif = $ref->setModeMaintenance($modeactuel ? "FALSE" : "TRUE");
	}
	$menuItem = 'menu_maintenance';
	require ("include/menu.php");

	if (isset($_SESSION['phpCAS']) && array_key_exists('user', $_SESSION['phpCAS']))
	{
		$userCAS = new user($dbcon, $_SESSION['phpCAS']['user']);
		if ($userCAS->isSuperAdmin(false))
		{
?>
<div id="contenu1">
	<h2>Mode Maintenance</h2>
	<form name='maintenance' method='post' action='maintenance.php'>
		<input type="hidden" id ="userid" name="userid" value="<?php echo $userid;?>">
		<input type="hidden" id="mode" name="mode">
		<?php if ($ref->getModeMaintenance()) { ?>
			<input type='submit' value='Désactiver'>
		<?php } else { ?>
			<input type='submit' value='Activer'>
		<?php } ?>
	</form>
	<?php if (isset($modemodif) && $modemodif) { ?>
		<p class="alerte alerte-success"> Mode maintenance modifié. </p>
	<?php } ?>
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


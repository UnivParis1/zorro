<?php
    require_once ('CAS.php');
    include './include/casconnection.php';
    include './class/ldap.php';
    require_once('./class/reference.php');
    require_once('./class/user.php');
	
	// Initialisation de l'utilisateur
	if (isset($_POST["userid"])) {
	 $userid = $_POST["userid"];
	 } else {
	 $userid = null;
	 }
	 if (is_null($userid) or ($userid == "")) {
	 elog("Redirection vers index.php (UID de l'utilisateur=" . $userid . ")");
	 header('Location: index.php');
	 exit();
	 }
	 if (isset($_POST['newuser']) && $_POST['newuser'] != null)
	 {
		elog($userid." a usurpe l identite de ".$_POST['newuser']);
	 	$ldap = new ldap();
	 	$ldap->setUser($_POST['newuser']);
	 	$ldap->getInfos($_POST['newuser']);
		$ldap->getUserAndStructureInfos($_POST['newuser']);
	 	$userid = $_POST['newuser'];
	 	$ref = new reference($dbcon, $rdbApo);
	 	$user = new user($dbcon, $userid);
	 	$allgroups = $ref->getAllGroupes();
	 	$groups = $user->getUserGroupes($allgroups);
	 	$_SESSION['groupes'] = $groups;
	 }
	 $menuItem = 'menu_admin';
	require ("include/menu.php");

	if (isset($_SESSION['phpCAS']) && array_key_exists('user', $_SESSION['phpCAS']))
	{
		$userCAS = new user($dbcon, $_SESSION['phpCAS']['user']);
		if ($userCAS->isSuperAdmin(false))
		{
?>
<div id="contenu1">
	<h2>Changer d'utilisateur</h2>
	<form name='subst_agent' method='post' action='admin_substitution.php'>
		<input id="newuser" name="newuser" placeholder="login" autofocus/> 
		<input type="hidden" id ="userid" name="userid" value="<?php echo $userid;?>">
		<input type='submit' value='Se faire passer pour...'>
	</form>
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


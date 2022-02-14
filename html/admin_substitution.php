<?php
    require_once ('CAS.php');
    include './include/casconnection.php';
	include './class/ldap.php';
	
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
	 if (isset($_POST['newuser']))
	 {
	 	$ldap = new ldap();
	 	$ldap->setUser($_POST['newuser']);
	 	$ldap->getInfos($_POST['newuser']);
	 	$userid = $_POST['newuser'];
	 }
	require ("include/menu.php");

?>

<br>
<form name='subst_agent' method='post' action='admin_substitution.php'>
	<input id="newuser" name="newuser" placeholder="login" autofocus/> 
	<input type="hidden" id ="userid" name="userid" value="<?php echo $userid;?>">
	<input type='submit' value='Se faire passer pour...'>
</form>

</body>
</html>


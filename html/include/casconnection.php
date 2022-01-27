<?php
require_once './include/dbconnection.php';   

	phpCAS::client(CAS_VERSION_2_0, CAS_SERVER, intval(CAS_PORT), CAS_PATH, true);
	
	phpCAS::setNoCasServerValidation();
	phpCAS::handleLogoutRequests(false);
	if (! phpCAS::isAuthenticated()) {
		// Recuperation de l'uid
		phpCAS::forceAuthentication();
	}
	$uid = phpCAS::getUser();
	if (!isset($_SESSION['uid']))
	{
		$_SESSION['uid'] = $uid;
	}
//echo "uid CAS du user :".var_export($_SESSION['uid'], true)."<br>";
?>

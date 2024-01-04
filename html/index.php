<?php
	session_start();
	ini_set('default_charset', 'utf-8');
    require_once './include/casconnection.php';
    require_once ("./include/dbconnection.php");
    require_once ("./include/fonctions.php");
    // Initialisation de l'utilisateur
    $userid = null;
    
    if (isset($_SESSION["uid"]))
    	$userid = $_SESSION["uid"];
    elseif (isset($_POST["userid"]))
        $userid = htmlspecialchars($_POST["userid"]);
    require_once ('./class/ldap.php');


	    $casversion = phpCAS::getVersion();
	    $errlog = "Index.php => Version de CAS.php utilisée  : " . $casversion;
	    //echo "<br><br>" . $errlog . "<br><br>";
	    elog($errlog);
	    $ldap = new ldap();
	    $infos_ldap = $ldap->getInfos($userid);
	    $ldap->getUserAndStructureInfos($userid);
	    //print_r2($infos_ldap);

	    //print_r2($infos_ldap['supannentiteaffectation']);
 
	    $roles = $ldap->getStructureResp($infos_ldap['supannentiteaffectation']);

	    if (!isset($_SESSION['groupes']))
	    {
	    	require_once('./class/user.php');
	    	require_once('./class/reference.php');
	    	$ref = new reference($dbcon, $rdbApo);
	    	$user = new user($dbcon, $userid);
	    	$allgroupes = $ref->getAllGroupes();
	    	$groupes = $user->getUserGroupes($allgroupes);
	    	$_SESSION['groupes'] = $groupes;
	    	//print_r2($_SESSION['groupes']);
	    }
	    //$menuItem = 'menu_manage';
	    //require_once ("./include/menu.php");
	    //echo '<html><body class="bodyhtml">';
	    include './manage_decree.php';
	    /*foreach ($infos_ldap as $cle => $info)
	    {
			echo $cle.' : '.$info." <br> ";
	    }
	    echo "<br>";
	    foreach ($roles as $role)
	    {
			echo $role['role']." : ".$role['name']." ".$role['mail']."<br>";
	    }
	    */
?>

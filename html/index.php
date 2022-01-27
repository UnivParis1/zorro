<?php
	session_start();
	ini_set('default_charset', 'utf-8');     
    require_once ('CAS.php');
    require_once './include/casconnection.php';
    require_once ("./include/dbconnection.php");
    require_once ("./include/fonctions.php");
    // Initialisation de l'utilisateur
    $userid = null;
    
    if (isset($_SESSION["uid"]))
    	$userid = $_SESSION["uid"];
    elseif (isset($_POST["userid"]))
        $userid = $_POST["userid"];
    require_once ('./class/ldap.php');
    require_once ("./include/menu.php");


	    $casversion = phpCAS::getVersion();
	    $errlog = "Index.php => Version de CAS.php utilisée  : " . $casversion;
	    //echo "<br><br>" . $errlog . "<br><br>";
	    elog($errlog);
	    echo '<html><body class="bodyhtml">';
	    $ldap = new ldap();
	    $infos_ldap = $ldap->getInfos($userid);
	    //var_dump($infos_ldap);
	    foreach ($infos_ldap as $cle => $info)
	    {
	    	echo $cle.' : '.$info." <br> ";
	    }
	    echo "<br>";
?>
</body>
</html>

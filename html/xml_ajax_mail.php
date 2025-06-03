<?php
/**
 *
 */
header("Content-Type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
echo "<list>";

session_start();

if (isset($_POST['uid']))
{
	include './include/dbconnection.php';
	require_once './class/ldap.php';
	require_once './class/reference.php';
	$ref = new reference($dbcon, $rdbApo);
	$ldap = new ldap();
	$mail = $ldap->getEmailUser($_POST['uid']);
	if ($mail !== NULL)
	{
		echo "<item id=\"emailpresident\" libelle=\"".$mail."\" />";
	}
}
echo "</list>";

?>
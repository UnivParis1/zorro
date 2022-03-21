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
	$etuInfos = $ldap->getEtuInfos($_POST['uid']);
	if (is_array($etuInfos))
	{
		foreach($etuInfos as $key => $info)
		{
			echo "<item id=\"".$key."\" libelle=\"".$info."\" />";
		}
	}
}
echo "</list>";

?>
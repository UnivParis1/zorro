<?php
/**
 *
 */
header("Content-Type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
echo "<list>";

session_start();

if (isset($_POST['structure']))
{
	include './include/dbconnection.php';
	require_once './class/ldap.php';
	require_once './class/reference.php'; 
	$ref = new reference($dbcon, $rdbApo);
	$ldap = new ldap();
	$structureInfos = $ldap->getStructureInfos($_POST['structure']);
	if (isset($structureInfos['codeapo']) && sizeof($structureInfos['codeapo']) > 0)
	{
		foreach($structureInfos['codeapo'] as $codApo)
		{
			$composanteName = $ref->executeQuery(array('schema'=>'APOGEE',
					'query' => "SELECT cmp.cod_cmp, cmp.lib_web_cmp FROM composante cmp WHERE cmp.tem_en_sve_cmp = 'O' AND cmp.cod_cmp = '".$codApo."'"))[0];
			echo "<item id=\"".$codApo."\" libelle=\"".$composanteName['value']."\" />";
		}

	}
	else 
	{
		$query = "SELECT cod_cmp, cmp.lib_web_cmp FROM composante cmp WHERE cmp.tem_en_sve_cmp = 'O' ORDER BY cod_cmp";
		$result = $ref->executeQuery(array("schema" => 'APOGEE', "query" => $query, "query_clause" => NULL));
		foreach ($result as $comp)
		{
			echo "<item id=\"".$comp['code']."\" libelle=\"".$comp['value']."\" />";
		}
	}
}
echo "</list>";

?>
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
	$codApo = $ldap->getInfoApo($_POST['structure']);
	if ($codApo != '')
	{
		$composanteName = $ref->executeQuery(array('schema'=>'APOGEE',
				'query' => "SELECT cmp.cod_cmp, cmp.lib_web_cmp FROM composante cmp WHERE cmp.tem_en_sve_cmp = 'O' AND cmp.cod_cmp = '".$codApo."'"))[0];
		echo "<item id=\"".$codApo."\" libelle=\"".$composanteName['value']."\" />";
	}
	else 
	{
		/*$query = "SELECT cod_cmp, cmp.lib_web_cmp FROM composante cmp WHERE cmp.tem_en_sve_cmp = 'O' ORDER BY cod_cmp";
		$result = $ref->executeQuery(array("schema" => 'APOGEE', "query" => $query, "query_clause" => NULL));
		foreach ($result as $comp)
		{
			echo "<item id=\"".$comp['code']."\" libelle=\"".$comp['value']."\" />";
		}*/
		echo "<item id=\"Erreur\" libelle=\"Pas de composante associée\" />";
	}
}
elseif(isset($_POST['cod_cmp_dom']) && isset($_POST['idmodel']))
{
	include './include/dbconnection.php';
	include './class/model.php';
	include './class/decree.php';
	require_once './class/reference.php';
	$ref = new reference($dbcon, $rdbApo);
	$model = new model($dbcon, $_POST['idmodel']);
	$cod_cmp = $_POST['cod_cmp_dom'];
	if (!isset($_POST['coddfd']))
	{
		if (!isset($_POST['mention']))
		{
			$query = $model->getQueryField(2); //domaine
			if ($cod_cmp != '')
			{
				$query['query_clause'] = ($query['query_clause'] != NULL) ? $query['query_clause']." AND chv.cod_cmp = '".$_POST['cod_cmp_dom']."' ORDER BY 2" : " AND chv.cod_cmp = '".$_POST['cod_cmp_dom']."' ORDER BY 2";
			}
			else
			{
				$query['query_clause'] = ($query['query_clause'] != NULL) ? $query['query_clause']." ORDER BY 2" : " ORDER BY 2";
			}
			$result = $ref->executeQuery($query);
			$valeur = '';
			if (isset($_POST['iddecree']))
			{
				$mod_decree = new decree($dbcon, null, null, $_POST['iddecree']);
				$valeur = $mod_decree->getFieldForFieldType(2);
			}
			foreach ($result as $dom)
			{
				if ($valeur == $dom['value'])
				{
					echo "<item id=\"".$dom['value']."\" libelle=\"".$dom['value']."\" selected=\"true\" />";
				}
				else
				{
					echo "<item id=\"".$dom['value']."\" libelle=\"".$dom['value']."\" selected=\"false\" />";
				}
			}
		}
		else
		{
			$query = $model->getQueryField(8); //specialite
			$sql_mention = " AND mev.lib_mev = '".str_replace("'", "''", $_POST['mention'])."' ";
			if ($cod_cmp != '')
			{
				$query['query_clause'] = ($query['query_clause'] != NULL) ? $query['query_clause'].$sql_mention." AND chv.cod_cmp = '".$_POST['cod_cmp_dom']."' ORDER BY 2" : $sql_mention." AND chv.cod_cmp = '".$_POST['cod_cmp_dom']."' ORDER BY 2";
			}
			else
			{
				$query['query_clause'] = ($query['query_clause'] != NULL) ? $query['query_clause'].$sql_mention." ORDER BY 2" : $sql_mention." ORDER BY 2";
			}
			$result = $ref->executeQuery($query);
			$valeur = '';
			if (isset($_POST['iddecree']))
			{
				$mod_decree = new decree($dbcon, null, null, $_POST['iddecree']);
				$valeur = $mod_decree->getFieldForFieldType(8);
			}
			foreach ($result as $spec)
			{
				if ($valeur == $spec['value'])
				{
					echo "<item id=\"".$spec['value']."\" libelle=\"".$spec['value']."\" selected=\"true\" />";
				}
				else
				{
					echo "<item id=\"".$spec['value']."\" libelle=\"".$spec['value']."\" selected=\"false\" />";
				}
			}
		}
	}
	else
	{
		if (isset($_POST['etp']))
		{
			$query = $model->getQueryField(106); //mention2
			$sql_comp = $cod_cmp != '' ? " AND chv.cod_cmp = '".$_POST['cod_cmp_dom']."'" : '';
			$sql_dfd = $_POST['coddfd'] != '' ? " AND dfd.lib_dfd = '".str_replace("'", "''", $_POST['coddfd'])."'" : '';
			$sql_etp = $_POST['etp'] != '' ? " AND vet2.lib_web_vet = '".str_replace("'", "''", $_POST['etp'])."'" : '';
			$query['query_clause'] = ($query['query_clause'] != NULL) ? $query['query_clause'].$sql_comp.$sql_dfd.$sql_etp." ORDER BY 2" : $sql_comp.$sql_dfd.$sql_etp." ORDER BY 2";
			//elog(var_export($query, true));
			$result = $ref->executeQuery($query);
			$valeur = '';
			if (isset($_POST['iddecree']))
			{
				$mod_decree = new decree($dbcon, null, null, $_POST['iddecree']);
				$valeur = $mod_decree->getFieldForFieldType(106);
			}
			foreach ($result as $dom)
			{
				if ($valeur == htmlspecialchars($dom['value']))
				{
					echo "<item id=\"".htmlspecialchars($dom['value'])."\" libelle=\"".htmlspecialchars($dom['value'])."\" selected=\"true\" />";
				}
				else
				{
					echo "<item id=\"".htmlspecialchars($dom['value'])."\" libelle=\"".htmlspecialchars($dom['value'])."\" selected=\"false\" />";
				}
			}
		}
		else
		{
			$query = $model->getQueryField(3); //mention
			$sql_comp = $cod_cmp != '' ? " AND chv.cod_cmp = '".$_POST['cod_cmp_dom']."'" : '';
			$sql_dfd = $_POST['coddfd'] != '' ? " AND dfd.lib_dfd = '".str_replace("'", "''", $_POST['coddfd'])."'" : '';
			$query['query_clause'] = ($query['query_clause'] != NULL) ? $query['query_clause'].$sql_comp.$sql_dfd." ORDER BY 2" : $sql_comp.$sql_dfd." ORDER BY 2";
			//elog(var_export($query, true));
			$result = $ref->executeQuery($query);
			$valeur = '';
			if (isset($_POST['iddecree']))
			{
				$mod_decree = new decree($dbcon, null, null, $_POST['iddecree']);
				$valeur = $mod_decree->getFieldForFieldType(3);
			}
			foreach ($result as $dom)
			{
				if ($valeur == htmlspecialchars($dom['value']))
				{
					echo "<item id=\"".htmlspecialchars($dom['value'])."\" libelle=\"".htmlspecialchars($dom['value'])."\" selected=\"true\" />";
				}
				else
				{
					echo "<item id=\"".htmlspecialchars($dom['value'])."\" libelle=\"".htmlspecialchars($dom['value'])."\" selected=\"false\" />";
				}
			}
		}
	}
}
echo "</list>";

?>
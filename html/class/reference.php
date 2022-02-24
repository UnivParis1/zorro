<?php
require_once './include/const.php';
require_once './include/fonctions.php';

class reference {
	
	private $_dbcon;
	private $_rdbApo;
	
	function __construct($dbcon, $rdbApo)
	{
		require_once ("./include/dbconnection.php");
		$this->_dbcon = $dbcon;
		$this->_rdbApo = $rdbApo;
	}
	
	function getListDecreeType()
	{
		$select = "SELECT dt.*, count(DISTINCT m.idmodel) AS nb_model FROM decree_type dt INNER JOIN model m ON m.iddecree_type = dt.iddecree_type GROUP BY dt.iddecree_type";
		$result = mysqli_query($this->_dbcon, $select);
		$list = array();
		if ( !mysqli_error($this->_dbcon))
		{
			while ($res = mysqli_fetch_assoc($result))
			{
				$list[$res['iddecree_type']] = $res;
			}
		}
		else 
		{
			elog("erreur select * from decree_type.".mysqli_error($this->_dbcon));
		}
		return $list;
	}
	
	function getListModel($iddecree_type = null)
	{
		$select = "SELECT * FROM model ";
		if ($iddecree_type != null)
			$select .= " WHERE iddecree_type = ".intval($iddecree_type);
		$select .= " ORDER BY iddecree_type, name";
		$result = mysqli_query($this->_dbcon, $select);
		$list = array();
		if ( !mysqli_error($this->_dbcon))
		{
			while ($res = mysqli_fetch_assoc($result))
			{
				$list[$res['idmodel']] = $res;
			}
		}
		else
		{
			elog("erreur select * from model.".mysqli_error($this->_dbcon));
		}
		return $list;
	}
	
	function getListRole()
	{
		$select = "SELECT * FROM role WHERE scope <> 'app' ";
		$result = mysqli_query($this->_dbcon, $select);
		$list = array();
		if ( !mysqli_error($this->_dbcon))
		{
			while ($res = mysqli_fetch_assoc($result))
			{
				$list[$res['idrole']] = $res;
			}
		}
		else
		{
			elog("erreur select * from role.".mysqli_error($this->_dbcon));
		}
		return $list;
	}
	
	function getUserUid()
	{
		return (isset($_SESSION['uid'])) ? $_SESSION['uid'] : NULL;
	}
	
	function getUserDisplayName()
	{
		return (isset($_SESSION['displayname'])) ? $_SESSION['displayname'] : NULL;
	}
	
	function getUserMail()
	{
		return (isset($_SESSION['mail'])) ? $_SESSION['mail'] : NULL;
	}
	
	function getUserStructureCodeRH()
	{
		return (isset($_SESSION['supannentiteaffectation'])) ? $_SESSION['supannentiteaffectation'] : NULL;
	}
	
	function getUserStructureLib()
	{
		return (isset($_SESSION['ou'])) ? $_SESSION['ou'] : NULL;
	}
	
	function getUserStructureCodeAPO()
	{
		return (isset($_SESSION['supannrefid'])) ? $_SESSION['supannrefid'] : NULL;
	}
	
	function executeQuery($tab)
	{
		if (array_key_exists('schema', $tab) && $tab['schema'] == 'APOGEE')
		{
			if (array_key_exists('query', $tab))
			{
				$select = $tab['query'];
				if (array_key_exists('query_clause', $tab) && $tab['query_clause'] != NULL)
				{
					$select .= $tab['query_clause'];
				}
				$sth = oci_parse($this->_rdbApo, $select);
				$fields = array();
				if ( !oci_error($this->_rdbApo))
				{
					$res = oci_execute($sth);
					if ($res)
					{
						while ($res = oci_fetch_row($sth))
						{
							$fields[] = $res[0];
						}
					}
				}
				else
				{
					elog("erreur executeQuery from model. ".oci_error($this->_rdbApo));
				}
				return $fields;
			}
		}
		return NULL;
	}
	
	function getNumDispo($year)
	{
		$sql_num_dispo = "SELECT
							number + 1 AS numero_dispo
						FROM (SELECT d.number FROM decree d INNER JOIN number num ON num.year = d.year WHERE d.year = ".intval($year)." AND num.low_number <= d.number UNION SELECT num.low_number - 1 AS number FROM number num WHERE num.year = ".intval($year).") AS d
						WHERE NOT EXISTS (SELECT d2.number FROM decree d2 WHERE d2.number = d.number + 1 AND (d2.status <> 'a' OR d2.status IS NULL) AND d2.year = ".intval($year).")
						ORDER BY number LIMIT 1";
		$result = mysqli_query($this->_dbcon, $sql_num_dispo);
		$numero_dispo = -1;
		if (mysqli_error($this->_dbcon))
		{
			elog("Erreur a l'execution de la requete du prochain numero d'arrete.");
		}
		else {
			
			if ($res = mysqli_fetch_assoc($result))
			{
				$numero_dispo = $res['numero_dispo'];
			}
		}
		return $numero_dispo;
	}
	
	function getAllGroupes()
	{
		$select = "SELECT idgroupe, name, grouper FROM groupe";
		$res = mysqli_query($this->_dbcon, $select);
		$allgroupes = array();
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($res))
			{
				$allgroupes[] = $row;
			}
		}
		return $allgroupes;
	}
	
	function getGroupeById($idgroupe)
	{
		$select = "SELECT idgroupe, name, grouper FROM groupe WHERE idgroupe = ".intval($idgroupe);
		$res = mysqli_query($this->_dbcon, $select);
		$groupe = NULL;
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($res))
			{
				$groupe = $row;
			}
		}
		return $groupe;
	}

}
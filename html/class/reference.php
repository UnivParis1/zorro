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
	
	function getListModel($iddecree_type = null, $activeonly=false)
	{
		$select = "SELECT * FROM model ";
		$params = array();
		if ($iddecree_type != null)
		{
			$select .= " WHERE iddecree_type = ? ";
			if ($activeonly)
			{
				$select .= " AND active = 'O'";
			}
			$params = array($iddecree_type);
		}
		elseif ($activeonly)
		{
			$select .= " WHERE active = 'O' ";
		}
		$select .= " ORDER BY iddecree_type, name";
		if (sizeof($params) > 0)
		{
			$result = prepared_select($this->_dbcon, $select, $params);
		}
		else
		{
			$result = mysqli_query($this->_dbcon, $select);
		}
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
							$fields[] = array ('code' => $res[0], 'value' => $res[1]);
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
						FROM (SELECT d.number FROM decree d INNER JOIN number num ON num.year = d.year WHERE d.year = ? AND num.low_number <= d.number UNION SELECT num.low_number - 1 AS number FROM number num WHERE num.year = ?) AS d
						WHERE NOT EXISTS (SELECT d2.number FROM decree d2 WHERE d2.number = d.number + 1 AND d2.year = ?)
						ORDER BY number LIMIT 1";
		$params = array($year, $year, $year);
		$result = prepared_select($this->_dbcon, $sql_num_dispo, $params);
		$numero_dispo = -1;
		if (mysqli_error($this->_dbcon))
		{
			elog("Erreur a l'execution de la requete du prochain numero d'arrete.");
		}
		else {
			
			if ($res = mysqli_fetch_assoc($result))
			{
				$numero_dispo = $res['numero_dispo'];
				elog('le numero disponible est : '.$numero_dispo);
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
		$select = "SELECT idgroupe, name, grouper FROM groupe WHERE idgroupe = ?";
		$params = array($idgroupe);
		$result = prepared_select($this->_dbcon, $select, $params);
		$groupe = NULL;
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($result))
			{
				$groupe = $row;
			}
		}
		return $groupe;
	}

	function getGroupesRoles()
	{
		$lstGroupesRoles = array();
		$select_allgroupes = "SELECT
								g.idgroupe,
							    g.name,
							    r.idrole,
							    r.name as rolename,
							    gr.idmodel,
							    m.name as modelname,
								dt.iddecree_type,
							    dt.name as decreetypename,
								gr.active
							FROM
								groupe g
							    LEFT JOIN groupe_role gr
									ON g.idgroupe = gr.idgroupe
								LEFT JOIN role r
									ON r.idrole = gr.idrole
							    LEFT JOIN model m
									ON m.idmodel = gr.idmodel
								LEFT JOIN decree_type dt
									ON m.iddecree_type = dt.iddecree_type
							ORDER BY g.name";
		$result = mysqli_query($this->_dbcon, $select_allgroupes);
		if (mysqli_error($this->_dbcon))
		{
			elog("Erreur a l'execution de la requete select all groupes.");
		}
		else {
			while ($res = mysqli_fetch_assoc($result))
			{
				$lstGroupesRoles[] = $res;
			}
		}
		return $lstGroupesRoles;
	}

	function decreeNumExists($numero, $annee)
	{
		$select = 'SELECT iddecree FROM decree WHERE number = ? AND year = ?';
		$params = array($numero, $annee);
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if (mysqli_num_rows($result) > 0)
			{
				return true;
			}
		}
		return false;
	}

	function getModelFieldName($idmodel_field)
	{
		$select = 'SELECT fty.name FROM field_type fty INNER JOIN model_field mfi ON fty.idfield_type = mfi.idfield_type WHERE mfi.idmodel_field = ?';
		$params = array($idmodel_field);
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($row = mysqli_fetch_assoc($result))
			{
				return $row['name'];
			}
		}
		return NULL;
	}

	function getStatuts()
	{
		return array(STATUT_BROUILLON => 'Brouillon',
				STATUT_EN_COURS => 'En cours de signature',
				STATUT_VALIDE => 'Validé',
				STATUT_REFUSE => 'Refusé',
				STATUT_ANNULE => 'Annulé',
				STATUT_CORBEILLE => 'Document dans la corbeille d\'eSignature',
				STATUT_SUPPR_ESIGN => 'Document supprimé d\'eSignature',
				STATUT_ERREUR => 'Document non trouvé sur eSignature');
	}

	function getStructureExportPath($structure)
	{
		if (substr($structure, 0, 11) != 'structures-')
		{
			$structure = 'structures-'.$structure;
		}
		$select = "SELECT export_path FROM structure_export_path WHERE structure = ?";
		$params = array($structure);
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($row = mysqli_fetch_assoc($result))
			{
				return $row['export_path'];
			}
		}
		return NULL;
	}

	function setStructureExportPath($structure, $export_path)
	{
		$insert = "INSERT INTO structure_export_path (`structure`, `export_path`) VALUES (?, ?)";
		$params = array($structure, $export_path);
		$result = prepared_query($this->_dbcon, $insert, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			elog("Export_path cree pour la structure ".$structure." : ".$export_path);
		}
		else
		{
			elog("Erreur insert Export_path cree pour la structure ".$structure." : ".$export_path." ".mysqli_error($this->_dbcon));
		}
	}

	function getIdfieldTypeByName($name)
	{
		$select = "SELECT idfield_type FROM field_type WHERE name = ?";
		$params = array($name);
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($row = mysqli_fetch_assoc($result))
			{
				return $row['idfield_type'];
			}
		}
		return NULL;
	}
}
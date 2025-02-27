<?php
require_once dirname(__FILE__,2).'/include/const.php';
require_once dirname(__FILE__,2).'/include/fonctions.php';
require_once dirname(__FILE__,2).'/class/ldap.php';

class user {
	
	private $_dbcon;

	private $_uid;
	
	function __construct($dbcon, $uid)
	{
		require_once dirname(__FILE__,2)."/include/dbconnection.php";
		$this->_dbcon = $dbcon;
		$this->_uid = mysqli_real_escape_string($this->_dbcon,$uid);
		$this->save();
	}
	
	function getUid()
	{
		return $this->_uid;
	}
	function getid()
	{
		$select = "SELECT iduser FROM user WHERE uid = ?";
		$params = array($this->_uid);
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				return $res['iduser'];
			}
			else 
			{
				elog("user $this->_uid absent de la table.");
			}
		}
		else 
		{
			elog("erreur select iduser from user. ".mysqli_error($this->_dbcon));
		}
		return 0;
	}
	
	function save()
	{
		$select = "SELECT uid FROM user WHERE uid = ?";
		$params = array($this->_uid);
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if (mysqli_num_rows($result) == 0)
			{
				$insert = "INSERT INTO user (`uid`) VALUES (?)";
				$params = array($this->_uid);
				$result = prepared_query($this->_dbcon, $insert, $params);
				if ( !mysqli_error($this->_dbcon))
				{
					elog("Utilisateur cree : ".$this->_uid);
				}
				else
				{
					elog("Erreur insert Utilisateur : ".$this->_uid." ".mysqli_error($this->_dbcon));
				}
			}
			else {
				//elog("Utilisateur existant : ".$this->_uid);
			}
		}
		else 
		{
			elog("Erreur select Utilisateur : ".$this->_uid." ".mysqli_error($this->_dbcon));
		}
	}
	
	function getRoles($active = true)
	{
		$select = "SELECT uro.idrole, uro.idmodel, uro.active, model.iddecree_type FROM user_role uro LEFT JOIN model ON model.idmodel = uro.idmodel WHERE uro.iduser = ? ";
		$roles = array();
		if ($active)
		{
			$select .= " AND uro.active = 'O'";
		}
		$select .= " ORDER BY model.iddecree_type ";
		$params = array($this->getid());
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($result))
			{
				$roles[] = array('idrole' => $row['idrole'], 'idmodel' => $row['idmodel'], 'iddecree_type' => $row['iddecree_type'], 'active' => $row['active']);
			}
		}
		return $roles;
	}
	
	function getModelRoles($active = true)
	{
		$select = "SELECT uro.idrole, uro.idmodel, uro.active, model.iddecree_type FROM role INNER JOIN user_role uro ON uro.idrole = role.idrole LEFT JOIN model ON model.idmodel = uro.idmodel WHERE role.scope = 'model' AND uro.iduser = ? ";
		$roles = array();
		if ($active)
		{
			$select .= " AND uro.active = 'O'";
		}
		$select .= " ORDER BY model.iddecree_type ";
		$params = array($this->getid());
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($result))
			{
				$roles[] = array('idrole' => $row['idrole'], 'idmodel' => $row['idmodel'], 'iddecree_type' => $row['iddecree_type'], 'active' => $row['active']);
			}
		}
		return $roles;
	}
	
	function setRoles($tab_roles)
	{
		$this->save();
		foreach ($tab_roles as $role)
		{
			$idrole = intval($role['idrole']);
			$idmodel = intval($role['idmodel']);
			$select = "SELECT * FROM user_role WHERE iduser = ? AND idrole = ? AND idmodel = ?";
			$params = array($this->getid(), $idrole, $idmodel);
			$result = prepared_select($this->_dbcon, $select, $params);
			if ( !mysqli_error($this->_dbcon))
			{
				if (mysqli_num_rows($result) == 0)
				{
					$insert = "INSERT INTO user_role (`iduser`,`idrole`, `idmodel`, `createdate`, `createuserid`) ";
					$insert .= " VALUES (?, ?, ?, NOW(), ?)";
					$params = array($this->getid(), $idrole, $idmodel, $role['createuserid']);
					$result = prepared_query($this->_dbcon, $insert, $params);
					if (mysqli_error($this->_dbcon))
					{
						elog("erreur à l'insert du role ".var_export($role)." ".mysqli_error($this->_dbcon));
					}
					else
					{
						elog("le role a été créé ".var_export($role));
					}
				}
				else 
				{
					$update = "UPDATE user_role SET updatedate = NOW(), updateuserid = ?, active = ?";
					$update .= " WHERE iduser = ? AND idrole = ? AND idmodel = ?";
					$params = array($role['updateuserid'], $role['active'], $this->getid(), $idrole, $idmodel);
					$result = prepared_query($this->_dbcon, $update, $params);
					if (mysqli_error($this->_dbcon))
					{
						elog("erreur à l'update du role ".var_export($role)." ".mysqli_error($this->_dbcon));
					}
					else
					{
						elog("le role a été maj ".var_export($role, true));
					}
				}
			}
			else
			{
				elog("Erreur select user_role : ".$this->_uid." ".mysqli_error($this->_dbcon));
			}
		}
	}
	
	function isSuperAdmin($setSession = true)
	{
		if ($setSession)
		{
			if (!isset($_SESSION['issuperadmin']))
			{
				$select = "SELECT iduser_role FROM user_role WHERE iduser = ? AND active = 'O' AND idrole = 3";
				$params = array($this->getid());
				$result = prepared_select($this->_dbcon, $select, $params);
				$_SESSION['issuperadmin'] = FALSE;
				if ( !mysqli_error($this->_dbcon))
				{
					if (mysqli_num_rows($result) > 0)
					{
						elog("L'utilisateur est super admin <br>".$this->_uid);
						$_SESSION['issuperadmin'] = TRUE;
					}
				}
				else
				{
					elog( "L'utilisateur n'est pas super admin <br>");
				}
			}
			return $_SESSION['issuperadmin'];
		}
		else
		{
			$select = "SELECT iduser_role FROM user_role WHERE iduser = ? AND active = 'O' AND idrole = 3";
			$params = array($this->getid());
			$result = prepared_select($this->_dbcon, $select, $params);
			$issuperadmin = FALSE;
			if ( !mysqli_error($this->_dbcon))
			{
				if (mysqli_num_rows($result) > 0)
				{
					elog("L'utilisateur est super admin : ".$this->_uid);
					$issuperadmin = TRUE;
				}
			}
			else
			{
				elog( "L'utilisateur n'est pas super admin : ".$this->_uid);
			}
			return $issuperadmin;
		}
	}
	
	function isDaji()
	{
		if (isset($_SESSION['supannentiteaffectation']))
		{
			$structure = $_SESSION['supannentiteaffectation'];
		}
		else
		{
			$ldap = new ldap();
			$structure = $ldap->getInfos($this->_uid, false)['supannentiteaffectation'];
		}
		if ($structure == 'DGC' || $structure == 'DGCB' || $structure == 'DGCC')
		{
			return true;
		}
		return false;
	}

	function isAdmin()
	{
		$ldap = new ldap();
		if (isset($_SESSION['supannentiteaffectation']))
		{
			$structure = 'structures-'.$_SESSION['supannentiteaffectation'];
		}
		else
		{
			$structure = 'structures-'.$ldap->getInfos($this->_uid, false)['supannentiteaffectation'];
		}
		//print_r2($structure);
		$infostruct = $ldap->getStructureInfos($structure);
		//print_r2($infostruct);
		if (array_key_exists('superGroups', $infostruct) && array_key_exists($structure, $infostruct['superGroups'])
				&& array_key_exists('roles', $infostruct['superGroups'][$structure]) && isset($this->_uid) && array_key_exists($this->_uid, $infostruct['superGroups'][$structure]['roles'])
				&& ($infostruct['superGroups'][$structure]['roles'][$this->_uid]['role'] == 'Responsable administratif' || $infostruct['superGroups'][$structure]['roles'][$this->_uid]['role'] == 'Responsable administrative' || $infostruct['superGroups'][$structure]['roles'][$this->_uid]['role'] == 'Responsable'
						|| $infostruct['superGroups'][$structure]['roles'][$this->_uid]['role'] == 'Responsable administratif par intérim' || $infostruct['superGroups'][$structure]['roles'][$this->_uid]['role'] == 'Responsable administrative par intérim'
						|| strpos($infostruct['superGroups'][$structure]['roles'][$this->_uid]['role'], 'Directrice') !== FALSE || strpos($infostruct['superGroups'][$structure]['roles'][$this->_uid]['role'], 'Directeur') !== FALSE))
		{
			elog("L'utilisateur ".$this->_uid." est ".$infostruct['superGroups'][$structure]['roles'][$this->_uid]['role']." de sa structure ".$structure);
			return TRUE;
		}
		else
		{
			elog("L'utilisateur ".$this->_uid." n'a pas de responsabilité sur sa structure");
			return FALSE;
		}
		elog( "L'utilisateur ".$this->_uid." n'a pas d'affectation <br>");
		return FALSE;
	}

	function isAdminModel()
	{
		$groupe_role = array_column($this->getGroupeRoles($_SESSION['groupes'], 'model', true), 'idmodel', 'idrole');
		return array_key_exists(1, $groupe_role) ? true : false;
	}

	function getAdminSubStructs($structure)
	{
		$retour = array();
		if (substr($structure, 0, 11) != 'structures-')
		{
			$structure = 'structures-'.$structure;
		}
		//$retour[] = $structure;
		$ldap = new ldap();
		$infostruct = $ldap->getStructureInfos($structure);
		foreach($infostruct['subGroups'] as $sousStruct)
		{
			$retour[] = $sousStruct['key'];
			if (array_key_exists('subGroups', $sousStruct))
			{
				$tmp = $this->getAdminSubStructs($sousStruct['key']);
				foreach($tmp as $tmp2)
				{
					$retour[] = $tmp2;
				}
			}
		}
		return $retour;
	}

	function getStructureCodApo()
	{
		$ldap = new ldap();
		$struct = $ldap->getUserAndStructureInfos($this->_uid, false);
		$codapo = (key_exists('codeapo', $struct)) ? $struct['codeapo'] : null;
		return $codapo;
	}

	function getStructurePorteuseApo()
	{
		/*if (isset($_SESSION['structporteuse']))
		{
			return $_SESSION['structporteuse'];
		}*/
		$ldap = new ldap();
		$info_user_struct = $ldap->getUserAndStructureInfos($this->_uid, false);
		$retour = NULL;
		if (key_exists('structcodeapo', $info_user_struct))
		{
			$retour = $info_user_struct['structcodeapo'];
			$_SESSION['structporteuse'] = $retour;
		}
		return $retour;
	}
	
	// Pas utilisé
	function getModelsAdmin()
	{
		$select = "SELECT idmodel FROM user_role WHERE iduser = ? AND active = 'O' AND idrole = 1";
		$params = array($this->getid());
		$result = prepared_select($this->_dbcon, $select, $params);
		$models = array();
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($result))
			{
				$models[] = $row['idmodel'];
			}
		}
		return $models;
	}
	
	// pas utilisé
	function getInfosLdap()
	{
		$ldap = new ldap();
		$infos = $ldap->getInfos($this->_uid, false);
		//print_r2($infos);
	}
	
	function getAllDecrees($idmodel = null, $active = false)
	{
		$listdecrees = array();
		$iduser = $this->getid();
		$params = array();
		$select = " SELECT DISTINCT
						d.iddecree,
						d.number,
						d.year,
						d.createdate,
						d.majdate,
						d.idesignature,
						m.name as modelname,
						dt.name as decreetypename,
						user.uid as uid,
						d.structure,
						d.status
					FROM 
						decree d 
						INNER JOIN model m
							ON m.idmodel = d.idmodel
						INNER JOIN decree_type dt
							ON dt.iddecree_type = m.iddecree_type
						LEFT JOIN user 
							ON user.iduser = d.iduser";
		if ($this->isSuperAdmin() || $this->isDaji())
		{
			$select .= " WHERE d.iduser LIKE '%' ";
		}
		elseif ($this->isAdmin())
		{
			$listStructuresFilles = $this->getAdminSubStructs($_SESSION['supannentiteaffectation']);
			$listStructuresFilles[] = 'structures-'.$_SESSION['supannentiteaffectation'];
			//print_r2($listStructuresFilles);
			$select .= " WHERE (d.structure IN (?";
			$params[] = $listStructuresFilles[0];
			for($i = 1; $i < sizeof($listStructuresFilles); $i++)
			{
				$select .= ', ?';
				$params[] = $listStructuresFilles[$i];
			}
			$select .= ') OR d.iduser = ? )';
			$params[] = $iduser;
		}
		else // user lambda
		{
			$select .= " WHERE d.iduser = '?'";
			$params[] = $iduser;
		}
		if ($idmodel != null)
		{
			$select .= " AND d.idmodel = ?";
			$params[] = $idmodel;
		}
		if (sizeof($params) == 0)
		{
			$result = mysqli_query($this->_dbcon, $select);
		}
		else
		{
			$result = prepared_select($this->_dbcon, $select, $params);
		}
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($result))
			{
				$listdecrees[] = $row;
			}
		}
		return $listdecrees;
	}
	
	function hasAccessDecree($decree)
	{
		// L'utilisateur a créé le document ou est super admin
		if ($this->getid() == $decree['iduser'] || $this->isSuperAdmin() || $this->isDaji())
		{
			return true;
		}
		else
		{
			$groupe_role = array_column($this->getGroupeRoles($_SESSION['groupes'], 'model', true), 'idrole', 'idmodel');
			// L'utilisateur fait partie d'un groupe administrateur du modèle d'arrêté
			if (array_key_exists($decree['idmodel'], $groupe_role) && $groupe_role[$decree['idmodel']] == 1)
			{
				return true;
			}
			// L'utilisateur appartient à la structure pour laquelle le document a été créé
			else
			{
				if ($this->isAdmin())
				{
					$listStructuresFilles = $this->getAdminSubStructs($_SESSION['supannentiteaffectation']);
					$listStructuresFilles[] = 'structures-'.$_SESSION['supannentiteaffectation'];
					if (in_array($decree['structure'], $listStructuresFilles))
					{
						return true;
					}
				}
				$structapo = $this->getStructurePorteuseApo();
				if ($structapo != NULL)
				{
					$groupe_role = array_column($this->getGroupeRoles($_SESSION['groupes'], 'model', true), 'idrole', 'idmodel');
					if ($structapo == $decree['structure'] && array_key_exists($decree['idmodel'], $groupe_role))
					{
						return true;
					}
				}
			}
		}
		return false;
	}
	
	function getUserGroupes($allGroupes)
	{
		$ldap = new ldap();
		$listGroupes = array();
		foreach ($allGroupes as $groupe)
		{
			if ($ldap->isMemberOf($this->_uid, $groupe['grouper']))
			{
				$listGroupes[$groupe['idgroupe']] = $groupe;
			}
		}
		return $listGroupes;
	}
	
	function getGroupeRoles($listGroupes, $scope = NULL, $activemodelonly=false)
	{
		$roles = array();
		$nbgroupes = sizeof($listGroupes);
		if ($nbgroupes > 0)
		{
			$listidgroupes = "(?";
			for($i = 1; $i < $nbgroupes; $i++)
			{
				$listidgroupes .= ', ?';
			}
			$listidgroupes .= ')';
			$params = array_keys($listGroupes);
			$select = "SELECT DISTINCT grr.idmodel, model.iddecree_type, MIN(role.idrole) as idrole FROM groupe_role grr INNER JOIN role ON role.idrole = grr.idrole INNER JOIN model ON model.idmodel = grr.idmodel WHERE grr.active = 'O' AND grr.idgroupe IN ".$listidgroupes;
			if ($scope != NULL)
			{
				$select .= " AND role.scope = ?";
				$params[] = $scope;
			}
			if($activemodelonly)
			{
				$select .= " AND model.active = 'O'";
			}
			$select .= " GROUP BY grr.idmodel, model.iddecree_type ORDER BY model.iddecree_type, grr.idmodel";
			$result = prepared_select($this->_dbcon, $select, $params);
			if ( !mysqli_error($this->_dbcon))
			{
				while ($row = mysqli_fetch_assoc($result))
				{
					$roles[] = $row;
				}
			}
			else 
			{
				elog("Erreur select groupe_role : ".$this->_uid." liste des groupes ".var_export($params, true)." ".mysqli_error($this->_dbcon));
			}
		}
		return $roles;
	}

	function setGroupeRoles($tab_roles)
	{
		$this->save();
		foreach ($tab_roles as $role)
		{
			$select = "SELECT * FROM groupe_role WHERE idrole = ? AND idmodel = ? AND idgroupe = ?";
			$params = array($role['idrole'], $role['idmodel'], $role['idgroupe']);
			$result = prepared_select($this->_dbcon, $select, $params);
			if ( !mysqli_error($this->_dbcon))
			{
				if (mysqli_num_rows($result) == 0)
				{
					$insert = "INSERT INTO groupe_role (`idgroupe`,`idrole`, `idmodel`, `createdate`, `createuserid`, `active`) ";
					$insert .= "VALUES (?, ?, ?, NOW(), ?, ?)";
					$params = array($role['idgroupe'], $role['idrole'], $role['idmodel'], $role['createuserid'], $role['active']);
					$result = prepared_query($this->_dbcon, $insert, $params);
					if (mysqli_error($this->_dbcon))
					{
						elog("erreur à l'insert du groupe role ".var_export($role)." ".mysqli_error($this->_dbcon));
					}
					else
					{
						elog("le groupe role a été créé ".var_export($role, true));
					}
				}
				else
				{
					$update = "UPDATE groupe_role SET updatedate = NOW(), updateuserid = ?, active = ?";
					$update .= " WHERE idgroupe = ? AND idrole = ? AND idmodel = ?";
					$params = array($role['updateuserid'], $role['active'], $role['idgroupe'], $role['idrole'], $role['idmodel']);
					$result = prepared_query($this->_dbcon, $update, $params);
					if (mysqli_error($this->_dbcon))
					{
						elog("erreur à l'update du groupe role ".var_export($role)." ".mysqli_error($this->_dbcon));
					}
					else
					{
						elog("le groupe role a été maj ".var_export($role, true));
					}
				}
			}
			else
			{
				elog("Erreur select groupe_role : ".$role['idgroupe']." ".mysqli_error($this->_dbcon));
			}
		}
	}
	
	function getDecreesBy($criteres, $limit, $last_id = 0, $orderby = -1, $desc = false, $nousercontrol = false)
	{
		// TODO
		$listdecrees = array();
		$iduser = $this->getid();
		$params = array();
		$select = " SELECT DISTINCT
						concat(d.year, '-', d.number) as numero,
						d.filename,
						m.name as modelname,
						d.structure,
						user.uid as uid,
						u2.uid as uidmaj,
						IFNULL(d.majdate, d.createdate) AS majdate,
						d.status,
						d.iddecree,
						d.number,
						d.year,
						d.createdate,
						d.idesignature,
						d.univ_year,
						dt.name as decreetypename,
						YEAR(IFNULL(d.majdate, d.createdate)) as y,
						MONTH(IFNULL(d.majdate, d.createdate)) as m,
						DAY(IFNULL(d.majdate, d.createdate)) as d
					FROM
						decree d
						INNER JOIN model m
							ON m.idmodel = d.idmodel
						INNER JOIN decree_type dt
							ON dt.iddecree_type = m.iddecree_type
						LEFT JOIN user
							ON user.iduser = d.iduser
						LEFT JOIN user u2
							ON u2.iduser = d.idmajuser";
		if ($this->isSuperAdmin() || $this->isDaji() || $nousercontrol) // Accès à tous les arrêtés
		{
			$select .= " WHERE (d.iduser LIKE '%' ";
			if (array_key_exists('idmodel', $criteres) && $criteres['idmodel'] != null)
			{
				$select .= " AND d.idmodel = ?";
				$params[] = $criteres['idmodel'];
			}
		}
		else
		{
			// on souhaite récupérer la structure porteuse du code composante apogée
			// pour donner accès aux arrêtés créés pour la composante aux personnels
			$hasstructapo = false;
			$structporteuse = $this->getStructurePorteuseApo();
			if ($structporteuse !== NULL  && key_exists("allcomp", $criteres))
			{
				$hasstructapo = true;
			}
			if (array_key_exists('idmodel', $criteres) && $criteres['idmodel'] != null)
			{
				if ($hasstructapo)
				{
					$select .= " WHERE (((d.iduser = ? OR d.structure = ?) AND d.idmodel = ?) ";
					$params[] = $iduser;
					$params[] = $structporteuse;
					$params[] = $criteres['idmodel'];
				}
				else
				{
					$select .= " WHERE ((d.iduser = ? AND d.idmodel = ?) ";
					$params[] = $iduser;
					$params[] = $criteres['idmodel'];
				}
			}
			else
			{
				$select .= " WHERE ( ? ";
				$params[] = '';
			}
			$groupes = $this->getGroupsZorro();
			require_once dirname(__FILE__,2).'/class/reference.php';
			$ref = new reference($this->_dbcon);
			$admin = false;
			if ($this->isAdmin()) // Responsable de structure => accès à tous les arrêtés du modèle pour sa structure
			{
				$admin = true;
				$ldap = new ldap();
				$structure = isset($_SESSION['supannentiteaffectation']) ? $_SESSION['supannentiteaffectation'] : $ldap->getInfos($this->_uid, false)['supannentiteaffectation'];
				$listStructuresFilles = $this->getAdminSubStructs($structure);
				$listStructuresFilles[] = 'structures-'.$structure;
				$select .= " OR (d.structure IN (?";
				$params[] = $listStructuresFilles[0];
				for($i = 1; $i < sizeof($listStructuresFilles); $i++)
				{
					$select .= ', ?';
					$params[] = $listStructuresFilles[$i];
				}
				if ($hasstructapo)
				{
					$select .= ', ?';
					$params[] = $structporteuse;
				}
				$select .= ') ';
			}
			if (array_key_exists('idmodel', $criteres) && $criteres['idmodel'] != null)
			{
				$select2 = '';
				$params2 = array();
				$hasidmodel = false;
				foreach($groupes as $groupe)
				{
					$roles = $ref->getRoleForGroupModel($groupe['idgroupe'], $criteres['idmodel']);
					foreach ($roles as $role)
					{
						if ($role['idrole'] == 1) // Admin du modèle => accès à tous les arrêtés du modèle
						{
							$select2 .= " OR (d.iduser LIKE '%' AND d.idmodel = ?) ";
							$params2[] = $criteres['idmodel'];
						}
						else
						{
							if ($admin) // Responsable de structure => accès à tous les arrêtés du modèle pour sa structure
							{
								if (!$hasidmodel)
								{
									$hasidmodel = true;
									$select .= 'AND d.idmodel IN ( ';
								}
								$select .= '?, ';
								$params[] = $criteres['idmodel'];
							}
							else
							{
								$select .= ' AND d.idmodel = ? ';
								$params[] = $criteres['idmodel'];
							}
						}
					}
				}
				if ($admin)
				{
					if ($hasidmodel)
					{
						$select = substr($select, 0, -2).") ".$select2.")";
					}
					else
					{
						$select .= $select2.")";
					}
				}
				else
				{
					$select .= $select2;
				}
				$params = array_merge($params, $params2);
			}
			else
			{
				$roles = $this->getGroupeRoles($groupes,null,true);
				$select2 = '';
				$params2 = array();
				$hasidmodel = false;
				foreach ($roles as $role)
					{
						if ($role['idrole'] == 1) // Admin du modèle => accès à tous les arrêtés du modèle
						{
							$select2 .= " OR (d.iduser LIKE '%' AND d.idmodel = ?) ";
							$params2[] = $role['idmodel'];
						}
						else
						{
							if ($admin) // Responsable de structure => accès à tous les arrêtés du modèle pour sa structure
							{
								if (!$hasidmodel)
								{
									$hasidmodel = true;
									$select .= 'AND d.idmodel IN ( ';
								}
								$select .= '?, ';
								$params[] = $role['idmodel'];
							}
							else
							{
								if ($hasstructapo)
								{
									$select .= ' OR ((d.iduser = ? OR d.structure = ?) AND d.idmodel = ?) ';
									$params[] = $iduser;
									$params[] = $structporteuse;
									$params[] = $role['idmodel'];
								}
								else
								{
									$select .= ' OR (d.iduser = ? AND d.idmodel = ?) ';
									$params[] = $iduser;
									$params[] = $role['idmodel'];
								}
							}
						}
					}
				if ($admin)
				{
					if ($hasidmodel)
					{
						$select = substr($select, 0, -2).") ".$select2.")";
					}
					else
					{
						$select .= $select2.")";
					}
				}
				else
				{
					$select .= $select2;
				}
				$params = array_merge($params, $params2);
			}
		}
		$select .= ") AND d.status != '".STATUT_REMPLACE."' ";
		if (sizeof($criteres) == 0)
		{
			$select .= " AND d.status NOT IN ('".STATUT_ANNULE."', '".STATUT_CORBEILLE."', '".STATUT_SUPPR_ESIGN."') ";
		}
		else
		{
			if (array_key_exists('year', $criteres) && $criteres['year'] != null)
			{
				$select .= " AND d.year = ?";
				$params[] = $criteres['year'];
			}
			if (array_key_exists('createyear', $criteres) && $criteres['createyear'] != null)
			{
				$select .= " AND univ_year = ? ";
				$params[] = $criteres['createyear'];
			}
			if (array_key_exists('findnum', $criteres) && $criteres['findnum'] != null)
			{
				$select .= " AND d.number = ?";
				$params[] = $criteres['findnum'];
			}
			if (array_key_exists('status', $criteres) && $criteres['status'] != null)
			{
				$select .= " AND d.status = ?";
				$params[] = $criteres['status'];
			}
			else
			{
				$select .= " AND d.status NOT IN ('".STATUT_ANNULE."', '".STATUT_CORBEILLE."', '".STATUT_SUPPR_ESIGN."') ";
			}
			if (array_key_exists('contenu', $criteres) && $criteres['contenu'] != '')
			{
				$select .= " AND (concat(d.year,'/',d.number) = ? ";
				$params[] = $criteres['contenu'];
				$list_mots = explode(" ", $criteres['contenu']);
				if (sizeof($list_mots) > 0)
				{
					$select .= " OR exists (SELECT dfi.value FROM decree_field dfi WHERE dfi.iddecree = d.iddecree AND (LOWER(dfi.value) LIKE ? ";
					$params[] = '%'.mb_strtolower($list_mots[0],'UTF-8').'%';
					for ($i = 1; $i < sizeof($list_mots); $i++)
					{
						$select .= " OR LOWER(dfi.value) LIKE ? ";
						$params[] = '%'.mb_strtolower($list_mots[$i],'UTF-8').'%';
					}
					$select .= "))";
				}
				$select .= ")";
			}
			if (array_key_exists('composante', $criteres) && $criteres['composante'] != '')
			{
				$select .= " AND d.structure = ? ";
				$params[] = $criteres['composante'];
			}
			if (array_key_exists('esign', $criteres) && $criteres['esign'] != '')
			{
				$select .= " AND d.idesignature IS NOT NULL ";
			}
		}
		$select .= ' ORDER BY ';
		if ($orderby >= 0)
		{
			if ($orderby == 5)
			{
				if ($desc == 'TRUE')
				{
					$select .= ' y DESC, m DESC, d DESC ';
				} else {
				$select .= ' y, m, d ';
				}
			}
			elseif ($orderby == 0)
			{
				if ($desc == 'TRUE')
				{
					$select .= ' 10 DESC, 9 DESC ';
				} else {
				$select .= ' 10, 9 ';
				}
			}
			else
			{
				$select .= $orderby+1;
				if ($desc == 'TRUE')
				{
					$select .= ' DESC ';
				}
			}
			$select .= ', ';
		}
		$select .= ' d.iddecree DESC ';
		if (is_int($limit) && $limit > 0)
		{
			$select .= " LIMIT ";
			if ($last_id >= 0)
			{
				$select .= " ? , ";
				$params[] = $last_id;
			}
			$select .= " ? ";
			$params[] = $limit;
		}
		if (sizeof($params) == 0)
		{
			$result = mysqli_query($this->_dbcon, $select);
		}
		else {
			$result = prepared_select($this->_dbcon, $select, $params);
		}
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($result))
			{
				$listdecrees[] = $row;
			}
		}
		return $listdecrees;
	}

	function getGroupsZorro()
	{
		require_once dirname(__FILE__,2).'/class/reference.php';
		$ref = new reference($this->_dbcon);
		$ldap = new ldap();
		$groupes = $ref->getAllGroupes();
		$retour = array();
		foreach ($groupes as $groupe)
		{
			if ($ldap->isMemberOf($this->_uid,$groupe['grouper']))
			{
				$retour[$groupe['idgroupe']] = $groupe;
			}
		}
		return $retour;
	}

}
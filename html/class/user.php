<?php
require_once './include/const.php';
require_once './include/fonctions.php';
require_once './class/ldap.php';

class user {
	
	private $_dbcon;

	private $_uid;
	
	function __construct($dbcon, $uid)
	{
		require_once ("./include/dbconnection.php");
		$this->_uid = $uid;
		$this->_dbcon = $dbcon;
	}
	
	function getid()
	{
		$select = "SELECT iduser FROM user WHERE uid = '$this->_uid'";
		$result = mysqli_query($this->_dbcon, $select);
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
		$select = "SELECT uid FROM user WHERE uid = '".$this->_uid."'";
		$res = mysqli_query($this->_dbcon, $select);
		if ( !mysqli_error($this->_dbcon))
		{
			if (mysqli_num_rows($res) == 0)
			{
				$insert = "INSERT INTO user (`uid`) VALUES ('".$this->_uid."')";
				mysqli_query($this->_dbcon, $insert);
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
				elog("Utilisateur existant : ".$this->_uid);
			}
		}
		else 
		{
			elog("Erreur select Utilisateur : ".$this->_uid." ".mysqli_error($this->_dbcon));
		}
	}
	
	function getRoles($active = true)
	{
		$select = "SELECT uro.idrole, uro.idmodel, uro.active, model.iddecree_type FROM user_role uro LEFT JOIN model ON model.idmodel = uro.idmodel WHERE uro.iduser = ".$this->getid()." ";
		$roles = array();
		if ($active)
			$select .= " AND uro.active = 'O'";
		$select .= " ORDER BY model.iddecree_type ";
		$res = mysqli_query($this->_dbcon, $select);
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($res))
			{
				$roles[] = array('idrole' => $row['idrole'], 'idmodel' => $row['idmodel'], 'iddecree_type' => $row['iddecree_type'], 'active' => $row['active']);
			}
		}
		return $roles;
	}
	
	function getModelRoles($active = true)
	{
		$select = "SELECT uro.idrole, uro.idmodel, uro.active, model.iddecree_type FROM role INNER JOIN user_role uro ON uro.idrole = role.idrole LEFT JOIN model ON model.idmodel = uro.idmodel WHERE role.scope = 'model' AND uro.iduser = ".$this->getid()." ";
		$roles = array();
		if ($active)
			$select .= " AND uro.active = 'O'";
			$select .= " ORDER BY model.iddecree_type ";
			$res = mysqli_query($this->_dbcon, $select);
			if ( !mysqli_error($this->_dbcon))
			{
				while ($row = mysqli_fetch_assoc($res))
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
			$select = "SELECT * FROM user_role WHERE iduser = ". $this->getid() ." AND idrole = ".$role['idrole']." AND idmodel = ".$role['idmodel'];
			$result = mysqli_query($this->_dbcon, $select);
			if ( !mysqli_error($this->_dbcon))
			{
				if (mysqli_num_rows($result) == 0)
				{
					$insert = "INSERT INTO user_role (`iduser`,`idrole`, `idmodel`, `createdate`, `createuserid`) ";
					$insert .= "VALUES (".$this->getid().", ".$role['idrole'].", ".$role['idmodel'].", NOW(), '".$role['createuserid']."')";
					mysqli_query($this->_dbcon, $insert);
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
					$update = "UPDATE user_role SET updatedate = NOW(), updateuserid = ".$role['updateuserid'].", active = '".$role['active']."'";
					$update .= " WHERE iduser = ".$this->getid()." AND idrole = ".$role['idrole']." AND idmodel = ".$role['idmodel'];
					mysqli_query($this->_dbcon, $update);
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
	
	function isSuperAdmin()
	{
		if (!isset($_SESSION['issuperadmin']))
		{
			$select = "SELECT iduser_role FROM user_role WHERE iduser = '".$this->getid()."' AND active = 'O' AND idrole = 3";
			$res = mysqli_query($this->_dbcon, $select);
			$_SESSION['issuperadmin'] = FALSE;
			if ( !mysqli_error($this->_dbcon))
			{
				if (mysqli_num_rows($res) > 0)
				{
					elog("L'utilisateur est super admin <br>".$this->_uid);
					$_SESSION['issuperadmin'] = TRUE;
				}
			}
			elog( "L'utilisateur n'est pas super admin <br>");
		}
		return $_SESSION['issuperadmin'];
	}
	
	function isAdmin()
	{
		$select = "SELECT iduser_role FROM user_role WHERE iduser = '".$this->getid()."' AND active = 'O' AND idrole = 1";
		$res = mysqli_query($this->_dbcon, $select);
		if ( !mysqli_error($this->_dbcon))
		{
			if (mysqli_num_rows($res) > 0)
			{
				elog("L'utilisateur est admin <br>");
				return TRUE;
			}
		}
		elog( "L'utilisateur n'est pas admin <br>");
		return FALSE;
	}
	
	function getStructure()
	{
		$ldap = new ldap();
		return $ldap->getInfos($this->_uid)['supannrefid'];
	}
	
	function getModelsAdmin()
	{
		$select = "SELECT idmodel FROM user_role WHERE iduser = '".$this->getid()."' AND active = 'O' AND idrole = 1";
		$models = array();
		$res = mysqli_query($this->_dbcon, $select);
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($res))
			{
				$models[] = $row['idmodel'];
			}
		}
		return $models;
	}
	
	function getInfosLdap()
	{
		$ldap = new ldap();
		$infos = $ldap->getInfos($this->_uid);
		var_dump($infos);
	}
	
	function getAllDecrees($idmodel = null, $active = false)
	{
		$listdecrees = array();
		$iduser = $this->getid();
		$select = " SELECT DISTINCT
						d.number,
						d.year,
						d.createdate,
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
						LEFT JOIN user_role ur
							ON ur.iduser = d.iduser
								AND ur.idmodel = m.idmodel
						LEFT JOIN user 
							ON user.iduser = d.iduser";
		if ($this->isSuperAdmin())
		{
			$select .= " WHERE d.iduser LIKE '%' ";
		}
		elseif ($this->isAdmin())
		{
			$select .= " WHERE d.structure = ".$this->getStructure();
		}
		else // user lambda
		{
			$select .= " WHERE d.iduser = ".$iduser;
		}
		if ($idmodel != null)
		{
			$select .= " AND d.idmodel = ".$idmodel;
		}
		if ($active)
		{
			$select .= " AND ur.active = 'O'";
		}
		$res = mysqli_query($this->_dbcon, $select);
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($res))
			{
				$listdecrees[] = $row;//array('number' => $row['number'], 'year' => $row['year'], 'createdate' => $row['createdate'], 'idesignature' => $row['idesignature'], 'modelname' => $row['modelname'], 'decreetypename' => $row['decreetypename'], 'status' => $row['status']);
			}
		}
		return $listdecrees;
	}
	
	function hasAccessDecree($decree)
	{
		if ($this->getId() == $decree['iduser'] || $this->isSuperAdmin())
		{
			return true;
		}
		else {
			if ($this->getStructure() == $decree['structure'])
			{
				$select = "SELECT * FROM user_role WHERE iduser = ".$this->getId()." AND idmodel = ".$decree['idmodel']." AND idrole = 1 AND active = 'O'" ;
				$res = mysqli_query($this->_dbcon, $select);
				if ( !mysqli_error($this->_dbcon))
				{
					if (mysqli_num_rows($res) > 0)
					{
						return true;
					}
				}
			}
		}
		return false;
	}
}
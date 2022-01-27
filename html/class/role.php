<?php
require_once './include/const.php';
require_once './include/fonctions.php';

class role {
	
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
		$select = "SELECT id FROM user WHERE uid = '$this->_uid'";
		$result = mysqli_query($this->_dbcon, $select);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				return $res['id'];
			}
			else 
			{
				elog("user $this->_uid absent de la table.");
			}
		}
		else 
		{
			elog("erreur select id from user.".mysqli_error($this->_dbcon));
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
		$select = "SELECT * FROM user_role WHERE uid = '".$this->_uid."' ";
		$roles = array();
		if ($active)
			$select .= " AND active = 'O'";
		$res = mysqli_query($this->_dbcon, $select);
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($res))
			{
				$roles[] = array('role' => $row['idrole'], 'model' => $row['idmodel'], 'active' => $row['active']);
			}
		}
		return $roles;
	}
	
	function setRoles($tab_roles)
	{
		$this->save();
		foreach ($tab_roles as $role)
		{
			$select = "SELECT * FROM user_role WHERE iduser = $this->_uid AND idrole = ".$role['idrole']." AND idmodele = ".$role['idmodel'];
			$result = mysqli_query($this->_dbcon, $select);
			if ( !mysqli_error($this->_dbcon))
			{
				if (mysqli_num_rows($result) == 0)
				{
					$insert = "INSERT INTO user_role (`iduser`,`idrole`, `idmodele`, `createdate`, `createuserid`) ";
					$insert .= "VALUES (".$this->getid().", ".$role['idrole'].", ".$role['idmodel'].", ".$role['createdate'].", ".$role['createuserid'];
					mysqli_query($this->_dbcon, $insert);
					if (mysqli_error($this->_dbcon))
					{
						elog("erreur à l'insert du role ".var_export($role));
					}
				}
				else 
				{
					$update = "UPDATE user_role SET updatedate = ".$role['updatedate'].", updateuserid = ".$role['updateuserid'].", active = ".$role['active'];
					$update .= " WHERE iduser = ".$this->getid()." AND idrole = ".$role['idrole']." AND idmodel = ".$role['idmodel'];
					mysqli_query($this->_dbcon, $update);
					if (mysqli_error($this->_dbcon))
					{
						elog("erreur à l'update du role ".var_export($role));
					}
				}
			}
		}
	}
}
<?php
require_once './include/const.php';
require_once './include/fonctions.php';

class decree {
	
	private $_dbcon;
	
	private $_year;
	
	private $_number;
	
	function __construct($dbcon, $year, $number)
	{
		require_once ("./include/dbconnection.php");
		$this->_year = $year;
		$this->_number = $number;
		$this->_dbcon = $dbcon;
	}
	
	function getid()
	{
		$select = "SELECT iddecree FROM decree WHERE number = '$this->_number' AND year = '$this->_year'";
		$result = mysqli_query($this->_dbcon, $select);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				return $res['iddecree'];
			}
			else 
			{
				elog("decree $this->_year / $this->_number absent de la table.");
			}
		}
		else 
		{
			elog("erreur select iddecree from decree.".mysqli_error($this->_dbcon));
		}
		return 0;
	}
	
	function getDecree()
	{
		$select = "SELECT * FROM decree WHERE number = ".$this->_number." AND year = ".$this->_year;
		$result = mysqli_query($this->_dbcon, $select);
		$decree = NULL;
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				$decree = $res;
			}
		}
		else
		{
			elog("erreur SELECT * FROM decree WHERE number = $this->_number AND year = $this->_year.".mysqli_error($this->_dbcon));
		}
		return $decree;
	}
	
	function save($iduser, $idmodel, $structure, $idesignature=null, $status = 'p')
	{
		if ($idesignature != null)
		{
			$insert = "INSERT INTO decree (`year`, `number`, `createdate`, `iduser`, `idmodel`, `idesignature`, `status`, `structure`) VALUES ($this->_year, $this->_number, NOW(), $iduser, $idmodel, $idesignature, '$status', '$structure')";
		}
		else 
		{
			$insert = "INSERT INTO decree (`year`, `number`, `createdate`, `iduser`, `idmodel`, `status`, `structure`) VALUES ($this->_year, $this->_number, NOW(),  $iduser, $idmodel, '$status', '$structure')";
		}
		elog(var_export($insert, true));
		mysqli_query($this->_dbcon, $insert);
		if ( !mysqli_error($this->_dbcon))
		{
			elog("Decree cree : ".$this->_number." / ".$this->_year);
		}
		else
		{
			elog("Erreur insert Decree : ".$this->_number." / ".$this->_year." ".mysqli_error($this->_dbcon));
		}
	}
	
	function setFields($fields)
	{
		foreach ($fields as $field)
		{
			$insert = "INSERT INTO decree_field (`iddecree`, `idmodel_field`, `value`) VALUES (".$this->getid().", ".$field['idmodel_field'].", '".$field['value']."')";
			mysqli_query($this->_dbcon, $insert);
			if ( !mysqli_error($this->_dbcon))
			{
				elog("Field cree pour le decree $this->_number : ".var_export($field, true));
			}
			else
			{
				elog("Erreur insert Field : ".$this->_number." ".mysqli_error($this->_dbcon));
			}
		}
	}
	function getFields()
	{
		$select = "SELECT * FROM decree_field WHERE iddecree = ".$this->getid();
		$result = mysqli_query($this->_dbcon, $select);
		$fields = array();
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($result))
			{
				$fields[$row['idmodel_field']][] = $row;
			}
		}
		else
		{
			elog("Erreur select decree_field : ".$this->getid()." ".mysqli_error($this->_dbcon));
		}
		return $fields;
	}
	
	function unsetNumber($iduser)
	{
		$update = "UPDATE decree SET number = NULL, idmajuser = ".$iduser.", majdate = NOW(), status = 'a' WHERE iddecree = ".$this->getid();
		mysqli_query($this->_dbcon, $update);
		if ( !mysqli_error($this->_dbcon))
		{
			elog("Numéro ".$this->_year.'/'.$this->_number." supprimé pour le decree id : ".$this->getid()." par iduser ".$iduser);
		}
		else
		{
			elog("Erreur à la suppression du numéro ".$this->_year.'/'.$this->_number." du decree : ".$this->getId()." ".mysqli_error($this->_dbcon));
		}
	}
}
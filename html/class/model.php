<?php
require_once './include/const.php';
require_once './include/fonctions.php';

class model {
	
	private $_dbcon;
	
	private $_idmodel;
	
	private $_iddecree_type;
	
	private $_name;
	
	private $_model_path;
	
	private $_export_path;
	
	function __construct($dbcon, $idmodel)
	{
		require_once ("./include/dbconnection.php");
		$this->_idmodel = intval($idmodel);
		$this->_dbcon = $dbcon;
	}
	
	function getid()
	{
		return $this->_idmodel;
	}
	
	
	function getModelInfo()
	{
		$select = "SELECT model.*, dty.name as namedecree_type FROM model INNER JOIN decree_type dty ON dty.iddecree_type = model.iddecree_type WHERE model.idmodel = ?";
		$params = array($this->_idmodel);
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				return $res;
			}
			else
			{
				elog("model $this->_idmodel absent de la table.");
			}
		}
		else
		{
			elog("erreur select * from model $this->_idmodel ".mysqli_error($this->_dbcon));
		}
		return 0;
	}
	
	function getDecreeType()
	{
		$select = "SELECT dty.* FROM model mod INNER JOIN decree_type dty ON dtY.iddecree_type = mod.iddecree_type WHERE mod.idmodel = ?";
		$params = array($this->_idmodel);
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				return $res;
			}
			else
			{
				elog("decreetype pour le model $this->_idmodel absent de la base.");
			}
		}
		else
		{
			elog("erreur select decreetype for model. ".mysqli_error($this->_dbcon));
		}
		return 0;
	}
	
	function getfile()
	{
		$select = "SELECT model_path FROM model WHERE idmodel = ?";
		$params = array($this->_idmodel);
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				return $res['model_path'];
			}
			else 
			{
				elog("model $this->_idmodel absent de la table.");
			}
		}
		else 
		{
			elog("erreur select model_path from model. ".mysqli_error($this->_dbcon));
		}
		return 0;
	}
	
	function getModelFields()
	{
		$select = "SELECT mfi.idmodel_field, mfi.number, mfi.auto, mfi.auto_value, fty.* FROM model_field mfi INNER JOIN field_type fty ON mfi.idfield_type = fty.idfield_type WHERE mfi.idmodel = ? ORDER BY mfi.order";
		$params = array($this->_idmodel);
		$result = prepared_select($this->_dbcon, $select, $params);
		$fields = array();
		if ( !mysqli_error($this->_dbcon))
		{
			while ($res = mysqli_fetch_assoc($result))
			{
				$fields[] = $res;
			}
		}
		else
		{
			elog("erreur select fields from model. ".mysqli_error($this->_dbcon));
		}
		return $fields;
	}
	
	function getQueryField($field_type)
	{
		$select = "SELECT qfi.schema, qfi.query, qmf.query_clause FROM query_field qfi LEFT JOIN query_model_field qmf ON qmf.idquery_field = qfi.idquery_field  AND qmf.idmodel = ? WHERE qfi.idfield_type = ?";
		$params = array($this->_idmodel, $field_type);
		$result = prepared_select($this->_dbcon, $select, $params);
		$fields = array();
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				$fields = $res;
			}
		}
		else
		{
			elog("erreur select query_model_fields. ".mysqli_error($this->_dbcon));
		}
		return $fields;
	}
	
	function getNumeroId()
	{
		$select = "SELECT idmodel_field FROM model_field WHERE idmodel = ? AND idfield_type = 1";
		$params = array($this->_idmodel);
		$result = prepared_select($this->_dbcon, $select, $params);
		$numeroid = 0;
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_row($result))
			{
				$numeroid = $res[0];
			}
		}
		else
		{
			elog("erreur select fields from model. ".mysqli_error($this->_dbcon));
		}
		return $numeroid;		
	}
	
}
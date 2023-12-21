<?php
require_once dirname(__FILE__,2).'/include/const.php';
require_once dirname(__FILE__,2).'/include/fonctions.php';

class reference {
	
	private $_dbcon;
	private $_rdbApo;
	
	function __construct($dbcon=null, $rdbApo=null)
	{
		require_once (dirname(__FILE__,2)."/include/dbconnection.php");
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
				if (array_key_exists('code', $tab) && $tab['code'] != NULL AND is_array($tab['code']))
				{
					foreach ($tab['code'] as $code)
					{
						error_log("bind params : ".var_export($code, true));
						oci_bind_by_name($sth, $code['codename'], $code['codevalue']);
					}
					error_log("bind_params terminé");
				}
				$fields = array();
				if ( !oci_error($this->_rdbApo))
				{
					$res = oci_execute($sth);
					if ($res)
					{
						while ($res = oci_fetch_row($sth))
						{
							$fields[] = array ('code' => $res[0], 'value' => $res[1], 'cmp' => array_key_exists(2, $res) ? $res[2] : null);
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
				STATUT_ERREUR => 'Document non trouvé sur eSignature',
				STATUT_HORS_ZORRO => 'Document Hors Zorro');
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

	function getAnneeUni($yearafter = 0)
	{

		if (date('m') < 9)
		{
			$year = (date('Y')-1+$yearafter).'-'.(date('Y')+$yearafter);
		}
		else
		{
			$year = (date('Y')+$yearafter).'-'.(date('Y')+1+$yearafter);
		}
		return $year;
	}

	function idEsignatureExists($id)
	{
		$select = "SELECT iddecree FROM decree WHERE idesignature = ?";
		$params = array($id);
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($row = mysqli_fetch_assoc($result))
			{
				return $row['iddecree'];
			}
		}
		return FALSE;
	}

	function getDecreeByIdEsignature($id)
	{
		$select = "SELECT iddecree FROM decree WHERE idesignature = ?";
		$params = array($id);
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($row = mysqli_fetch_assoc($result))
			{
				require_once dirname(__FILE__,2)."/class/decree.php";
				return new decree($this->_dbcon, null, null, $row['iddecree']);
			}
		}
		return FALSE;
	}

	function getAllDecreeEnCoursSign()
	{
		require_once dirname(__FILE__,2)."/class/decree.php";
		$select = "SELECT d.iddecree FROM decree d WHERE d.status IN ('".STATUT_EN_COURS."', '".STATUT_CORBEILLE."', '".STATUT_ERREUR."') OR (d.status = '".STATUT_REFUSE."' AND NOT EXISTS (SELECT ei.idesignature FROM esignature_info ei WHERE d.idesignature = ei.idesignature AND ei.refuse_comment IS NOT NULL))";
		$result = mysqli_query($this->_dbcon, $select);
		$list = array();
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($result))
			{
				$list[] = new decree($this->_dbcon, null, null, $row['iddecree']);
			}
		}
		return $list;
	}

	function getModeMaintenance()
	{
		$select= "SELECT value FROM constant WHERE name = 'MAINTENANCE'";
		$result = mysqli_query($this->_dbcon, $select);
		if (mysqli_error($this->_dbcon))
		{
			elog("Erreur a l'execution de la requete select MAINTENANCE.");
		}
		else {
			if ($row = mysqli_fetch_assoc($result))
			{
				//elog('MODE MAINTENANCE :'.$row['value']);
				return ($row['value'] == 'TRUE' ? TRUE : FALSE);
			}
		}
		return FALSE;
	}

	function setModeMaintenance($mode)
	{
		$update = "UPDATE constant SET constant.value = ? WHERE constant.name = 'MAINTENANCE'";
		$params = array($mode);
		$result = prepared_query($this->_dbcon, $update, $params);
		if (mysqli_error($this->_dbcon))
		{
			elog("Erreur a l'execution de la requete update MAINTENANCE : ".htmlspecialchars($mode));
			return FALSE;
		}
		else
		{
			elog('SET MODE MAINTENANCE : '.htmlspecialchars($mode));
			return TRUE;
		}
	}

	function getListComp()
	{
		return $this->executeQuery(array('schema' => 'APOGEE', 'query' => "SELECT cod_cmp, lic_cmp FROM composante WHERE tem_en_sve_cmp = 'O' ORDER BY lic_cmp"));
	}

	function getYears()
	{
		$select = 'SELECT DISTINCT year FROM decree';
		$result = mysqli_query($this->_dbcon, $select);
		$years = array();
		if (mysqli_error($this->_dbcon))
		{
			elog("Erreur a l'execution de la requete select year.");
		}
		else {
			while ($row = mysqli_fetch_assoc($result))
			{
				$years[] = $row['year'];
			}
		}
		return $years;
	}

	function getCreationYears()
	{
		$select = "SELECT DISTINCT case when month(createdate) >= 9 THEN year(createdate) else year(createdate) - 1 end as \"year\" FROM decree ORDER BY iddecree desc";
		$result = mysqli_query($this->_dbcon, $select);
		$years = array();
		if (mysqli_error($this->_dbcon))
		{
			elog("Erreur a l'execution de la requete select year.");
		}
		else {
			while ($row = mysqli_fetch_assoc($result))
			{
				$years[] = $row['year'];
			}
		}
		return $years;
	}

	function getRoleForGroupModel($idgroupe, $idmodel)
	{
		$sql = "SELECT idgroupe, idrole, idmodel, active FROM groupe_role WHERE idgroupe = ? AND idmodel = ? AND active = 'O'";
		$param = array($idgroupe,$idmodel);
		$result = prepared_select($this->_dbcon, $sql, $param);
		$listroles = array();
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($result))
			{
				$listroles[] = $row;
			}
		}
		return $listroles;
	}

	function getObjectsList()
	{
		$sql = "SELECT idobject_type, name FROM object_type WHERE active = 'O'";
		$result = mysqli_query($this->_dbcon, $sql);
		$listobjects = array();
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($result))
			{
				$listobjects[$row['idobject_type']] = $row;
			}
		}
		return $listobjects;
	}

	function getObjectTypePrices($idobject_type)
	{
		$sql = "SELECT idobject, new_tarif_public, new_tarif_etu_pers FROM object WHERE idobject_type = ? AND active = 'O'";
		$param = array($idobject_type);
		$result = prepared_select($this->_dbcon, $sql, $param);
		$price = array('idobject' =>'0', 'new_tarif_public' => '', 'new_tarif_etu_pers' => '');
		if ( !mysqli_error($this->_dbcon))
		{
			if ($row = mysqli_fetch_assoc($result))
			{
				$price = $row;
			}
		}
		return $price;
	}

	function getObjectPricesById($idobject)
	{
		$sql = "SELECT idobject_type, old_tarif_public, new_tarif_public, old_tarif_etu_pers, new_tarif_etu_pers FROM object WHERE idobject = ? ";
		$param = array($idobject);
		$result = prepared_select($this->_dbcon, $sql, $param);
		$price = array('idobject_type' =>'0', 'old_tarif_public' => '', 'new_tarif_public' => '', 'old_tarif_etu_pers' => '', 'new_tarif_etu_pers' => '');
		if ( !mysqli_error($this->_dbcon))
		{
			if ($row = mysqli_fetch_assoc($result))
			{
				$price = $row;
			}
		}
		return $price;
	}

	function setObjectPrices($idobject_type, $oldtarifpub, $newtarifpub, $oldtarifep, $newtarifep, $iddecree='')
	{
		if ($iddecree == '')
		{
			$sql = "INSERT INTO object (idobject_type, old_tarif_public, new_tarif_public, old_tarif_etu_pers, new_tarif_etu_pers) VALUES (?, ?, ?, ?, ?)";
			$param = array($idobject_type, $this->format_price($oldtarifpub), $this->format_price($newtarifpub), $this->format_price($oldtarifep), $this->format_price($newtarifep));
			$result = prepared_query($this->_dbcon, $sql, $param);
			if ( !mysqli_error($this->_dbcon))
			{
				elog("Tarif objet promotionnel insere ".var_export($param, true));
				return mysqli_insert_id($this->_dbcon);
			}
			else
			{
				elog("Erreur insert tarif objet promotionnel ".var_export($param, true)." ".mysqli_error($this->_dbcon));
			}
		}
		else
		{
			// vérifier si un tarif est fixé pour l'objet et l'arrêté
			$idobject = $this->getIdObjectPricesForDecree($idobject_type, $iddecree);
			// si oui update
			if ($idobject != 0)
			{
				$sql = "UPDATE object SET old_tarif_public = ?, new_tarif_public = ?, old_tarif_etu_pers = ?, new_tarif_etu_pers = ? WHERE idobject = ?";
				$param = array($this->format_price($oldtarifpub), $this->format_price($newtarifpub), $this->format_price($oldtarifep), $this->format_price($newtarifep), $idobject);
				$result = prepared_query($this->_dbcon, $sql, $param);
				if ( !mysqli_error($this->_dbcon))
				{
					elog("Tarif objet promotionnel mis à jour ".var_export($param, true));
					return $idobject;
				}
				else
				{
					elog("Erreur insert tarif objet promotionnel ".var_export($param, true)." ".mysqli_error($this->_dbcon));
				}
			}
			// sinon insert
			else
			{
				$sql = "INSERT INTO object (idobject_type, old_tarif_public, new_tarif_public, old_tarif_etu_pers, new_tarif_etu_pers) VALUES (?, ?, ?, ?, ?)";
				$param = array($idobject_type, $this->format_price($oldtarifpub), $this->format_price($newtarifpub), $this->format_price($oldtarifep), $this->format_price($newtarifep));
				$result = prepared_query($this->_dbcon, $sql, $param);
				if ( !mysqli_error($this->_dbcon))
				{
					elog("Tarif objet promotionnel insere ".var_export($param, true));
					return mysqli_insert_id($this->_dbcon);
				}
				else
				{
					elog("Erreur insert tarif objet promotionnel ".var_export($param, true)." ".mysqli_error($this->_dbcon));
				}
			}
		}
		return 0;
	}

	function getIdObjectActiveForIdOjectInactive($idobject)
	{
		$sql = "SELECT act.idobject FROM object act INNER JOIN object inact ON inact.idobject_type = act.idobject_type
				WHERE inact.idobject = ? AND act.active = 'O'";
		$param = array($idobject);
		$result = prepared_select($this->_dbcon, $sql, $param);
		$idobject_active = '';
		if ( !mysqli_error($this->_dbcon))
		{
			if ($row = mysqli_fetch_assoc($result))
			{
				$idobject_active = $row['idobject'];
			}
		}
		return $idobject_active;
	}
	function activateObjectPrices($idobject, $active = 'O')
	{
		$sql = "UPDATE object SET active = ? WHERE idobject = ?";
		$param = array($active, $idobject);
		$result = prepared_query($this->_dbcon, $sql, $param);
		if ( !mysqli_error($this->_dbcon))
		{
			elog("Activation Tarif objet promotionnel ".var_export($param, true));
		}
		else
		{
			elog("Erreur activation tarif objet promotionnel ".var_export($param, true)." ".mysqli_error($this->_dbcon));
		}
	}

	function getIdObjectPricesForDecree($idobject_type, $iddecree)
	{
		$sql = "SELECT DISTINCT obj.idobject FROM object obj INNER JOIN decree_field df ON df.value = obj.idobject
				INNER JOIN model_field mf ON mf.idmodel_field = df.idmodel_field
				INNER JOIN field_type ft ON ft.idfield_type = mf.idfield_type AND ft.datatype = 'object'
				WHERE df.iddecree = ? AND obj.idobject_type = ?";
		$param = array($iddecree, $idobject_type);
		$result = prepared_select($this->_dbcon, $sql, $param);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($row = mysqli_fetch_assoc($result))
			{
				return $row['idobject'];
			}
		}
		else
		{
			elog("Erreur recherche id objet pour l'arrete. ".mysqli_error($this->_dbcon));
		}
		return 0;
	}

	function format_price($price)
	{
		return $price != '' ? number_format($price, 2, '.', ' ') : '';
	}

	function getRoomsList()
	{
		$sql = "SELECT idroom_type, name, centre, capacite FROM room_type WHERE active = 'O' ORDER BY centre, name";
		$result = mysqli_query($this->_dbcon, $sql);
		$listrooms = array();
		if ( !mysqli_error($this->_dbcon))
		{
			while ($row = mysqli_fetch_assoc($result))
			{
				$listrooms[$row['idroom_type']] = $row;
			}
		}
		return $listrooms;
	}

	function getRoomTypePrices($idroom_type)
	{
		$sql = "SELECT idroom, new_tarif_heure, new_tarif_demi, new_tarif_jour FROM room WHERE idroom_type = ? AND active = 'O'";
		$param = array($idroom_type);
		$result = prepared_select($this->_dbcon, $sql, $param);
		$price = array('idroom' =>'0', 'new_tarif_heure' => '', 'new_tarif_demi' => '', 'new_tarif_jour' => '');
		if ( !mysqli_error($this->_dbcon))
		{
			if ($row = mysqli_fetch_assoc($result))
			{
				$price = $row;
			}
		}
		return $price;
	}

	function getRoomPricesById($idroom)
	{
		$sql = "SELECT idroom_type, old_tarif_heure, new_tarif_heure, old_tarif_demi, new_tarif_demi, old_tarif_jour, new_tarif_jour FROM room WHERE idroom = ? ";
		$param = array($idroom);
		$result = prepared_select($this->_dbcon, $sql, $param);
		$price = array('idroom_type' =>'0', 'old_tarif_heure' => '', 'new_tarif_heure' => '', 'old_tarif_demi' => '', 'new_tarif_demi' => '', 'old_tarif_jour' => '', 'new_tarif_jour' => '');
		if ( !mysqli_error($this->_dbcon))
		{
			if ($row = mysqli_fetch_assoc($result))
			{
				$price = $row;
			}
		}
		return $price;
	}

	function setRoomPrices($idroom_type, $oldtarifheure, $newtarifheure, $oldtarifdemi, $newtarifdemi, $oldtarifjour, $newtarifjour, $iddecree='')
	{
		if ($iddecree == '')
		{
			$sql = "INSERT INTO room (idroom_type, old_tarif_heure, new_tarif_heure, old_tarif_demi, new_tarif_demi, old_tarif_jour, new_tarif_jour) VALUES (?, ?, ?, ?, ?, ? , ?)";
			$param = array($idroom_type, $this->format_price($oldtarifheure), $this->format_price($newtarifheure), $this->format_price($oldtarifdemi), $this->format_price($newtarifdemi), $this->format_price($oldtarifjour), $this->format_price($newtarifjour));
			$result = prepared_query($this->_dbcon, $sql, $param);
			if ( !mysqli_error($this->_dbcon))
			{
				elog("Tarif salle insere ".var_export($param, true));
				return mysqli_insert_id($this->_dbcon);
			}
			else
			{
				elog("Erreur insert tarif salle ".var_export($param, true)." ".mysqli_error($this->_dbcon));
			}
		}
		else
		{
			// vérifier si un tarif est fixé pour l'objet et l'arrêté
			$idroom = $this->getIdRoomPricesForDecree($idroom_type, $iddecree);
			// si oui update
			if ($idroom != 0)
			{
				$sql = "UPDATE room SET old_tarif_heure = ?, new_tarif_heure = ?, old_tarif_demi = ?, new_tarif_demi = ?, old_tarif_jour = ?, new_tarif_jour = ? WHERE idroom = ?";
				$param = array($this->format_price($oldtarifheure), $this->format_price($newtarifheure), $this->format_price($oldtarifdemi), $this->format_price($newtarifdemi), $this->format_price($oldtarifjour), $this->format_price($newtarifjour), $idroom);
				$result = prepared_query($this->_dbcon, $sql, $param);
				if ( !mysqli_error($this->_dbcon))
				{
					elog("Tarif salle mis à jour ".var_export($param, true));
					return $idroom;
				}
				else
				{
					elog("Erreur insert tarif salle ".var_export($param, true)." ".mysqli_error($this->_dbcon));
				}
			}
			// sinon insert
			else
			{
				$sql = "INSERT INTO room (idroom_type, old_tarif_heure, new_tarif_heure, old_tarif_demi, new_tarif_demi, old_tarif_jour, new_tarif_jour) VALUES (?, ?, ?, ?, ?, ?, ?)";
				$param = array($idroom_type, $this->format_price($oldtarifheure), $this->format_price($newtarifheure), $this->format_price($oldtarifdemi), $this->format_price($newtarifdemi), $this->format_price($oldtarifjour), $this->format_price($newtarifjour));
				$result = prepared_query($this->_dbcon, $sql, $param);
				if ( !mysqli_error($this->_dbcon))
				{
					elog("Tarif salle insere ".var_export($param, true));
					return mysqli_insert_id($this->_dbcon);
				}
				else
				{
					elog("Erreur insert tarif salle ".var_export($param, true)." ".mysqli_error($this->_dbcon));
				}
			}
		}
		return 0;
	}

	function getIdRoomActiveForIdRoomInactive($idroom)
	{
		$sql = "SELECT act.idroom FROM room act INNER JOIN room inact ON inact.idroom_type = act.idroom_type
				WHERE inact.idroom = ? AND act.active = 'O'";
		$param = array($idroom);
		$result = prepared_select($this->_dbcon, $sql, $param);
		$idroom_active = '';
		if ( !mysqli_error($this->_dbcon))
		{
			if ($row = mysqli_fetch_assoc($result))
			{
				$idroom_active = $row['idroom'];
			}
		}
		return $idroom_active;
	}
	function activateRoomPrices($idroom, $active = 'O')
	{
		$sql = "UPDATE room SET active = ? WHERE idroom = ?";
		$param = array($active, $idroom);
		$result = prepared_query($this->_dbcon, $sql, $param);
		if ( !mysqli_error($this->_dbcon))
		{
			elog("Activation Tarif salle ".var_export($param, true));
		}
		else
		{
			elog("Erreur activation tarif salle ".var_export($param, true)." ".mysqli_error($this->_dbcon));
		}
	}

	function getIdRoomPricesForDecree($idroom_type, $iddecree)
	{
		$sql = "SELECT DISTINCT roo.idroom FROM room roo INNER JOIN decree_field df ON df.value = roo.idroom
				INNER JOIN model_field mf ON mf.idmodel_field = df.idmodel_field
				INNER JOIN field_type ft ON ft.idfield_type = mf.idfield_type AND ft.datatype = 'room'
				WHERE df.iddecree = ? AND roo.idroom_type = ?";
		$param = array($iddecree, $idroom_type);
		$result = prepared_select($this->_dbcon, $sql, $param);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($row = mysqli_fetch_assoc($result))
			{
				return $row['idroom'];
			}
		}
		else
		{
			elog("Erreur recherche id salle pour l'arrete. ".mysqli_error($this->_dbcon));
		}
		return 0;
	}

	function sortModel($tab)
	{
		array_multisort(array_column($tab, 'iddecree_type'), SORT_ASC, array_column($tab, 'name'), SORT_ASC, $tab);
		return $tab;
	}

	function getListDecreeStatusForCompModel($user, $list_comp, $list_model, $year)
	{
		require_once 'ldap.php';
		require_once 'model.php';
		$ldap = new ldap();
		$resp = array();
		$stats = array();
		$recap = array();
		$tem_commission = false;
		$tem_jury = false;
		foreach($list_comp as $comp)
		{
			$supann = $ldap->getSupannCodeEntiteFromAPO($comp['code']);
			$resp[$comp['code']] = $ldap->getStructureResp($supann);
			echo "<br><b> Composante : ".$comp['value']."</b><br>";
			echo "Responsables : <br>";
			foreach($resp[$comp['code']] as $responsable)
			{
				echo $responsable['name']." ".$responsable['mail']." ".$responsable['role']."<br>";
			}
			$tem_relance = false;
			$recap[$comp['code']] = '';
			foreach($list_model as $idmodel => $model)
			{
				if ($idmodel != 8 && $idmodel != 38)
				{
					$obj_model = new model($this->_dbcon, $idmodel);
					$dt = $obj_model->getDecreeType()['iddecree_type'];
					if ($dt == 1 || $dt == 5)
					{
						$tem_jury = true;
					}
					elseif ($dt == 2 || $dt == 6)
					{
						$tem_commission = true;
					}
					$model_decrees = $user->getDecreesBy(array('idmodel' => $obj_model->getid(), 'createyear' => $year, 'composante' => 'structures-'.$supann), -1);
					//var_dump($model_decrees);
					$stats[$comp['code']][$idmodel] = $obj_model->getStats($model_decrees, $comp['code']);
					if (sizeof($stats[$comp['code']][$idmodel]['liste_to_do']) > 0)
					{
						$tem_relance = true;
						$params_url = "?new&idmodel=".$idmodel."&comp=".'structures-'.$supann;
						$recap[$comp['code']] .= "<b> Modèle : ".$obj_model->getDecreeType()['name']." ".$model['name'];
						$recap[$comp['code']] .= "<br> Nombre d'arrêtés à créer : ".sizeof($stats[$comp['code']][$idmodel]['liste_to_do'])."<br>";
						$recap[$comp['code']] .= "Nombre d'arrêtés créés : ".$stats[$comp['code']][$idmodel]['nb_decree_made']."</b><br>";
						$recap[$comp['code']] .= "Détail :  <br>";
						//var_dump($stats[$comp['code']][$idmodel]['liste_edit']);
						foreach($stats[$comp['code']][$idmodel]['liste_to_do'] as $todo)
						{
							$params_url = "?new&idmodel=".$idmodel."&comp=".'structures-'.$supann."&etp=".$todo['code'];
							$recap[$comp['code']] .= $todo['code']." : ".$todo['value']." --- ";
							if (key_exists($todo['value'], $stats[$comp['code']][$idmodel]['liste_edit']))
							{
								$liste_periode = array();
								$liste_periodes_edited = array_column($stats[$comp['code']][$idmodel]['liste_edit'][$todo['value']], 'periode');
								array_multisort($liste_periodes_edited, SORT_ASC, $stats[$comp['code']][$idmodel]['liste_edit'][$todo['value']]);
								if (!in_array("Annuel", $liste_periodes_edited))
								{
									if (!in_array("semestre 1", $liste_periodes_edited))
									{
										// TODO : Ajouter paramétrage : modèle, composante, diplôme, periode
										$params_url .= "&periode=S1";
										$recap[$comp['code']] .= " semestre 1 <a href=\"".URL_BASE_ZORRO."/create_decree.php".$params_url."\" target=\"_blank\">➕</a> - ";
									}
								}
								foreach($stats[$comp['code']][$idmodel]['liste_edit'][$todo['value']] as $elem)
								{
									$recap[$comp['code']] .= " ".$elem['periode']." ".$elem['statut']['img']." - ";
									$liste_periode[] = $elem['periode'];
								}
								if (!in_array("Annuel", $liste_periodes_edited))
								{
									if (!in_array("semestre 2", $liste_periodes_edited))
									{
										$params_url .= "&periode=S2";
										$recap[$comp['code']] .= " semestre 2 <a href=\"".URL_BASE_ZORRO."/create_decree.php".$params_url."\" target=\"_blank\">➕</a> - ";
									}
								}
							}
							else
							{
								$recap[$comp['code']] .= "<a href=\"".URL_BASE_ZORRO."/create_decree.php".$params_url."\" target=\"_blank\">➕</a>";
							}
							$recap[$comp['code']] .= "
							<br>";
						}
						$recap[$comp['code']] .= "<br>";
					}
				}
			}
			echo $recap[$comp['code']]."<br>";
			if ($tem_relance)
			{
				echo "<p style='color:red;'> RELANCE à envoyer </p>";
				$subject = "Des arrêtés attendent leur création.";
				$message = "<html>
				<head>

				<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">
				<title></title>
				</head>
				<body>
				<p>Bonjour,</p>";
				if ($tem_jury)
				{
					$message .= "<p>Veuillez trouver ci-après les arrêtés de jury de votre composante qui doivent être impérativement validés avant la tenue des délibérations.</p>";
				}
				if ($tem_commission)
				{
					$message .= "<p>Les commissions d'examen des voeux ne peuvent se réunir tant que les arrêtés correspondants n'ont pas été validés. Veuillez trouver ci-après l'état d'avancement des arrêtés de votre composante.</p>";
				}
				$message .= "<p>".$recap[$comp['code']]."</p>";
				$message .= "<p>Légende :  cliquez sur l'icone pour effectuer l'action correspondante.<br>";
				$message .= "✏️ :  accéder au brouillon sur Zorro.<br>";
				$message .= "🕓🕕🕖 : accéder au document en cours de signature sur eSignature.<br>";
				$message .= "✅ : accéder au document signé sur eSignature.<br>";
				$message .= "➕ : créer le document sur Zorro.";
				$message .= "</p>";
				$message .= "<p>Veuillez créer les arrêtés sur Zorro.</p>";
				$message .= "<p>Cordialement,</p>
				<p style='font-size:15px;'>Message automatique envoyé par l'application <a href=\"".URL_BASE_ZORRO."\">Zorro</a> de gestion des arrêtés<br>
				</p>
				</body>
				</html>";
				if (TEM_ENVOI_MAIL == 'O')
				{
					$this->sendEmail($subject, $message, array_column($resp[$comp['code']], 'mail'));
				}
				else
				{
					echo "L'envoi de mail est désactivé. Les destinataires prévus sont : ".implode(",", array_column($resp[$comp['code']], 'mail'));
				}
			}
			echo "<br>___________________________";
		}
		return $recap;
	}

	function sendEmail($subject, $message, $recipients)
	{
		if (MODE_TEST == 'O')
		{
			$message .= "Message de test - destinataires prévus : ";
			foreach($recipients as $recipient)
			{
				$message .= " ".$recipient;
			}
			$recipients = explode(';',SENDMAIL_TEST);
		}
		// Paramétrer sendmail_FROM
		ini_set('sendmail_from', SENDMAIL_FROM);
		ini_set('SMTP', SENDMAIL_SMTP);
		// Envoyer un email de relance
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "Content-type: text/html; charset=UTF-8";
		$headers[] = "Reply-To: ".SENDMAIL_REPLY_TO;
		$headers[] = "From: Zorro <".SENDMAIL_FROM.">";
		$preferences = array("input-charset" => "UTF-8", "output-charset" => "UTF-8");
		$encoded_subject = str_replace("Subject: ", "", iconv_mime_encode("Subject", $subject, $preferences));
		foreach ($recipients as $mail)
		{
			if (mail($mail, $encoded_subject, $message, implode("\r\n",$headers)))
			{
				echo "Email envoyé avec succès à ".$mail;
				return true;
			}
			else
			{
				echo "Echec de l'envoi de mail à ".$mail;
				return false;
			}
		}
		return false;
	}

	function getEtapeLibelle($cod_etp, $req)
	{
		return $this->executeQuery(array('schema' => 'APOGEE', 'query' => '','query_clause' => "SELECT col1,col2 FROM (".$req['query_clause'].") WHERE col1 = '".$cod_etp."'"));
	}

	function getDomaineEtape($cod_etp, $req, $liste_dom)
	{
		foreach ($liste_dom as $dom)
		{
			$domaine = $this->executeQuery(array('schema' => 'APOGEE', 'query' => '','query_clause' => "SELECT col1,col2 FROM (".$req['query_clause']." AND dfd.lib_dfd = '".str_replace("'", "''", $dom)."') WHERE col1 = '".$cod_etp."'"));
			if (sizeof($domaine) > 0)
			{
				return $dom;
			}
		}
		return array();
	}

}
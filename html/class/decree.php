<?php
require_once dirname(__FILE__,2).'/include/const.php';
require_once dirname(__FILE__,2).'/include/fonctions.php';
require_once dirname(__FILE__,2).'/class/model.php';
require_once dirname(__FILE__,2).'/class/reference.php';
require_once dirname(__FILE__,2).'/class/ldap.php';

class decree {
	
	private $_dbcon;
	
	private $_year;
	
	private $_number;
	
	private $_id;
	
	private $_idmodel;
	
	function __construct($dbcon, $year=null, $number=null, $id=null)
	{
		require_once (dirname(__FILE__,2)."/include/dbconnection.php");
		if ($year != null && $number != null)
		{
			$this->_year = intval($year);
			$this->_number = intval($number);
		}
		if ($id != null)
		{
			$this->_id = intval($id);
		}
		$this->_dbcon = $dbcon;
	}
	
	function getid()
	{
		if (isset($this->_id) && $this->_id != null)
		{
			return $this->_id;
		}
		$select = "SELECT iddecree FROM decree WHERE number = ? AND year = ?";
		$params = array($this->getNumber(), $this->getYear());
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				$this->_id = $res['iddecree'];
				return $res['iddecree'];
			}
			else 
			{
				elog("decree ".$this->getYear()." / ".$this->getNumber()." absent de la table.");
			}
		}
		else 
		{
			elog("erreur select iddecree from decree.".mysqli_error($this->_dbcon));
		}
		return 0;
	}
	
	
	function getNumber()
	{
		if (isset($this->_number) && $this->_number != null)
		{
			return $this->_number;
		}
		$select = "SELECT number FROM decree WHERE iddecree = ?";
		$params = array($this->getId());
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				$this->_number = $res['number'];
				return $res['number'];
			}
			else
			{
				elog("decree $this->_id absent de la table.");
			}
		}
		else
		{
			elog("erreur select number from decree.".mysqli_error($this->_dbcon));
		}
		return 0;
	}
	
	
	function getYear()
	{
		if (isset($this->_year) && $this->_year != null)
		{
			return $this->_year;
		}
		$select = "SELECT year FROM decree WHERE iddecree = ?";
		$params = array($this->getId());
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				$this->_year = $res['year'];
				return $res['year'];
			}
			else
			{
				elog("decree $this->_id absent de la table.");
			}
		}
		else
		{
			elog("erreur select year from decree.".mysqli_error($this->_dbcon));
		}
		return 0;
	}
	
	function getMajDate()
	{
		$select = "SELECT majdate FROM decree WHERE iddecree = ?";
		$params = array($this->getId());
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				return $res['majdate'];
			}
			else
			{
				elog("decree $this->_id absent de la table.");
			}
		}
		else
		{
			elog("erreur select majdate from decree.".mysqli_error($this->_dbcon));
		}
		return 0;
	}

	function getModelId()
	{
		//print_r2("getModelId");
		if (isset($this->_idmodel) && $this->_idmodel != null)
		{
			return $this->_idmodel;
		}
		$select = "SELECT idmodel FROM decree WHERE iddecree = ?";
		$params = array($this->getId());
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				$this->_idmodel = $res['idmodel'];
				return $res['idmodel'];
			}
			else
			{
				elog("decree $this->_id absent de la table.");
			}
		}
		else
		{
			elog("erreur select idmodel from decree.".mysqli_error($this->_dbcon));
		}
		return 0;
	}
	
	function getDecree()
	{
		$select = "SELECT * FROM decree WHERE iddecree = ?";
		$params = array($this->getId());
		$result = prepared_select($this->_dbcon, $select, $params);
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
			elog("erreur SELECT * FROM decree WHERE id =". $this->getid()." ".mysqli_error($this->_dbcon));
		}
		return $decree;
	}
	
	function setIdEsignature($id)
	{
		$update = "UPDATE decree SET idesignature = ?, status = ? WHERE iddecree = ?";
		$params = array($id, STATUT_EN_COURS, $this->getId());
		$result = prepared_query($this->_dbcon, $update, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			elog("identifiant esignature : ".$id." pour le decree id : ".$this->getId());
		}
		else
		{
			elog("Erreur update idesignature : ".$id." pour le decree id : ".$this->getId()." ".mysqli_error($this->_dbcon));
		}
	}
	
	function unsetIdEsignature($userid)
	{
		$update = "UPDATE decree SET idesignature = NULL, status = ?, idmajuser = ?, majdate = NOW() WHERE iddecree = ?";
		$params = array(STATUT_ANNULE, $userid, $this->getId());
		$result = prepared_query($this->_dbcon, $update, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			elog("identifiant esignature supprime pour le decree id : ".$this->getId());
		}
		else
		{
			elog("Erreur suppression idesignature pour le decree id : ".$this->getId()." ".mysqli_error($this->_dbcon));
		}
	}
	
	
	function getIdEsignature()
	{
		$select = "SELECT idesignature FROM decree WHERE iddecree = ?";
		$params = array($this->getId());
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				return $res['idesignature'];
			}
		}
		else
		{
			elog("Erreur select idesignature pour le decree id : ".$this->getId()." ".mysqli_error($this->_dbcon));
		}
		return 0;
	}
	
	function setStatus($status, $date, $iduser)
	{
		$update = "UPDATE decree SET status = ?, majdate = ?, idmajuser = ? WHERE iddecree = ?";
		$params = array($status, $date, $iduser, $this->getId());
		$result = prepared_query($this->_dbcon, $update, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			elog("status esignature : ".$status." pour le decree id : ".$this->getId());
		}
		else
		{
			elog("Erreur update status esignature : ".$status." pour le decree id : ".$this->getId()." ".mysqli_error($this->_dbcon));
		}
	}
	
	function setFilename($filename)
	{
		$update = "UPDATE decree SET filename = ? WHERE iddecree = ?";
		$params = array($filename, $this->getId());
		$result = prepared_query($this->_dbcon, $update, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			elog("filename : ".$filename." pour le decree id : ".$this->getId());
		}
		else
		{
			elog("Erreur update filename : ".$filename." pour le decree id : ".$this->getId()." ".mysqli_error($this->_dbcon));
		}
	}

	function getStatus($synchro = true)
	{
		$select = "SELECT status FROM decree WHERE iddecree = ?";
		$params = array($this->getId());
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				$status = $res['status'];
				if ($synchro && $status == STATUT_EN_COURS || $status == STATUT_CORBEILLE)
				{
					$new_status = $this->synchroEsignatureStatus($status);
					return ($new_status == NULL) ? ($status == STATUT_ANNULE ? STATUT_ANNULE : STATUT_ERREUR)  : $new_status;
				}
				return $status;
			}
		}
		else
		{
			elog("Erreur select status pour le decree id : ".$this->getId()." ".mysqli_error($this->_dbcon));
		}
		return 0;
	}
	
	function save($iduser, $idmodel, $structure, $update=false)
	{
		$status = STATUT_BROUILLON;
		if ($idmodel == 27)
		{
			$status = STATUT_HORS_ZORRO;
		}
		if ($update)
		{
			$insert = "UPDATE decree SET year = ?, number = ?, majdate = NOW(), idmajuser = ?, idmodel = ?, status = ?, structure = ? WHERE iddecree = ?";
			$params = array($this->getYear(), $this->getNumber(), $iduser, $idmodel, $status, $structure, $this->getid());
		}
		else 
		{
			$insert = "INSERT INTO decree (`year`, `number`, `createdate`, `iduser`, `idmodel`, `status`, `structure`) VALUES (?, ?, NOW(), ?, ?, ?, ?)";
			$params = array($this->_year, $this->_number, $iduser, $idmodel, $status, $structure);
		}
		$result = prepared_query($this->_dbcon, $insert, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			elog("Decree cree : ".$this->_number." / ".$this->_year);
		}
		else
		{
			elog("Erreur insert Decree : ".$this->_number." / ".$this->_year." ".mysqli_error($this->_dbcon));
		}
		$this->getFileName();
	}

	function setFields($fields, $update=false)
	{
		if ($update)
		{
			$delete = "DELETE FROM decree_field WHERE iddecree = ?";
			$param = array($this->getid());
			$result = prepared_query($this->_dbcon, $delete, $param);
			if ( !mysqli_error($this->_dbcon))
			{
				elog("Fields supprimés pour le decree ".$this->getNumber());
			}
			else
			{
				elog("Erreur delete Fields : ".$this->getNumber()." ".mysqli_error($this->_dbcon));
			}
		}
		foreach ($fields as $field)
		{
			$insert = "INSERT INTO decree_field (`iddecree`, `idmodel_field`, `value`) VALUES (?, ?, ?)";
			$params = array($this->getid(), $field['idmodel_field'], $field['value']);
			$result = prepared_query($this->_dbcon, $insert, $params);
			if ( !mysqli_error($this->_dbcon))
			{
				elog("Field cree pour le decree ".$this->getNumber()." : ".var_export($field, true));
			}
			else
			{
				elog("Erreur insert Field : ".$this->getNumber()." ".mysqli_error($this->_dbcon));
			}
		}
	}
	function getFields()
	{
		$select = "SELECT * FROM decree_field WHERE iddecree = ?";
		$params = array($this->getId());
		$result = prepared_select($this->_dbcon, $select, $params);
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
		$update = "UPDATE decree SET number = NULL, idmajuser = ?, majdate = NOW() WHERE iddecree = ?";
		$params = array($iduser, $this->getid());
		$result = prepared_query($this->_dbcon, $update, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			$this->_number = null;
			$this->_year = null;
			elog("Numéro ".$this->_year.'/'.$this->_number." supprimé pour le decree id : ".$this->getid()." par iduser ".intval($iduser));
		}
		else
		{
			elog("Erreur à la suppression du numéro ".$this->_year.'/'.$this->_number." du decree : ".$this->getId()." ".mysqli_error($this->_dbcon));
		}
	}
	
	function getModel()
	{
		//print_r2("getModel");
		$idmodel = $this->getModelId();
		$model = new model($this->_dbcon, $idmodel);
		return $model;
	}
	
	function getFileName($extension='pdf', $new=false)
	{
		//print_r2("getFileName");
		// Si $new = true on écrase le nom de fichier actuel sinon on garde l'ancien ou on créé un nouveau s'il n'est pas renseigné
		if (!$new)
		{
			$select = "SELECT filename FROM decree WHERE iddecree = ? AND filename IS NOT NULL";
			$param = array($this->getid());
			$result = prepared_select($this->_dbcon, $select, $param);
			if ( !mysqli_error($this->_dbcon))
			{
				if (mysqli_num_rows($result) > 0)
				{
					if ($row = mysqli_fetch_assoc($result))
					{
						return $row['filename'].'.'.$extension;
					}
				}
			}
			else
			{
				elog("Erreur select filename : ".$this->getid()." ".mysqli_error($this->_dbcon));
			}
		}
		$model = $this->getModel();
		$filename = "Arrêté n°".$this->getYear().'-'.$this->getNumber()." ";
		$infosModel = $model->getModelInfo();
		if ($infosModel['iddecree_type'] == 1)
		{
			$filename .= 'Jury';
		}
		else
		{
			$filename .= str_replace(array(" "), "_",$infosModel['name']);
		}
		$modelfields = $model->getFieldsForFileName();
		$fields = $this->getFields();
		foreach($modelfields as $modelfield)
		{
			if (array_key_exists($modelfield['idmodel_field'],$fields) && $fields[$modelfield['idmodel_field']][0]['value'] != '')
			{
				if ($modelfield['datatype'] == 'group')
				{
					$ldap = new ldap();
					$nomstruct = $ldap->getStructureName($fields[$modelfield['idmodel_field']][0]['value']);
					$filename .= "_".str_replace(array( "(", ")", ",", ":", "?", "<", ">", "|", "*", "&quot;", "&amp;"), "", str_replace(array("'", ".", " ", "/", "\\"), "_", $nomstruct));
				}
				else
				{
					$filename .= "_".str_replace(array( "(", ")", ",", ":", "?", "<", ">", "|", "*", "&quot;", "&amp;"), "", str_replace(array("'", ".", " ", "/", "\\"), "_", $fields[$modelfield['idmodel_field']][0]['value']));
				}
			}
		}
		$ref = new reference('', '');
		if ($infosModel['iddecree_type'] == 2) // Commissions pour l'année suivante
		{
			$year = $ref->getAnneeUni(1);
		}
		else
		{
			$year = $ref->getAnneeUni();
		}
		$filename .= '_'.$year;
		$filename .= '_'.$this->getid();
		$this->setFilename($filename);
		$filename .= ".".$extension;
		return $filename;
	}
	
	function getFileNameAff()
	{
		$filename = '';
		$select = "SELECT filename FROM decree WHERE iddecree = ? AND filename IS NOT NULL";
		$param = array($this->getid());
		$result = prepared_select($this->_dbcon, $select, $param);
		if ( !mysqli_error($this->_dbcon))
		{
			if (mysqli_num_rows($result) > 0)
			{
				if ($row = mysqli_fetch_assoc($result))
				{
					$filename = $row['filename'];
					$nom_sans_numero = substr($filename, 0, strrpos($filename, '_'));
					return $nom_sans_numero;
				}
			}
		}
		else
		{
			elog("Erreur select filename : ".$this->getid()." ".mysqli_error($this->_dbcon));
		}
		return $filename;
	}

	function synchroEsignatureStatus($status)
	{
		$idesignature = $this->getIdEsignature();
		if ($idesignature == 0) 
		{
			elog("Pas de synchronisation du document ".$this->getId()." pas d'identifiant eSignature.");
		}
		else 
		{
			elog("Synchronisation... ".ESIGNATURE_BASE_URL.ESIGNATURE_CURLOPT_URL_GET_SIGNREQ . $idesignature);
			$curl = curl_init();
			$opts = array(
					CURLOPT_URL => ESIGNATURE_BASE_URL.ESIGNATURE_CURLOPT_URL_GET_SIGNREQ . $idesignature,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_PROXY => ''
			);
			curl_setopt_array($curl, $opts);
			curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			$json = curl_exec($curl);
			
			$error = curl_error ($curl);
			curl_close($curl);
			if ($error != "")
			{
				elog(" Erreur Curl =>  " . $error);
			}
			//echo "<br>" . print_r($json,true) . "<br>";
			$response = json_decode($json, true);
			//print_r2($response);
			
			elog("Le json est =>  " . var_export($json,true));
			
			//elog("La réponse est =>  " . var_export($response,true));
			if ($json == '' || $json == 'null')
			{
				// on vérifie si le document a été supprimé d'esignature
				elog("Le json est vide ou null pour l'id ".$idesignature);
				$curl = curl_init();
				$opts = array(
						CURLOPT_URL => ESIGNATURE_BASE_URL.ESIGNATURE_CURLOPT_URL_STATUS . $idesignature,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_SSL_VERIFYPEER => false,
						CURLOPT_PROXY => ''
				);
				curl_setopt_array($curl, $opts);
				elog(var_export($opts, true));
				curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
				$json = curl_exec($curl);
				$error = curl_error ($curl);
				curl_close($curl);
				if ($error != "")
				{
					elog(" Erreur Curl =>  " . $error);
				}
				elog("Le json est =>  " . var_export($json,true));
				if ($json == 'fully-deleted')
				{
					if ($status != STATUT_VALIDE)
					{
						// On conserve le statut si la demande est signée
						$this->setStatus(STATUT_SUPPR_ESIGN, date("Y-m-d H:i:s"), 0);
						$this->unsetIdEsignature(0);
						$this->unsetNumber(0);
						return STATUT_SUPPR_ESIGN;
					}
					else
					{
						return STATUT_VALIDE;
					}
				}
				return null;
			}
			else
			{
				if (stristr(substr($json,0,20),'HTML') === false)
				{
					if (! isset($response['error']))
					{
						elog ("Success. setStatus...");
						if (isset($response['parentSignBook']['status']))
						{
							elog("Statut de la demande sur eSignature : ".$response['parentSignBook']['status']);
							$current_status = $response['parentSignBook']['status'];
						}
						else
						{
							$current_status = '';
						}
						elog("current status ".$current_status);
						$new_status = $current_status;
						switch (strtolower($current_status))
						{
							//draft, pending, canceled, checked, signed, refused, deleted, completed, exported, archived, cleaned
							case 'draft' :
							case 'pending' :
							case 'signed' :
							case 'checked' :
								$new_status = STATUT_EN_COURS; // pending
								$date = date("Y-m-d H:i:s");
								break;
							case 'completed' :
							case 'exported' :
							case 'archived' :
							case 'cleaned' :
								$new_status = STATUT_VALIDE; // validated
								$date = date("Y-m-d H:i:s");
								if (isset($response['parentSignBook']['endDate']))
								{
									if (!is_int($response['parentSignBook']['endDate']))
									{
										$date = new DateTime($response['parentSignBook']['endDate']);
										$date = $date->format("Y-m-d H:i:s");
									}
									else
									{
										// FORMAT /1000 pour ôter les nanosecondes
										$date = date("Y-m-d H:i:s", intdiv($response['parentSignBook']['endDate'], 1000));
									}
								}
								break;
							case 'refused':
								$new_status = STATUT_REFUSE; // refused
								$date = date("Y-m-d H:i:s");
								if (isset($response['parentSignBook']['endDate']))
								{
									if (!is_int($response['parentSignBook']['endDate']))
									{
										$date = new DateTime($response['parentSignBook']['endDate']);
										$date = $date->format("Y-m-d H:i:s");
									}
									else
									{
										// FORMAT /1000 pour ôter les nanosecondes
										$date = date("Y-m-d H:i:s", intdiv($response['parentSignBook']['endDate'], 1000));
									}
								}
								$this->unsetNumber(0);
								break;
							case 'deleted' : // TODO : Attention le document est dans la corbeille
							case 'canceled' :
								$new_status = STATUT_CORBEILLE; // trash
								$date = date("Y-m-d H:i:s");
								if (!is_int($response['parentSignBook']['endDate']))
								{
									$date = new DateTime($response['parentSignBook']['endDate']);
									$date = $date->format("Y-m-d H:i:s");
								}
								else
								{
									// FORMAT /1000 pour ôter les millisecondes
									$date = date("Y-m-d H:i:s", intdiv($response['parentSignBook']['endDate'], 1000));
								}
								break;
							case '' : elog('Erreur Statut vide esignature... Ne rien faire');
								break;
							default :
								$new_status = STATUT_ERREUR; // error
								$date = date("Y-m-d H:i:s");
						}
						elog ("Nouveau statut le $date de la demande : ".$new_status);
						if ($status != $new_status)
						{
							$this->setStatus($new_status, $date, 0);
						}
						return $new_status;
					}
					else
					{
						elog("La réponse est en erreur.");
					}
				}
				else
				{
					elog("Le json est du HTML.");
				}
			}
		}
		return null;
	}

	function getEsignInfo()
	{
		$select = "SELECT * FROM esignature_info WHERE idesignature = ?";
		$param = array($this->getIdEsignature());
		$result = prepared_select($this->_dbcon, $select, $param);
		if ( !mysqli_error($this->_dbcon))
		{
			if (mysqli_num_rows($result) > 0)
			{
				if ($row = mysqli_fetch_assoc($result))
				{
					return $row;
				}
			}
		}
		return NULL;
	}
	
	function getRefuseComment($synchro=false)
	{
		$idesignature = $this->getIdEsignature();
		if ($idesignature == 0)
		{
			elog("Le document ".$this->getId()." n'a pas d'identifiant eSignature.");
		}
		elseif($synchro)
		{
			elog("Récupération du commentaire de refus... ".ESIGNATURE_BASE_URL.ESIGNATURE_CURLOPT_URL_GET_SIGNREQ . $idesignature);
			$curl = curl_init();
			$opts = array(
					CURLOPT_URL => ESIGNATURE_BASE_URL.ESIGNATURE_CURLOPT_URL_GET_SIGNREQ . $idesignature,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_PROXY => ''
			);
			curl_setopt_array($curl, $opts);
			curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			$json = curl_exec($curl);
			$error = curl_error ($curl);
			curl_close($curl);
			if ($error != "")
			{
				elog(" Erreur Curl =>  " . $error);
			}
			$response = json_decode($json, true);
			if (stristr(substr($json,0,20),'HTML') === false)
			{
				if (! isset($response['error']))
				{
					elog ("Succès. getRefuseComment...");
					if (isset($response['comments'][0]['text']))
					{
						elog ("Commentaire : ".$response['comments'][0]['text']);
						$esign_info = $this->getEsignInfo();
						if ($esign_info != null)
						{
							$update = "UPDATE esignature_info SET refuse_comment = ? WHERE idesignature = ?";
							$params = array($response['comments'][0]['text'], $idesignature);
							$result = prepared_select($this->_dbcon, $update, $params);
							if (mysqli_error($this->_dbcon))
							{
								elog("Erreur lors de l'update du commentaire de refus. idesignature : ".$idesignature.", commentaire : ".htmlspecialchars($response['comments'][0]['text']));
							}
						}
						else
						{
							$insert = "INSERT INTO esignature_info (idesignature, refuse_comment, sign_step) VALUES (?,?,NULL)";
							$params = array($idesignature, $response['comments'][0]['text']);
							$result = prepared_select($this->_dbcon, $insert, $params);
							if (mysqli_error($this->_dbcon))
							{
								elog("Erreur lors de l'insert du commentaire de refus. idesignature : ".$idesignature.", commentaire : ".htmlspecialchars($response['comments'][0]['text']));
							}
						}
						return htmlspecialchars($response['comments'][0]['text']);
					}
					else
					{
						elog ("Erreur pas de commentaire...");
					}
				}
			}
		}
		else
		{
			$esign_info = $this->getEsignInfo();
			if ($esign_info != null)
			{
				if (array_key_exists("refuse_comment", $esign_info))
				{
					return htmlspecialchars($esign_info["refuse_comment"]);
				}
			}
		}
		return 'erreur eSignature';
	}

	function getSignStep($synchro=false)
	{
		$idesignature = $this->getIdEsignature();
		if ($idesignature == 0)
		{
			elog("Le document ".$this->getId()." n'a pas d'identifiant eSignature.");
		}
		elseif($synchro)
		{
			elog("Récupération de l'etape de signature... ".ESIGNATURE_BASE_URL.ESIGNATURE_CURLOPT_URL_GET_SIGNREQ . $idesignature);
			$curl = curl_init();
			$opts = array(
					CURLOPT_URL => ESIGNATURE_BASE_URL.ESIGNATURE_CURLOPT_URL_GET_SIGNREQ . $idesignature,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_PROXY => ''
			);
			curl_setopt_array($curl, $opts);
			curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			$json = curl_exec($curl);
			$error = curl_error ($curl);
			curl_close($curl);
			if ($error != "")
			{
				elog(" Erreur Curl =>  " . $error);
			}
			$response = json_decode($json, true);
			if (stristr(substr($json,0,20),'HTML') === false)
			{
				if (! isset($response['error']))
				{
					elog ("Succès. getSignStep...");
					if (isset($response['parentSignBook']['liveWorkflow']['currentStep']['workflowStep']['description']))
					{
						elog ("Signataire en attente : ".$response['parentSignBook']['liveWorkflow']['currentStep']['workflowStep']['description']);
						$esign_info = $this->getEsignInfo();
						$nb_sign = 0;
						if (isset($response['parentSignBook']['liveWorkflow']['currentStep']['recipients']))
						{
							foreach ($response['parentSignBook']['liveWorkflow']['currentStep']['recipients'] as $recipient)
							{
								if (isset($recipient['signed']) && $recipient['signed'])
								{
									$nb_sign ++;
								}
							}
						}
						if ($esign_info != null)
						{
							$update = "UPDATE esignature_info SET sign_step = ?, nb_sign = ? WHERE idesignature = ?";
							$params = array($response['parentSignBook']['liveWorkflow']['currentStep']['workflowStep']['description'], $nb_sign, $idesignature);
							$result = prepared_select($this->_dbcon, $update, $params);
							if (mysqli_error($this->_dbcon))
							{
								elog("Erreur lors de l'update de l'étape de signature. idesignature : ".$idesignature.", étape : ".htmlspecialchars($response['parentSignBook']['liveWorkflow']['currentStep']['workflowStep']['description']));
							}
						}
						else
						{
							$insert = "INSERT INTO esignature_info (idesignature, refuse_comment, sign_step, nb_sign) VALUES (?,NULL,?,?)";
							$params = array($idesignature, $response['parentSignBook']['liveWorkflow']['currentStep']['workflowStep']['description'], $nb_sign);
							$result = prepared_select($this->_dbcon, $insert, $params);
							if (mysqli_error($this->_dbcon))
							{
								elog("Erreur lors de l'insert de l'étape de signature. idesignature : ".$idesignature.", commentaire : ".htmlspecialchars($response['parentSignBook']['liveWorkflow']['currentStep']['workflowStep']['description']));
							}
						}
						return $response['parentSignBook']['liveWorkflow']['currentStep']['workflowStep']['description'];
					}
					else
					{
						elog ("Erreur pas de signature en attente...");
					}
				}
			}
		}
		else
		{
			$esign_info = $this->getEsignInfo();
			if ($esign_info != null)
			{
				if (array_key_exists("sign_step", $esign_info))
				{
					return $esign_info["sign_step"];
				}
			}
		}
		return 'erreur eSignature';
	}

	function getNbSignForStep()
	{
		$select = "SELECT nb_sign FROM esignature_info WHERE idesignature = ?";
		$param = array($this->getIdEsignature());
		$result = prepared_select($this->_dbcon, $select, $param);
		$nb_sign = 0;
		if ( !mysqli_error($this->_dbcon))
		{
			if ($row = mysqli_fetch_assoc($result))
			{
				$nb_sign = $row['nb_sign'];
			}
		}
		return $nb_sign;
	}

	function deleteSignRequest($userid)
	{
		$ch = curl_init();
		$idesignature = $this->getIdEsignature();
		curl_setopt($ch, CURLOPT_URL, ESIGNATURE_BASE_URL.ESIGNATURE_CURLOPT_URL_GET_SIGNREQ . $idesignature);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$json = curl_exec($ch);
		$result = json_decode($json);
		error_log(basename(__FILE__) . " -- RETOUR ESIGNATURE SUPPRESSION DECREE ".$this->getId()." - id esignature : ".$idesignature."-- " . var_export($result, true));
		$error = curl_error ($ch);
		curl_close($ch);
		$errlog = '';
		if ($error != "")
		{
			elog("Erreur Curl = " . $error);
		}
		if (!is_null($result))
		{
			elog(" Erreur lors de la suppression de l arrete dans Esignature : ".var_export($result, true));
			$message = "<p class='alerte alerte-danger'>Erreur lors de la suppression de l'arrêté dans eSignature.</p>";
		}
		else
		{
			$this->unsetIdEsignature($userid);
			elog("Demande de signature supprimée pour le decree  ".$this->getId()." par l'utilisateur ".$userid);
			$message = "<p class='alerte alerte-success'>Demande de signature supprimée.</p>";
		}
		return $message;
	}

	function getExportPath()
	{
		$model = $this->getModel();
		$model_export_path = $model->getExportPath();
		$date = date('m');
		$year = ($date < '9') ? date('Y')-1 : date('Y');
		$decree_type = $model->getDecreeType();
		$ref = new reference($this->_dbcon, '');
		// Les modèles de la DEVE
		if ($decree_type['iddecree_type'] == 3)
		{
			// TARGET_ULR.export_path_decree_type.composante_etu_export_path.export_path_model.year
			$infoetu = explode(' - ', $this->getFieldForFieldType(23)); // champ formation composante
			if (sizeof($infoetu) > 0)
			{
				$supannCodeEntite = end($infoetu);
				$struct_export_path = $ref->getStructureExportPath($supannCodeEntite);
				if ($struct_export_path == NULL)
				{
					$ldap = new ldap();
					$nom_court = $ldap->getNomCourtStruct($supannCodeEntite);
					if ($nom_court == NULL)
					{
						$struct_export_path = '';
					}
					else
					{
						$ref->setStructureExportPath('structures-'.$supannCodeEntite, $nom_court);
						$struct_export_path = $nom_court.'/';
					}
				}
				else
				{
					$struct_export_path .= '/';
				}
			}
			return TARGET_URL.$model_export_path['decree_type_export_path'].'/'.$struct_export_path.$model_export_path['model_export_path'].'/'.$year;
		}
		else
		{
			// TARGET_ULR.export_path_decree_type.structure_export_path.year.export_path_model
			$structure = $this->getStructure();
			$struct_export_path = $ref->getStructureExportPath($structure);
			if ($struct_export_path == NULL)
			{
				$ldap = new ldap();
				$nom_court = $ldap->getNomCourtStruct($structure);
				if ($nom_court == NULL)
				{
					$struct_export_path = '';
				}
				else
				{
					$ref->setStructureExportPath($structure, $nom_court);
					$struct_export_path = $nom_court.'/';
				}
			}
			else
			{
				$struct_export_path .= '/';
			}
			return TARGET_URL.$model_export_path['decree_type_export_path'].'/'.$struct_export_path.$year; //.'/'.$model_export_path['model_export_path'];
		}
	}

	function getStructure()
	{
		$select = "SELECT structure FROM decree WHERE iddecree = ?";
		$params = array($this->getId());
		$result = prepared_select($this->_dbcon, $select, $params);
		$structure = NULL;
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				$structure = $res['structure'];
			}
		}
		return $structure;
	}

	function getWorkflow()
	{
		$model = $this->getModel();
		return $model->getWorkflow();
	}

	function modelActive()
	{
		$model = $this->getModel();
		return $model->isActive();
	}

	function getFieldForFieldType($idfield_type)
	{
		$select = 'SELECT dfi.value FROM decree_field dfi INNER JOIN model_field mfi ON dfi.idmodel_field = mfi.idmodel_field WHERE dfi.iddecree = ? AND mfi.idfield_type = ?';
		$params = array($this->getId(), $idfield_type);
		$result = prepared_select($this->_dbcon, $select, $params);
		$values = '';
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				$values = $res['value'];
			}
		}
		return $values;
	}

	function getRecipientMail()
	{
		$select = "SELECT mw.idetape, mw.recipient_type, mw.recipient_default_value FROM model_workflow mw INNER JOIN decree d ON d.idmodel = mw.idmodel WHERE d.iddecree = ? ORDER BY mw.idetape";
		$params = array($this->getId());
		$result = prepared_select($this->_dbcon, $select, $params);
		$values = '';
		if ( !mysqli_error($this->_dbcon))
		{
			$ref = new reference('','');
			$ldap = new ldap();
			while ($res = mysqli_fetch_assoc($result))
			{
				switch ($res['recipient_type']) {
					case 'email':
						$values .= $res['idetape'].'*'.$res['recipient_default_value'].',';
						break;
					case 'role':
						// récupérer les personnes associées au role $res['recipient_default_value'] et pour chacun ajouter son email
						$roles = $ldap->getStructureResp($this->getStructure());
						switch ($res['recipient_default_value']) {
							case 'RA':
								foreach ($roles as $role)
								{
									if ($role['role'] == 'Responsable administratif')
									{
										$values .= $res['idetape'].'*'.$role['mail'].',';
									}
								}
								break;
							case 'RESP':
								foreach ($roles as $role)
								{
									if ($role['role'] == 'Responsable')
									{
										$values .= $res['idetape'].'*'.$role['mail'].',';
									}
								}
								break;
							case 'DIR':
								foreach ($roles as $role)
								{
									if ($role['role'] == 'Directeur' || $role['role'] == 'Directrice')
									{
										$values .= $res['idetape'].'*'.$role['mail'].',';
									}
								}
								break;
							case 'ALL':
								foreach ($roles as $role)
								{
									$values .= $res['idetape'].'*'.$role['mail'].',';
								}
								break;
							default:
								break;
						}
						break;
					case 'group':
						// récupérer les membres de la structure $res['recipient_default_value'] et pour chacun ajouter son email
						$emails = $ldap->getEmailsForGroupUsers($res['recipient_default_value']);
						foreach ($emails as $email)
						{
							$values .= $res['idetape'].'*'.$email.',';
						}
						break;
					case 'creator':
						$values .= $res['idetape'].'*'.$ref->getUserMail().',';
						break;
					default:
						break;
				}
			}
			$values = substr($values, 0, -1);
		}
		return $values;
	}

	function setRefuseHisto($newid, $motif, $date)
	{
		$insert = "INSERT INTO decree_histo_refus (iddecree_old, iddecree_new, refus_comment, refus_date) VALUES (?,?,?,?)";
		$oldid = $this->getid();
		$values = array($oldid, $newid, $motif, $date);
		$result = prepared_query($this->_dbcon, $insert, $values);
		if ( !mysqli_error($this->_dbcon))
		{
			elog("Refus historise : (oldid, newid, motif, date) (".$oldid.",".$newid.",".htmlspecialchars($motif).",".$date.")");
		}
		else
		{
			elog("Erreur d'historisation du refus : (oldid, newid, motif, date) (".$oldid.",".$newid.",".htmlspecialchars($motif).",".$date.") ".mysqli_error($this->_dbcon));
		}
	}

	// Récupérer le commentaire depuis la demande refusée puis dupliquée
	function getRefuseHisto()
	{
		$retour = array();
		$select = "SELECT refus_comment, refus_date FROM decree_histo_refus WHERE iddecree_new = ?";
		$params = array($this->getId());
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				$retour = $res;
			}
		}
		return $retour;
	}

	// Récupérer le commentaire du document refusé puis supprimé de Zorro
	function getRefusedCommentOnDelete()
	{
		$retour = array();
		$select = "SELECT refus_comment, refus_date FROM decree_histo_refus WHERE iddecree_old = ?";
		$params = array($this->getId());
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				$retour = $res;
			}
		}
		return $retour;
	}

	function getStatusAff($width = 20)
	{
		switch ($this->getStatus(false)) {
            case STATUT_ANNULE :
                $contenu = "<a href='create_decree.php?id=".$this->getid()."'><img src='img/supprimer.svg' alt='annulé' width='".$width."px'></a>";
                $title = 'Annulé';
                $class = "img";
                break;
            case STATUT_REFUSE :
                $comment = $this->getRefuseComment();
                $contenu = "<a href='".ESIGNATURE_BASE_URL.ESIGNATURE_URL_DOC.$this->getIdEsignature()."' target='_blank'>".date('d/m/Y', strtotime($this->getMajDate()))."</a>";
                $title = 'Refusé : '.$comment;
                $class = "red";
                break;
            case STATUT_BROUILLON :
                $contenu = "<a href='create_decree.php?id=".$this->getid()."'><img src='img/brouillon.svg' alt='brouillon' width='".$width."px'></a>";
                $title = 'Brouillon';
                $class = "img";
                break;
            case STATUT_HORS_ZORRO :
                $contenu = "<a href='create_decree.php?id=".$this->getid()."'><img src='img/valide_OK.svg' alt='hors_zorro' width='".$width."px'></a>";
                $title = 'Hors Zorro';
                $class = "img";
                break;
            case STATUT_VALIDE :
                $contenu = "<a href='".ESIGNATURE_BASE_URL.ESIGNATURE_URL_DOC.$this->getIdEsignature()."' target='_blank'>".date('d/m/Y', strtotime($this->getMajDate()))."</a>";
                $title = 'Validé';
                $class = "green";
                break;
            case STATUT_EN_COURS :
                $step = $this->getSignStep();
				if ($step == 'Visa de la composante')
				{
					$img = "<img src='img/enattenteA.svg' alt='visa composante en attente' width='".$width."px'>";
					$title = 'En cours de signature : '.$step;
					if ($this->getNbSignForStep() == 1)
					{
						$img = "<img src='img/enattenteB.svg' alt='2e visa composante en attente' width='".$width."px'>";
						$title = 'En cours de signature : 2e '.$step;
					}
				}
				elseif ($step == 'Validation de la présidence')
				{
					$img = "<img src='img/enattenteC.svg' alt='validation présidence en attente' width='".$width."px'>";
					$title = 'En cours de signature : '.$step;
				}
                $contenu = "<a href='".ESIGNATURE_BASE_URL.ESIGNATURE_URL_DOC.$this->getIdEsignature()."' target='_blank'>$img</a>";
                $class = "img";
                break;
            case STATUT_ERREUR :
                $contenu = "<a href='create_decree.php?id=".$this->getid()."'><img src='img/erreur1.svg' alt='Document non trouvé sur eSignature' width='".$width."px'></a>";
                $title = 'erreur';
                $class = "img";
                break;
            case STATUT_SUPPR_ESIGN :
                $contenu = "<a href='create_decree.php?id=".$this->getid()."'><img src='img/supprimer.svg' alt='Document supprimé d\'eSignature' width='".$width."px'></a>";
                $title = 'Document supprimé d\'eSignature';
                $class = "img";
                break;
            case STATUT_CORBEILLE :
                $contenu = "<a href='".ESIGNATURE_BASE_URL.ESIGNATURE_URL_DOC.$this->getIdEsignature()."' target='_blank'><img src='img/supprimer.svg' alt='Document dans la corbeille d\'eSignature' width='".$width."px'></a>";
                $title = 'Document dans la corbeille d\'eSignature';
                $class = "img";
                break;
            default :
                $contenu = "<a href='create_decree.php?id=".$this->getid()."'><img src='img/supprimer.svg' alt='annulé' width='".$width."px'></a>";
                $title = 'Annulé';
                $class = "img";
                break;
        }
		return array('contenu' => $contenu, 'title' => $title, 'class' => $class);
	}
}
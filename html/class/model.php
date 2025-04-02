<?php
require_once dirname(__FILE__,2).'/include/const.php';
require_once dirname(__FILE__,2).'/include/fonctions.php';

class model {
	
	private $_dbcon;

	private $_rdbApo;
	
	private $_idmodel;
	
	private $_iddecree_type;
	
	private $_name;
	
	private $_model_path;
	
	private $_export_path;

	private $_ref;
	
	function __construct($dbcon, $idmodel)
	{
		include (dirname(__FILE__,2)."/include/dbconnection.php");
		$this->_idmodel = intval($idmodel);
		$this->_dbcon = $dbcon;
		$this->_rdbApo = $rdbApo;
		require_once dirname(__FILE__,1).'/reference.php';
		$this->_ref = new reference($this->_dbcon, $this->_rdbApo);
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
		$select = "SELECT dty.* FROM model INNER JOIN decree_type dty ON dty.iddecree_type = model.iddecree_type WHERE model.idmodel = ?";
		$params = array($this->getid());
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
		$select = "SELECT mfi.idmodel_field, mfi.number, mfi.auto, mfi.auto_value, mfi.linkedto, mfi.complement_before, mfi.complement_after, mfi.lib_section, mfi.idfield_type_section, mfi.order, mfi.filename_position, mfi.tem_facultatif, mfi.tem_blank_line, fty.* FROM model_field mfi INNER JOIN field_type fty ON mfi.idfield_type = fty.idfield_type WHERE mfi.idmodel = ? ORDER BY mfi.order";
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
	
	function getListField($field_type)
	{
		$select = "SELECT idlist_field, value, tem_active FROM list_field WHERE idfield_type = ?";
		$params = array($field_type);
		$result = prepared_select($this->_dbcon, $select, $params);
		$fields = array();
		if ( !mysqli_error($this->_dbcon))
		{
			while ($res = mysqli_fetch_assoc($result))
			{
				$fields[] = array('key' => $res['idlist_field'], 'value' => htmlspecialchars($res['value']), 'tem_active' => $res['tem_active']);
			}
		}
		else
		{
			elog("erreur select list_fields. ".mysqli_error($this->_dbcon));
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
	
	function getInfofield($idmodel_field)
	{
		$select = "SELECT mfi.idmodel_field, mfi.number, mfi.auto, mfi.auto_value, fty.* FROM model_field mfi INNER JOIN field_type fty ON mfi.idfield_type = fty.idfield_type WHERE mfi.idmodel = ? AND mfi.idmodel_field = ?";
		$params = array($this->_idmodel, $idmodel_field);
		$result = prepared_select($this->_dbcon, $select, $params);
		$infos = array();
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				$infos = $res;
			}
		}
		else
		{
			elog("erreur select fields from model. ".mysqli_error($this->_dbcon));
		}
		return $infos;
	}

	function getExportPath()
	{
		if (isset($this->_export_path))
		{
			return $this->_export_path;
		}
		$select = 'SELECT model.export_path as model_export_path, decree_type.export_path as decree_type_export_path FROM model INNER JOIN decree_type ON decree_type.iddecree_type = model.iddecree_type WHERE idmodel = ?';
		$param = array($this->_idmodel);
		$result = prepared_select($this->_dbcon, $select, $param);
		$export_path = NULL;
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				$export_path = $res;
			}
		}
		else
		{
			elog("erreur select export_path from model. ".mysqli_error($this->_dbcon));
		}
		return $export_path;
	}

	function getWorkflow()
	{
		$select = 'SELECT idworkflow_esign FROM model WHERE idmodel = ?';
		$param = array($this->_idmodel);
		$result = prepared_select($this->_dbcon, $select, $param);
		$id = NULL;
		if ( !mysqli_error($this->_dbcon))
		{
			if ($res = mysqli_fetch_assoc($result))
			{
				$id = $res['idworkflow_esign'];
			}
			else
			{
				elog('Workflow eSignature non renseigné.');
			}
		}
		else
		{
			elog("Erreur select idworkflow_esign from model_workflow. ".mysqli_error($this->_dbcon));
		}
		return $id;
	}

	function getFieldsForFileName()
	{
		$select = "SELECT mf.idmodel_field, mf.filename_position, ft.datatype FROM model_field mf INNER JOIN field_type ft ON ft.idfield_type = mf.idfield_type WHERE mf.idmodel = ? AND mf.filename_position IS NOT NULL AND mf.filename_position > 0 ORDER BY mf.filename_position";
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

	function isActive()
	{
		$select = "SELECT active FROM model WHERE idmodel = ? AND active = 'O'";
		$params = array($this->_idmodel);
		$result = prepared_select($this->_dbcon, $select, $params);
		if ( !mysqli_error($this->_dbcon))
		{
			if (mysqli_num_rows($result) > 0)
			{
				return true;
			}
		}
		else
		{
			elog("erreur select active from model. ".mysqli_error($this->_dbcon));
		}
		return false;
	}

	function getSections()
	{
		$select = "SELECT idmodel_field, idfield_type_section FROM model_field WHERE idmodel = ? AND idfield_type_section IS NOT NULL";
		$params = array($this->_idmodel);
		$result = prepared_select($this->_dbcon, $select, $params);
		$retour = array();
		if ( !mysqli_error($this->_dbcon))
		{
			while ($res = mysqli_fetch_assoc($result))
			{
				$retour[$res['idfield_type_section']][] = $res['idmodel_field'];
			}
		}
		else
		{
			elog("erreur select section from model_field. ".mysqli_error($this->_dbcon));
		}
		return $retour;
	}

	function getModelWorkflow()
	{
		$select = "SELECT mw.idetape, mw.recipient_type, mw.recipient_default_value FROM model_workflow mw INNER JOIN model m ON m.idmodel = mw.idmodel WHERE m.idmodel = ? ORDER BY mw.idetape";
		$params = array($this->getId());
		$result = prepared_select($this->_dbcon, $select, $params);
		$values = array();
		if ( !mysqli_error($this->_dbcon))
		{
			while ($res = mysqli_fetch_assoc($result))
			{
				$values[] = $res;
			}
		}
		else
		{
			elog("erreur select section from model_field. ".mysqli_error($this->_dbcon));
		}
		return $values;
	}

	function getLastQuery()
	{
		// Récupérer la requête la plus basse pour le modèle : field_type.datatype = 'query' et max(position)
		$fields = $this->getModelFields();
		$max = 0;
		$field_type = NULL;
		foreach ($fields as $field)
		{
			if ($field['datatype'] == 'query' && $field['tem_facultatif'] == 'N' && $field['order'] > $max && $field['number'] > 0)
			{
				$max = $field['order'];
				$field_type = $field['idfield_type'];
			}
		}
		return $field_type;
	}

	function getCodeField()
	{
		$select = "SELECT fty.idfield_type FROM field_type fty WHERE fty.name = 'codemention'";
		$result = mysqli_query($this->_dbcon, $select);
		$idfield_type = 0;
		if ( !mysqli_error($this->_dbcon))
		{
			while ($res = mysqli_fetch_assoc($result))
			{
				$idfield_type = $res['idfield_type'];
			}
		}
		else
		{
			elog("erreur select idfield_type FROM field_type. ".mysqli_error($this->_dbcon));
		}
		return $idfield_type;
	}

	function getListDecreesToEditForComp($composante = null, $cod_anu = null)
	{
		$values = array();
		$field_type = $this->getLastQuery();
		if ($field_type != null)
		{
			$select = $this->getQueryField($field_type);
			if ($composante != null)
			{
				$select['query_clause'] .= " AND chv.cod_cmp = '".$composante."'";
			}
			if ($cod_anu == NULL) $cod_anu = COD_ANU;
			$select['query_clause'] .= " AND anu.cod_anu = '".$cod_anu."' ";
			$select['query_clause'] .= ' ORDER BY 3, 2 ';
			$values = $this->_ref->executeQuery($select);
		}
		return $values;
	}


	function getStats($model_decrees, $composante, $year = NULL)
	{
		require_once dirname(__FILE__,1).'/decree.php';
		$query_field = $this->getCodeField();
		$liste_to_do = $this->getListDecreesToEditForComp($composante, $year);

		$idfield_periode = ($this->_idmodel == 12) ? 104 : 7; // idfield_type de la période 7 ou 104 pour capacité
		$liste_edit = array();
		$decree_made_by_periode = array("Annuel" => array(), "P1" => array(), "P2" => array());
		$decree_doublon = array();
		$nb_decree_made = 0;
		$periode_weight = array("Annuel" => 1, "P1" => 0.5, "P2" => 0.5);
		$liste_etp_to_do = array_map('htmlspecialchars',array_column($liste_to_do,'code'));
		$decree_made = array(STATUT_VALIDE => $decree_made_by_periode, STATUT_EN_COURS => $decree_made_by_periode, "Validation de la présidence" => $decree_made_by_periode, "Visa de la composante" => $decree_made_by_periode, STATUT_BROUILLON => $decree_made_by_periode);
		foreach($model_decrees as $mdecree)
		{
			$decree = new decree($this->_dbcon, null, null, $mdecree['iddecree']);
			$query_value =  $decree->getFieldForFieldType($query_field);
			$periode_value =  $decree->getFieldForFieldType($idfield_periode);
			if (in_array($query_value, $liste_etp_to_do))
			{
				$liste_edit[$query_value][] = array('statut' => $decree->getStatusAff(), 'periode' => $periode_value == '' ? "Annuel" : $periode_value);

				$gerer_doublon = false; $doublon_valide = false;
				switch ($periode_value)
				{
					case '' : // Cas annuel
						$status = $decree->getStatus(false);
						switch ($status)
						{
							case STATUT_BROUILLON :
								// On ne compte l'arrêté annuel en brouillon que si aucun arrêté annuel ou semestriel n'a été compté sur un statut supérieur
								if (!in_array($query_value, $decree_made[$status]["Annuel"])
									&& !in_array($query_value, $decree_made[STATUT_EN_COURS]["Annuel"])
									&& !in_array($query_value, $decree_made[STATUT_VALIDE]["Annuel"])
									&& !in_array($query_value, $decree_made[STATUT_EN_COURS]["P1"])
									&& !in_array($query_value, $decree_made[STATUT_VALIDE]["P1"])
									&& !in_array($query_value, $decree_made[STATUT_EN_COURS]["P2"])
									&& !in_array($query_value, $decree_made[STATUT_VALIDE]["P2"]))
								{
									// Contrôle de la présence d'un arrêté pour un semestre dans le même statut
									$liste_statuts_inf = array($status);
									$liste_periodes = array("P1", "P2");
									foreach ($liste_statuts_inf as $st)
									{
										foreach ($liste_periodes as $pe)
										{
											$query_value_in_P = array_search($query_value, $decree_made[$st][$pe]);
											if ($query_value_in_P !== FALSE)
											{
												unset($decree_made[$st][$pe][$query_value_in_P]);
												$nb_decree_made -= $periode_weight[$pe];
												//$gerer_doublon = true;
											}
										}
									}
									$decree_made[$status]["Annuel"][] = $query_value;
									$nb_decree_made += $periode_weight["Annuel"];
								}
								break;
							case STATUT_EN_COURS :
								if (!in_array($query_value, $decree_made[$status]["Annuel"])
									&& !in_array($query_value, $decree_made[STATUT_VALIDE]["Annuel"])
									&& !in_array($query_value, $decree_made[STATUT_VALIDE]["P1"])
									&& !in_array($query_value, $decree_made[STATUT_VALIDE]["P2"]))
								{
									// Contrôle de la présence d'un arrêté pour un semestre ou année dans le même statut et statuts inférieurs
									$liste_statuts_inf = array($status, STATUT_BROUILLON);
									$liste_periodes = array("P1", "P2", "Annuel");
									$signStep = $decree->getSignStep();
									foreach ($liste_statuts_inf as $st)
									{
										foreach ($liste_periodes as $pe)
										{
											$query_value_in_P = array_search($query_value, $decree_made[$st][$pe]);
											if ($query_value_in_P !== FALSE)
											{
												unset($decree_made[$st][$pe][$query_value_in_P]);
												$nb_decree_made -= $periode_weight[$pe];
												$gerer_doublon = true;
											}
										}
									}
									$decree_made[$status]["Annuel"][] = $query_value;
									$nb_decree_made += $periode_weight["Annuel"];
									$search_in = array("Visa de la composante");
									if ($signStep == "Validation de la présidence")
									{
										$search_in[] = "Validation de la présidence";
									}
									if ($gerer_doublon)
									{
										foreach($search_in as $si)
										{
											foreach ($liste_periodes as $pe)
											{
												$query_value_in_P = array_search($query_value, $decree_made[$si][$pe]);
												if ($query_value_in_P !== FALSE)
												{
													unset($decree_made[$si][$pe][$query_value_in_P]);
												}
											}
										}
									}
									$decree_made[$signStep]["Annuel"][] = $query_value;
								}
								elseif(in_array($query_value, $decree_made[$status]["Annuel"]))
								{
									// Le seul cas où on doit modifier la stat est si l'arrêté annuel à compter est à la validation de la présidence et l'arrêté annuel présent est au visa de la composante
									$signStep = $decree->getSignStep();
									$query_value_in_P = array_search($query_value, $decree_made["Visa de la composante"]["Annuel"]);
									if ($signStep == "Validation de la présidence" && $query_value_in_P !== FALSE)
									{
										unset($decree_made["Visa de la composante"]["Annuel"][$query_value_in_P]);
										$decree_made["Validation de la présidence"]["Annuel"][] = $query_value;
									}
									$gerer_doublon = true;
								}
								else
								{
									$gerer_doublon = true;
								}
								break;
							case STATUT_VALIDE :
								if (!in_array($query_value, $decree_made[$status]["Annuel"]))
								{
									// Contrôle de la présence d'un arrêté pour un semestre ou année dans le même statut et statuts inférieurs
									$liste_statuts_inf = array($status, STATUT_EN_COURS, STATUT_BROUILLON);
									$liste_periodes = array("P1", "P2", "Annuel");
									foreach ($liste_statuts_inf as $st)
									{
										foreach ($liste_periodes as $pe)
										{
											$query_value_in_P = array_search($query_value, $decree_made[$st][$pe]);
											if ($query_value_in_P !== FALSE)
											{
												unset($decree_made[$st][$pe][$query_value_in_P]);
												$nb_decree_made -= $periode_weight[$pe];
												$gerer_doublon = true;
												if ($st == STATUT_VALIDE)
												{
													$doublon_valide = true;
													$doublon_periode = ($pe == "Annuel") ? array("Annuel") : array("Annuel", $pe);
												}
											}
										}
									}
									$decree_made[$status]["Annuel"][] = $query_value;
									$nb_decree_made += $periode_weight["Annuel"];
								}
								else
								{
									$gerer_doublon = true;
									$doublon_valide = true;
									$doublon_periode = array("Annuel");
								}
								if ($gerer_doublon && $doublon_valide)
								{
									if (!array_key_exists($query_value, $decree_doublon))
									{
										$decree_doublon[$query_value] = $doublon_periode;
									}
									else
									{
										foreach($doublon_periode as $dp)
										{
											if (!in_array($dp,$decree_doublon[$query_value]))
											{
												$decree_doublon[$query_value][] = $dp;
											}
										}
									}
								}
								break;
						}
						break;
					case 'semestre 1' :
					case 'première année' :
					case 'semestre 2' :
					case 'deuxième année' :
						if ($periode_value == 'semestre 1' || $periode_value == 'première année')
						{
							$p = "P1";
						}
						else
						{
							$p = "P2";
						}
						$status = $decree->getStatus(false);
						// Contrôle de la présence d'un arrêté pour le semestre ou année dans le même statut ou statuts supérieurs
						switch ($status)
						{
							case STATUT_BROUILLON :
								$liste_statuts_sup = array($status, STATUT_EN_COURS, STATUT_VALIDE);
								$liste_periodes = array($p, "Annuel");
								foreach ($liste_statuts_sup as $st)
								{
									foreach ($liste_periodes as $pe)
									{
										if (in_array($query_value, $decree_made[$st][$pe]))
										{
											$gerer_doublon = true;
										}
									}
								}
								if ($gerer_doublon)
								{

								}
								else
								{
									$decree_made[$status][$p][] = $query_value;
									$nb_decree_made += $periode_weight[$p];
								}
								break;
							case STATUT_EN_COURS :
								$signStep = $decree->getSignStep();
								if ($signStep == "Validation de la présidence")
								{
									$liste_statuts_sup = array($signStep, $status, STATUT_VALIDE);
									$liste_statuts_inf = array("Visa de la composante", STATUT_BROUILLON);
								}
								else // Visa de la composante
								{
									$liste_statuts_sup = array($signStep, "Validation de la présidence", $status, STATUT_VALIDE);
									$liste_statuts_inf = array(STATUT_BROUILLON);
								}
								$liste_periodes = array($p, "Annuel");
								foreach ($liste_statuts_sup as $st)
								{
									foreach ($liste_periodes as $pe)
									{
										if (in_array($query_value, $decree_made[$st][$pe]))
										{
											$gerer_doublon = true;
										}
									}
								}
								// chercher dans les statuts inférieurs pour les supprimer
								foreach ($liste_statuts_inf as $st)
								{
									foreach ($liste_periodes as $pe)
									{
										$query_value_in_P = array_search($query_value, $decree_made[$st][$pe]);
										if ($query_value_in_P !== FALSE)
										{
											unset($decree_made[$st][$pe][$query_value_in_P]);
											$nb_decree_made -= $periode_weight[$pe];
											$decree_made[$signStep][$p][] = $query_value;
											$nb_decree_made += $periode_weight[$p];
											$gerer_doublon = true;
										}
									}
								}
								if ($gerer_doublon)
								{

								}
								else
								{
									$decree_made[$status][$p][] = $query_value;
									$nb_decree_made += $periode_weight[$p];
									$decree_made[$signStep][$p][] = $query_value;
								}
								break;
							case STATUT_VALIDE :
								$liste_statuts_sup = array($status);
								$liste_statuts_inf = array(STATUT_BROUILLON, STATUT_EN_COURS);
								$liste_periodes = array($p, "Annuel");
								foreach ($liste_statuts_sup as $st)
								{
									foreach ($liste_periodes as $pe)
									{
										if (in_array($query_value, $decree_made[$st][$pe]))
										{
											$gerer_doublon = true;
											$doublon_valide = true;
											$doublon_periode = ($pe == $p) ? array($p) : array($pe, $p);
										}
									}
								}
								// chercher dans les statuts inférieurs pour les supprimer
								foreach ($liste_statuts_inf as $st)
								{
									foreach ($liste_periodes as $pe)
									{
										$query_value_in_P = array_search($query_value, $decree_made[$st][$pe]);
										if ($query_value_in_P !== FALSE)
										{
											unset($decree_made[$st][$pe][$query_value_in_P]);
											$nb_decree_made -= $periode_weight[$pe];
											$gerer_doublon = true;
										}
									}
								}
								if ($gerer_doublon)
								{
									if ($doublon_valide)
									{
										if (!array_key_exists($query_value, $decree_doublon))
										{
											$decree_doublon[$query_value] = $doublon_periode;
										}
										else
										{
											foreach($doublon_periode as $dp)
											{
												if (!in_array($dp,$decree_doublon[$query_value]))
												{
													$decree_doublon[$query_value][] = $dp;
												}
											}
										}
									}
								}
								else
								{
									$decree_made[$status][$p][] = $query_value;
									$nb_decree_made += $periode_weight[$p];
								}
								break;
						}
						break;
				}
			}
		}
		return array("decree_made" => $decree_made, "nb_decree_made" => $nb_decree_made, "decree_doublon" => $decree_doublon, "liste_edit" => $liste_edit, "query_field" => $query_field, "liste_to_do" => $liste_to_do);
	}
}
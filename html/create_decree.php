<?php
    require_once ('CAS.php');
    include './include/casconnection.php';
    require_once ('./include/fonctions.php');
    require_once ('./class/decree.php');
    require_once ('./include/dbconnection.php');
    require_once ('./class/user.php');
    require_once ('./class/model.php');
    require_once ('./class/reference.php');
    require_once ('./class/ldap.php');
    
    /*if (isset($_POST["userid"]))
        $userid = $_POST["userid"];
    else
        $userid = null;*/
	$message = '';
    $ref = new reference($dbcon, $rdbApo);
    $userid = $ref->getUserUid();
    //echo $_SESSION['uid'];
    if (is_null($userid) or ($userid == "")) 
    {
        elog("Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
        header('Location: index.php');
        exit();
    }
    
    if (isset($_GET['id']))
    {
    	$mode = 'modif';
    	$mod_decree_id = intval($_GET['id']);
    }
    elseif (isset($_POST['mod_id']))
    {    	
    	$mode = 'modif';
    	$mod_decree_id = intval($_POST['mod_id']);
    }
    else 
    {
    	$mode = 'create';
		$mod_decree_active = true;
    }
   /* if (isset($_POST['arrete'])) 
    {
    	$post_arrete = $_POST['arrete'];
    }*/
    if (isset($_POST['selectarrete']))
    {
    	$post_selectarrete = $_POST['selectarrete'];
    }
    if (isset($_POST['valide']))
    {
    	$post_valide = $_POST['valide'];
    }
    elseif (isset($_POST['duplique']))
    {
    	$post_duplique = $_POST['duplique'];
    }
   /* elseif (isset($_POST['modificatif']))
	{
		$mode = 'create';
		$post_modificatif = $_POST['modificatif'];
	}*/
    $ldap = new ldap();
    
    /*if (isset($_POST['mod_year']) && isset($_POST['mod_num']))
    {
    	$mode = 'modif';
    	$mod_year = intval($_POST['mod_year']);
    	$mod_num = intval($_POST['mod_num']);
    }*/

    //if ((isset($_POST[$modelfield['name'].$i])))
    //{
    	
    //}
    // Récupération des modeles auxquels à accès l'utilisateur
    $user = new user($dbcon, $userid);
    $superadmin = false;
    if ($user->isSuperAdmin() || $user->isDaji())
    {
		// donner accès à tous les modèles
		$superadmin = true;
    	$models = $ref->getListModel();
    	foreach ($models as $idmodel => $infos)
    	{
    		$model = new model($dbcon, $idmodel);
    		$listModels[] = $model->getModelInfo();
    	}
    }
    else 
    {
		$roles = $user->getGroupeRoles($_SESSION['groupes'], null, true); // roles actifs de l'utilisateur
    	//print_r2($_SESSION['groupes']);
	    $listModels = array();
	    foreach ($roles as $role)
	    {
	    	$model = new model($dbcon, $role['idmodel']);
	    	$listModels[] = $model->getModelInfo();
	    }
		$listModels = $ref->sortModel($listModels);
    }
    
    $menuItem = 'menu_create';
    require ("include/menu.php");
	if ($ref->getModeMaintenance() && !(isset($userCAS) && $userCAS->isSuperAdmin(false)))
	{ ?>
		<p class="alerte alerte-warning"> L'application est en maintenance. Merci de votre compréhension. </p>
	<?php }
	else
	{
		if ($mode == 'modif')
		{
			// RÉCUPÉRATION DU DOCUMENT ET DE SES PARAMÈTRES
			$mod_decree = new decree($dbcon, null, null, $mod_decree_id);
			$mod_status = $mod_decree->getStatus();
			$mod_num = $mod_decree->getNumber();
			$mod_year = $mod_decree->getYear();
			if ($mod_decree_id != NULL)
			{
				$mod_select_decree = $mod_decree->getDecree();
				$mod_decree_fields = $mod_decree->getFields();
				$mod_decree_active = $mod_decree->modelActive() || $superadmin;
			}
		}

		// SUPPRESSION DU DOCUMENT
		if (isset($_POST['supprime']) && isset($mod_decree) && $mod_status != STATUT_VALIDE && $mod_status != STATUT_SUPPR_ESIGN && $mod_decree_active)
		{
			if ($mod_status == STATUT_REFUSE)
			{
				$motif_refus = $mod_decree->getRefuseComment();
				$mod_decree_infos = $mod_decree->getDecree();
				$date_refus = $mod_decree_infos['majdate'];
				$mod_decree->setRefuseHisto(0, $motif_refus, $date_refus);
			}
			if ($mod_status == STATUT_REFUSE || $mod_status == STATUT_EN_COURS || $mod_status == STATUT_CORBEILLE)
			{
				// Supprimer d'esignature
				$retour_suppr = $mod_decree->deleteSignRequest($user->getId());
			}
			elog("Suppression du numero...");
			$mod_decree->unsetNumber($user->getId());
			$mod_decree->setStatus(STATUT_ANNULE, date("Y-m-d H:i:s"), $user->getId());
			$mod_num = 0;
			$mod_status = STATUT_ANNULE;
			if (isset($retour_suppr))
			{
				$message .= $retour_suppr;
			}
			else
			{
				$message .= "<p class='alerte alerte-success'>Le document a été supprimé.</p>";
			}
		}
		// ENVOI DU DOCUMENT A LA SIGNATURE
		elseif (isset($_POST['sign']) && isset($mod_decree) && $mod_status == STATUT_BROUILLON && $mod_decree_active)
		{
			$ldap = new ldap();
			elog('on est dans la signature...');
			if (isset($mod_decree))
			{
				$responsables = $mod_decree->getRecipientMail();
				$filename = $mod_decree->getFileName();
				if ($filename != "" && file_exists(PDF_PATH.$filename))
				{
					if (strstr($responsables, "1*") !== false && strstr($responsables, "2*") !== false )
					{
						$curl = curl_init();
						if ($user->isDaji())
						{
							$responsables .= ",1*".$ref->getUserMail();
						}
						$mail_user = $ref->getUserMail();
						$export_path = $mod_decree->getExportPath();
						//$params['targetUrls'] = '';
						if (MODE_TEST == 'O')
						{
							$responsables = "TEST NON ENVOYE : ".$responsables;
							$params = array	(
										'createByEppn' => $ref->getUserUid().'@univ-paris1.fr',
										'targetEmails' => $mail_user,
										'recipientEmails' => "1*elodie.briere@etu.univ-paris1.fr,1*".$mail_user.",2*elodie.briere@etu.univ-paris1.fr,2*".$mail_user
									);
							if ($export_path != NULL)
							{
								$params['targetUrls'] = $export_path."/TEST,".URL_WS_ZORRO;
							}
						}
						else
						{
							$params = array	(
										'createByEppn' => $ref->getUserUid().'@univ-paris1.fr',
										'targetEmails' => $mail_user,
										'recipientEmails' => $responsables
									);
							if ($export_path != NULL)
							{
								$params['targetUrls'] = $export_path.",".URL_WS_ZORRO;
							}
						}
						//$params['signRequestParamsJsonString'] = "[{ \"xPos\": 100, \"yPos\": 100, \"signPageNumber\": 1 },{ \"xPos\": 388, \"yPos\": 636, \"signPageNumber\": 1 }]";
						$params['multipartFiles'] = curl_file_create(realpath(APPLI_PATH.PDF_PATH.$filename), "application/pdf", $filename);
						//var_dump($params['multipartFiles'] );
						//$params['attachementMultipartFiles'] = $params['multipartFiles']; //curl_file_create(realpath(APPLI_PATH.PDF_PATH.'Nomination_jury_validation_delivrance_DEUG2022_47.pdf'), "application/pdf", 'Nomination_jury_validation_delivrance_DEUG2022_47.pdf');
						$params['title'] = $filename;
						$idworkflow = $mod_decree->getWorkflow();
						if ($idworkflow == NULL)
						{
							$message .= "<p class='alerte alerte-danger'>Echec de création dans eSignature. Le circuit n'est pas renseigné.</p>";
						}
						else
						{
							$opts = array(
									CURLOPT_URL => ESIGNATURE_BASE_URL.ESIGNATURE_CURLOPT_URL.$idworkflow.ESIGNATURE_CURLOPT_URL2,
									CURLOPT_CUSTOMREQUEST => "POST",
									CURLOPT_VERBOSE => true,
									CURLOPT_POST => true,
									CURLOPT_POSTFIELDS => $params,
									CURLOPT_RETURNTRANSFER => true,
									CURLOPT_SSL_VERIFYPEER => false
							);
							curl_setopt_array($curl, $opts);
							curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
							$json = curl_exec($curl);
							//print_r2($json);
							$info = curl_getinfo($curl);
							//echo "code: ${info['http_code']}";
							//print_r2($info);
							$error = curl_error ($curl);
							curl_close($curl);
							if ($error != "")
							{
								elog( "Erreur Curl = " . $error . "<br><br>");
							}
							//echo "<br>" . print_r($json,true) . "<br>";
							$id = json_decode($json, true);
							elog(var_export($opts, true));
							elog(" -- RETOUR ESIGNATURE CREATION ARRETE -- " . var_export($id, true));
							if (is_int($id) && $id > 0)
							{
								$mod_decree->setIdEsignature($id);
								$message .= "<p class='alerte alerte-success'>Le document a été envoyé à eSignature. Responsable(s) : $responsables</p>";
								$mod_status = $mod_decree->getStatus();
							}
							else
							{
								$message .= "<p class='alerte alerte-danger'>Echec de création dans eSignature.</p>";
							}
						}
					}
					else
					{
						$message .= "<p class='alerte alerte-danger'>Liste des signataires incomplète.</p>";
						elog("Liste des signataires incomplète pour le decree ".$mod_decree_id.' '.$responsables);
					}
				}
				else
				{
					$message .= "<p class='alerte alerte-danger'>Erreur de chargement du document.</p>";
					elog ("fichier pdf absent pour le decree ".$mod_decree_id." avant envoi à eSignature ".PDF_PATH.$filename);
				}
			}
			else
			{
				$message .= "<p class='alerte alerte-danger'>La structure référente n'est pas renseignée.</p>";
				elog("pas de code composante pour le decree ".$mod_decree_id." avant envoi à eSignature.");
			}
		}
		// MODIFICATION D UN ARRETE
		elseif (isset($post_selectarrete) && $post_selectarrete != '' || (isset($mod_select_decree) && $mod_decree_active))
		{
			$selectarrete = isset($mod_select_decree) ? $mod_select_decree['idmodel'] : $post_selectarrete;
			$modelselected = new model($dbcon, $selectarrete);
			$urlselected = $modelselected->getfile();
			$modelfields = $modelselected->getModelFields();

			$year = date('Y');
			if (isset($_POST['annee1']))
			{
				$year = intval($_POST['annee1']);
			}
			$numero_dispo = $ref->getNumDispo($year);

			$decreefields = array();
			if (isset($post_valide)||isset($post_duplique))
			{
				elog("Enregistrement du document : ".(isset($mod_decree_id) ? $mod_decree_id : 'nouveau'));
				// Si le document est en mode modif et qu'il n'est pas validé dans esignature on supprime le numero d'arrêté et on crée un nouveau
				if (isset($mod_year) && isset($mod_num) && isset($post_valide) && $post_valide == "Remplacer")
				{
					$mod_decree = new decree($dbcon, null, null, $mod_decree_id);
					$mod_decree_infos = $mod_decree->getDecree();
					if ($mod_decree_infos != NULL && $mod_decree->getStatus() != STATUT_VALIDE)
					{
						if ($mod_status == STATUT_REFUSE)
						{
							$motif_refus = $mod_decree->getRefuseComment();
							$date_refus = $mod_decree_infos['majdate'];
						}
						if ($mod_status == STATUT_REFUSE || $mod_status == STATUT_EN_COURS || $mod_status == STATUT_CORBEILLE)
						{
							// TODO : Supprimer d'esignature
							$retour_suppr = $mod_decree->deleteSignRequest($user->getId());
							$message .= $retour_suppr;
						}
						elog("Suppression du numero...");
						$oldyear = $mod_decree->getYear();
						$mod_decree->unsetNumber($user->getId());
						if ($oldyear == $year)
						{
							$numero_dispo = $ref->getNumDispo($year);
							elog("numero dispo remplacé par l'ancien");
						}
						// TODO : Supprimer le PDF qui avait été créé
					}
				}
				elseif($post_valide == "Enregistrer" && isset($mod_decree_id))
				{
					$mod_decree = new decree($dbcon, null, null, $mod_decree_id);
					$mod_decree_infos = $mod_decree->getDecree();
					if ($mod_decree_infos != NULL && ($mod_decree->getStatus() == STATUT_BROUILLON || $mod_decree->getStatus() == STATUT_HORS_ZORRO))
					{
						$oldyear = $mod_decree->getYear();
						if ($oldyear == $year)
						{
							$numero_dispo = $mod_decree->getNumber();
							elog("numero dispo remplacé par l'ancien : Enregistrement d'un brouillon.");
						}
					}
				}
				$idmodel_field_numero = $modelselected->getNumeroId();
				$erreurnumero = false;
				if ($idmodel_field_numero != 0)
				{
					$infos_field_numero = $modelselected->getInfofield($idmodel_field_numero);
					if (array_key_exists('auto', $infos_field_numero) && $infos_field_numero['auto'] == 'O')
					{
						$decreefields[] = array('idmodel_field' => $idmodel_field_numero, 'value' => $numero_dispo);
					}
					else
					{
						// Les numéros pour ce modèle sont gérés à la main
						if (isset($_POST["numero1"]))
						{
							if ($ref->decreeNumExists($_POST["numero1"], $year) && isset($post_duplique))
							{
								$erreurnumero = true;
							}
							else
							{
								$numero_dispo = $_POST["numero1"];
								elog("numero dispo remplacé par post :".$_POST["numero1"]);
							}
						}
						else
						{
							// Le numéro n'est pas saisi : on met un numéro automatique
						}
					}
				}
				else
				{
					// Le numéro n'est pas paramétrable sur le modèle : on met un numéro automatique
				}
				if (!$erreurnumero)
				{
					foreach ($modelfields as $modelfield)
					{
						$linked = $modelfield['linkedto'];
						$linked_ok = true;
						if ($linked != NULL)
						{
							$linked_field_name = $ref->getModelFieldName($modelfield['linkedto']);
							if (!isset($_POST[$linked_field_name]) && !(isset($_POST[$linked_field_name."1"]) && $_POST[$linked_field_name."1"] != ''))
							{
								$linked_ok = false;
							}
						}
						if ($linked_ok)
						{
							if ($modelfield['auto'] == 'N')
							{
								if ($modelfield['datatype'] != 'object' && $modelfield['datatype'] != 'room')
								{
									if ($modelfield['number'] == '+')
									{
										$valeurs = isset($_POST[$modelfield['name']]) ? $_POST[$modelfield['name']] : array();
										foreach($valeurs as $valeur)
										{
											$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => htmlspecialchars($valeur));
										}
										if (isset($_POST[$modelfield['name']."1"]) && $_POST[$modelfield['name']."1"] != '')
										{
											$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => htmlspecialchars($_POST[$modelfield['name']."1"]));
										}
									}
									else
									{
										for($i = 1; $i <= $modelfield['number']; $i++)
										{
											if (isset($_POST[$modelfield['name'].$i]) && $_POST[$modelfield['name'].$i] != '')
											{
												$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => htmlspecialchars($_POST[$modelfield['name'].$i]));
											}
										}
									}
								}
							}
							elseif ($modelfield['auto_value'] !== NULL)
							{
								if ($modelfield['auto_value'] == '')
								{
									if (isset($_POST[$modelfield['name']."1"]))
									{
										$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => htmlspecialchars($_POST[$modelfield['name']."1"]));
									}
								}
								else
								{
									$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => $modelfield['auto_value']);
								}
							}
						}
					}
					$idmodel = $post_selectarrete;
					$structure = htmlspecialchars($_POST['structure1']);
					if (isset($post_valide) && $post_valide == "Enregistrer" && isset($mod_status) && ($mod_status == STATUT_BROUILLON || $mod_status == STATUT_HORS_ZORRO))
					{
						// update le decree
						$decree = new decree($dbcon, null, null, $mod_decree_id);
						$numero_dispo = $decree->getNumber();
						elog("numero dispo remplace par numero du decree en modif : ".$numero_dispo);
						$decree->save($user->getid(), $idmodel, $structure, true);
						if ($modelfield['datatype'] == 'object')
						{
							$listobjects = $ref->getObjectsList();
							foreach($listobjects as $idobj => $object)
							{
								if (isset($_POST['newtarifpub'.$idobj]) && $_POST['newtarifpub'.$idobj] != '')
								{
									$idobjectBD = $ref->setObjectPrices($idobj, $_POST['anctarifpub'.$idobj], $_POST['newtarifpub'.$idobj], $_POST['anctarifep'.$idobj], $_POST['newtarifep'.$idobj], $mod_decree_id);
									$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => $idobjectBD);
								}
							}
						}
						elseif ($modelfield['datatype'] == 'room')
						{
							$listsalles = $ref->getRoomsList();
							foreach($listsalles as $idsalle => $salle)
							{
								if (isset($_POST['newtarifheure'.$idsalle]) && $_POST['newtarifheure'.$idsalle] != '')
								{
									$idsalleBD = $ref->setRoomPrices($idsalle, $_POST['anctarifheure'.$idsalle], $_POST['newtarifheure'.$idsalle], $_POST['anctarifdemi'.$idsalle], $_POST['newtarifdemi'.$idsalle], $_POST['anctarifjour'.$idsalle], $_POST['newtarifjour'.$idsalle], $mod_decree_id);
									$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => $idsalleBD);
								}
							}
						}
						$decree->setFields($decreefields, true);
					}
					else
					{
						$decree = new decree($dbcon, $year, $numero_dispo);
						elog('decree cree avec numero : '.$numero_dispo);
						$decree->save($user->getid(), $idmodel, $structure);
						if ($modelfield['datatype'] == 'object')
						{
							$listobjects = $ref->getObjectsList();
							foreach($listobjects as $idobj => $object)
							{
								if (isset($_POST['newtarifpub'.$idobj]) && $_POST['newtarifpub'.$idobj] != '')
								{
									$idobjectBD = $ref->setObjectPrices($idobj, $_POST['anctarifpub'.$idobj], $_POST['newtarifpub'.$idobj], $_POST['anctarifep'.$idobj], $_POST['newtarifep'.$idobj]);
									$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => $idobjectBD);
								}
							}
						}
						elseif ($modelfield['datatype'] == 'room')
						{
							$listsalles = $ref->getRoomsList();
							foreach($listsalles as $idsalle => $salle)
							{
								if (isset($_POST['newtarifheure'.$idsalle]) && $_POST['newtarifheure'.$idsalle] != '')
								{
									$idsalleBD = $ref->setRoomPrices($idsalle, $_POST['anctarifheure'.$idsalle], $_POST['newtarifheure'.$idsalle], $_POST['anctarifdemi'.$idsalle], $_POST['newtarifdemi'.$idsalle], $_POST['anctarifjour'.$idsalle], $_POST['newtarifjour'.$idsalle]);
									$decreefields[] = array('idmodel_field' => $modelfield['idmodel_field'], 'value' => $idsalleBD);
								}
							}
						}
						$decree->setFields($decreefields);
						if (isset($mod_status) && $mod_status == STATUT_REFUSE)
						{
							$mod_decree->setRefuseHisto($decree->getid(), $motif_refus, $date_refus);
							$mod_decree->setStatus(STATUT_REMPLACE, date("Y-m-d H:i:s"), $user->getid());
						}
					}
					$modelselected = new model($dbcon, $idmodel);
					$modelfile = new ZipArchive();
					if (file_exists("./models/".$modelselected->getfile()))
					{
						// TODO : Rendre l'année universitaire d'affichage paramétrable en BDD dans le modèle
						if (($decree->getModel()->getModelInfo()['iddecree_type'] == 2 || $decree->getModel()->getModelInfo()['iddecree_type'] == 6) && $decree->getModel()->getModelInfo()['idmodel'] != 7 && $decree->getModel()->getModelInfo()['idmodel'] != 41)
						{
							$anneeuniv = $ref->getAnneeUni(1);
						}
						else
						{
							$anneeuniv = $ref->getAnneeUni();
							$anneeunivplusun =  $ref->getAnneeUni(1);
							$anneeunivplusdeux = $ref->getAnneeUni(2);
						}
						$fieldstoinsert = $decree->getFields();
						// echo "fieldstoinsert <br><br>";print_r2($fieldstoinsert);
						$modelfields = $modelselected->getModelFields();
						// On repère les sections non représentées pour supprimer les lignes dans le document
						$modelsections = $modelselected->getSections();
						$sectionabsente = array();
						foreach($modelsections as $section => $listIdFieldType)
						{
							if (sizeof(array_intersect($listIdFieldType, array_keys($fieldstoinsert))) == 0)
							{
								$sectionabsente[] = $section;
							}
						}
						//echo "<br>modelfields <br><br>"; print_r2($modelfields);
						$modelfieldsarrange = array_column($modelfields, 'idmodel_field', 'name');
						$modelfieldstype = array_column($modelfields, 'datatype', 'idmodel_field');
						$modelfieldscomp = array_column($modelfields, 'complement_after', 'name');
						$modelfieldscompb = array_column($modelfields, 'complement_before', 'name');
						//echo "<br>modelfieldsarrange <br><br>"; print_r2($modelfieldsarrange);
						//echo "<br>modelfieldstype <br><br>"; print_r2($modelfieldstype);
						// copie du modele pour l'arrêté
						$odtfilename = $decree->getFileName("odt", true);
						copy("./models/".$modelselected->getfile(), PDF_PATH.$odtfilename);
						// ouverture du modele pour l'arrêté
						$modelfile->open(PDF_PATH.$odtfilename);
						// extraction du content.xml dans le dossier temporaire pour l'arrêté
						// TODO : nommer le document selon TAGS
						$modelfile->extractTo(PDF_PATH.$year.'_'.$numero_dispo."/", array('content.xml', 'styles.xml'));
						// ouverture du content.xml extrait
						$content = fopen(PDF_PATH.$year.'_'.$numero_dispo."/content.xml", 'r+');
						// lecture du content.xml extrait
						$contenu = fread($content, filesize(PDF_PATH.$year.'_'.$numero_dispo."/content.xml"));
						$doc = new DOMDocument('1.0', 'utf-8');
						$doc->preserveWhiteSpace = false;
						$doc->formatOutput = true;
						$doc->loadXML($contenu);
						$x = $doc->documentElement;
						$body = $x->getElementsByTagName('body')->item(0);
						// echo "BODY 1 : <br>"; print_r2($body);
						foreach ($fieldstoinsert as $idmodel_field => $field)
						{
							// dupliquer les champs multiples
							$nbChamps = sizeof($field);
							if ($nbChamps > 1)
							{
								$champ = array_keys($modelfieldsarrange, $idmodel_field)[0];
								if ($champ == 'objetpromo' || $champ == 'espaceloc')
								{
									// echo "Champs à multiplier : ";print_r2($field);
									// trouver le champs dans le xml
									$noeudcourant = $body; // le dernier noeud contenant le champ
									$noeudpere = $body; // le noeud où raccrocher le clone du champ
									$noeudadupliquer = $body; // le noeud à cloner
									$positiondunoeudadupliquer = 0; // la position où raccrocher le clone du champ sous le noeud père
									while (strpos($noeudcourant->textContent, '$$$'.$champ.'$$$') !== false)
									{
										if ($noeudcourant->nodeName != 'table:table-cell')
										{
											if ($noeudcourant->nextSibling != null || $noeudcourant->previousSibling != null)
											{
												$noeudadupliquer = $noeudcourant;
											}
											if ($noeudcourant->nodeName == 'table:table')
											{
												$noeudpere = $noeudcourant;
												$positiondunoeudadupliquer = $noeudcourant->childNodes->count();
											}
											foreach($noeudcourant->childNodes as $node)
											{
												if (strpos($node->textContent, '$$$'.$champ.'$$$') !== false)
												{
													$noeudcourant = $node;
													// echo "Noeud contenant le champ : <br>"; print_r2($node);
													break;
												}
												if ($noeudpere == $noeudcourant)
												{
													$positiondunoeudadupliquer++;
												}
											}
										}
										else
										{
											// echo "Dernier noeud contenant le champ : <br>"; print_r2($node)
											break;
										}
										// echo "Evolution noeud_pere <br>";print_r2($noeudpere);
									}
									// echo "Noeud à dupliquer  : <br>"; print_r2($noeudadupliquer);
									// echo "Noeud père où raccrocher la copie : <br>"; print_r2($noeudpere);
									// echo "Position où raccrocher sous le père : <br>"; print_r2($positiondunoeudadupliquer);
									// echo "Noeud à la position où raccrocher sous le père : <br>"; print_r2($noeudpere->childNodes->item($positiondunoeudadupliquer));
									for ($i = 1; $i < $nbChamps; $i++)
									{
										// dupliquer le noeud
										$clone_parfait = $noeudadupliquer->cloneNode(true);
										$clone = $noeudadupliquer->cloneNode();
										foreach ($clone_parfait->childNodes as $node)
										{
											$clone_node = $node->cloneNode(true);
											foreach($node->attributes as $attr)
											{
												if ($attr->nodeName == "table:style-name")
												{
													// Corriger le numéro des cellules pour chaque nouvelle ligne
													$pos_point = strpos($attr->nodeValue, '.');
													$debut = substr($attr->nodeValue, 0, $pos_point + 1);
													if (strlen(substr($attr->nodeValue, $pos_point + 1)) > 1)
													{
														$fin = substr($attr->nodeValue, $pos_point + 1, 1);
														$fin .= substr($attr->nodeValue, $pos_point + 2)+$i;
													}
													else
													{
														$fin = substr($attr->nodeValue, $pos_point + 1)+$i;
													}
													$new_value = $debut.$fin;
													$clone_node->setAttribute('value', $new_value);
												}
												$clone->appendChild($clone_node);
											}

										}
										// insérer le noeud
										$noeudpere->appendChild($clone);
									}
								}
								else {
									// echo "Champs à multiplier : ";print_r2($field);
									// trouver le champs dans le xml
									$noeudcourant = $body; // le dernier noeud contenant le champ
									$noeudpere = $body; // le noeud où raccrocher le clone du champ
									$noeudadupliquer = $body; // le noeud à cloner
									$positiondunoeudadupliquer = 0; // la position où raccrocher le clone du champ sous le noeud père
									while (strpos($noeudcourant->textContent, '$$$'.$champ.'$$$') !== false)
									{
										if ($noeudcourant->hasChildNodes())
										{
											if ($noeudcourant->nextSibling != null || $noeudcourant->previousSibling != null)
											{
												$noeudadupliquer = $noeudcourant;
											}
											if ($noeudcourant->childNodes->count() > 1)
											{
												$noeudpere = $noeudcourant;
												$positiondunoeudadupliquer = 0;
											}
											foreach($noeudcourant->childNodes as $node)
											{
												if (strpos($node->textContent, '$$$'.$champ.'$$$') !== false)
												{
													$noeudcourant = $node;
													// echo "Noeud contenant le champ : <br>"; print_r2($node);
													break;
												}
												if ($noeudpere == $noeudcourant)
												{
													$positiondunoeudadupliquer++;
												}
											}
										}
										else
										{
											// echo "Dernier noeud contenant le champ : <br>"; print_r2($node);
											break;
										}
									}
									// Si le champ est de type liste on modifie le texte au lieu de dupliquer le noeud contenant le texte
									// Pour éviter l'insertion d'un espace non souhaité
									if ($modelfieldstype[$idmodel_field] == 'list')
									{
										$contenu_noeud_courant = $noeudcourant->textContent;
										$position_de_la_chaine = strpos($contenu_noeud_courant, '$$$'.$champ.'$$$');
										$prefixe = '';
										if ($position_de_la_chaine > 0)
										{
											$prefixe = substr($contenu_noeud_courant, 0, $position_de_la_chaine);
										}
										for ($i = 1; $i < $nbChamps; $i++)
										{
											$contenu_noeud_courant = $prefixe."$$$".$champ."$$$".substr($contenu_noeud_courant, $position_de_la_chaine);
										}
										$clone_parfait = $noeudadupliquer->cloneNode(true);
										$clone = $noeudadupliquer->cloneNode();
										foreach ($clone_parfait->childNodes as $node)
										{
											$clone_node = $node->cloneNode(true);
											if (!$node->hasAttributes())
											{
												if ($node->nodeName == "#text")
												{
													$node->textContent = $contenu_noeud_courant;
													$noeudpere->replaceChild($clone_parfait, $noeudadupliquer);
												}
											}

										}
									}
									else
									{
										for ($i = 1; $i < $nbChamps; $i++)
										{
											// dupliquer le noeud
											$clone = $noeudadupliquer->cloneNode(true);
											// insérer le noeud
											$noeudpere->insertBefore($clone, $noeudpere->childNodes->item($positiondunoeudadupliquer));
											// echo "Noeud père après $i ème insert : <br>"; print_r2($noeudpere);
										}
									}
								}
							}
						}
						// print_r2($body);
						// enregistrement du xml modifié
						$doc->save(PDF_PATH.$year.'_'.$numero_dispo."/content2.xml");
						fclose($content);
						$content = fopen(PDF_PATH.$year.'_'.$numero_dispo."/content2.xml", 'r+');
						// lecture du content2.xml extrait
						$contenu = fread($content, filesize(PDF_PATH.$year.'_'.$numero_dispo."/content2.xml"));
						// copie du contenu extrait
						$contenu2 = $contenu;
						//echo "<br>contenu <br><br>"; print_r2($contenu);echo "<br><br>";
						$position1 = strpos($contenu, '$$$'); // position de la balise de début d'un champ paramétrable
						$position2 = strpos($contenu, '$$$', $position1+1); // position de la balise de fin d'un champ paramétrable
						//print_r2(strlen($contenu));
						$nb_field = array();
						$champsamodif = array();
						while ($position1 < strlen($contenu) && substr($contenu, $position1 + 3, $position2 - $position1 - 3) && $position1 !== false && $position2 !== false)
						{
							$field = substr($contenu, $position1 + 3, $position2 - $position1 - 3); // le nom du champ est entre les balises
							$comp_before = '';
							if (!key_exists($field, $nb_field))
							{
								$nb_field[$field] = 0;
							}
							$comp_after = '';
							if (array_key_exists($field, $modelfieldscomp) && $modelfieldscomp[$field] != null)
							{
								$comp_after = $modelfieldscomp[$field];
							}
							if (array_key_exists($field, $modelfieldscompb) && $modelfieldscompb[$field] != null)
							{
								$comp_before .= $modelfieldscompb[$field];
							}
							if (key_exists($field, $modelfieldsarrange) && key_exists($modelfieldsarrange[$field], $fieldstoinsert) && key_exists($nb_field[$field], $fieldstoinsert[$modelfieldsarrange[$field]]))
							{

								// echo "($position1 - $position2) à remplacer : $$$".$field."$$$ par : ".$fieldstoinsert[$modelfieldsarrange[$field]][$nb_field[$field]]['value']."<br>";
								if ($modelfieldstype[$modelfieldsarrange[$field]] == 'user')
								{
									$champsamodif[] = array("valeur" => "- ".$comp_before.$fieldstoinsert[$modelfieldsarrange[$field]][$nb_field[$field]]['value'].$comp_after, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($modelfieldstype[$modelfieldsarrange[$field]] == 'checkbox' || $modelfieldstype[$modelfieldsarrange[$field]] == 'checkbox2')
								{
									$champsamodif[] = array("valeur" => $comp_before."[x]".$comp_after, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($modelfieldstype[$modelfieldsarrange[$field]] == 'date')
								{
									$date = new DateTime($fieldstoinsert[$modelfieldsarrange[$field]][$nb_field[$field]]['value']);
									$date = $date->format("d/m/Y");
									$champsamodif[] = array("valeur" => $comp_before.$date.$comp_after, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($modelfieldstype[$modelfieldsarrange[$field]] == 'list')
								{
									if ($nb_field[$field] != 0)
									{
										$comp_before = ', ';
									}
									$champsamodif[] = array("valeur" => $comp_before.$fieldstoinsert[$modelfieldsarrange[$field]][$nb_field[$field]]['value'].$comp_after, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($modelfieldstype[$modelfieldsarrange[$field]] == 'group')
								{
									$nom_struct = $ldap->getStructureName($fieldstoinsert[$modelfieldsarrange[$field]][$nb_field[$field]]['value']);
									$champsamodif[] = array("valeur" => $comp_before.$nom_struct.$comp_after, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($modelfieldstype[$modelfieldsarrange[$field]] == 'object')
								{
									$listobjects = $ref->getObjectsList();
									$prixobj = $ref->getObjectPricesById($fieldstoinsert[$modelfieldsarrange[$field]][$nb_field[$field]]['value']);
									$nom_obj = $listobjects[$prixobj['idobject_type']]['name'];
									$ancprixpub = $prixobj['old_tarif_public'] == '' ? $prixobj['old_tarif_public'] : $prixobj['old_tarif_public']." €";
									$newprixpub = $prixobj['new_tarif_public']." €";
									$ancprixep = $prixobj['old_tarif_etu_pers'] == '' ? $prixobj['old_tarif_etu_pers'] : $prixobj['old_tarif_etu_pers']." €";
									$newprixep = $prixobj['new_tarif_etu_pers']." €";
									$champsamodif[] = array("valeur" => $comp_before.$nom_obj.$comp_after, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($modelfieldstype[$modelfieldsarrange[$field]] == 'room')
								{
									$listrooms = $ref->getRoomsList();
									$prixsalle = $ref->getRoomPricesById($fieldstoinsert[$modelfieldsarrange[$field]][$nb_field[$field]]['value']);
									$nom_salle = $listrooms[$prixsalle['idroom_type']]['name'];
									$ancprixheure = $prixsalle['old_tarif_heure'] == '' ? $prixsalle['old_tarif_heure'] : $prixsalle['old_tarif_heure']." €";
									$newprixheure = $prixsalle['new_tarif_heure']." €";
									$ancprixdemi = $prixsalle['old_tarif_demi'] == '' ? $prixsalle['old_tarif_demi'] : $prixsalle['old_tarif_demi']." €";
									$newprixdemi = $prixsalle['new_tarif_demi']." €";
									$ancprixjour = $prixsalle['old_tarif_jour'] == '' ? $prixsalle['old_tarif_jour'] : $prixsalle['old_tarif_jour']." €";
									$newprixjour = $prixsalle['new_tarif_jour']." €";
									$champsamodif[] = array("valeur" => $comp_before.$nom_salle.$comp_after, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								else
								{
									$champsamodif[] = array("valeur" => $comp_before.$fieldstoinsert[$modelfieldsarrange[$field]][$nb_field[$field]]['value'].$comp_after, "position" => $position1, "longueur" => (strlen($field)+6));
								}
							}
							else
							{
								$idfield_type = $ref->getIdfieldTypeByName($field);
								if ($field == "anneeuni")
								{
									$champsamodif[] = array("valeur" => $anneeuniv, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($field == "anneeuniplusun")
								{
									$champsamodif[] = array("valeur" => $anneeunivplusun, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($field == "anneeuniplusdeux")
								{
									$champsamodif[] = array("valeur" => $anneeunivplusdeux, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($field == "anctarifpub")
								{
									$champsamodif[] = array("valeur" => $ancprixpub, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($field == "newtarifpub")
								{
									$champsamodif[] = array("valeur" => $newprixpub, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($field == "anctarifetupers")
								{
									$champsamodif[] = array("valeur" => $ancprixep, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($field == "newtarifetupers")
								{
									$champsamodif[] = array("valeur" => $newprixep, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($field == "newtarifheure")
								{
									$champsamodif[] = array("valeur" => $newprixheure, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($field == "anctarifheure")
								{
									$champsamodif[] = array("valeur" => $ancprixheure, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($field == "newtarifdemi")
								{
									$champsamodif[] = array("valeur" => $newprixdemi, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($field == "anctarifdemi")
								{
									$champsamodif[] = array("valeur" => $ancprixdemi, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($field == "newtarifjour")
								{
									$champsamodif[] = array("valeur" => $newprixjour, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ($field == "anctarifjour")
								{
									$champsamodif[] = array("valeur" => $ancprixjour, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif ((array_key_exists($field, $modelfieldsarrange) && array_key_exists($modelfieldsarrange[$field], $modelfieldstype) && $modelfieldstype[$modelfieldsarrange[$field]] == 'checkbox')
										|| ($idfield_type != null && in_array($idfield_type, $sectionabsente))) // Pour supprimer les lignes des sections inutilisées
								{
									// Pour supprimer la ligne dans le document chercher le "<text:p" précédent et "</text:p>" suivant
									$position_debut = strrpos(substr($contenu, 0, $position1), "<text:p");
									$position_fin = strpos($contenu, "</text:p>", $position1);
									$longueur = $position_fin - $position_debut + 1;
									$champsamodif[] = array("valeur" => '', "position" => $position_debut, "longueur" => $longueur);
									// décaler le curseur à la fin de la ligne, pour éviter les champs compris dans la sélection
									$position1 = $position_fin;
									$position2 = $position_fin;
								}
								elseif(key_exists($field, $modelfieldsarrange) && key_exists($modelfieldsarrange[$field], $fieldstoinsert) && key_exists(0, $fieldstoinsert[$modelfieldsarrange[$field]]))
								{
									$champsamodif[] = array("valeur" => $comp_before.$fieldstoinsert[$modelfieldsarrange[$field]][0]['value'].$comp_after, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								elseif (array_key_exists($field, $modelfieldsarrange) && array_key_exists($modelfieldsarrange[$field], $modelfieldstype) && $modelfieldstype[$modelfieldsarrange[$field]] == 'checkbox2')
								{
									$champsamodif[] = array("valeur" => "[ ]".$comp_after, "position" => $position1, "longueur" => (strlen($field)+6));
								}
								else
								{
									//echo "($position1 - $position2) à remplacer : $$$".$field."$$$ par : vide <br>";
									$champsamodif[] = array("valeur" => '', "position" => $position1, "longueur" => (strlen($field)+6));
								}
							}
							$nb_field[$field] += 1;
							$position1 = strpos($contenu, '$$$', $position2 + 3);
							$position2 = strpos($contenu, '$$$', $position1 + 1);
						}
						fclose($content);
						$content = fopen(PDF_PATH.$year.'_'.$numero_dispo."/content2.xml", 'w');
						$champsamodiffromlast = array_reverse($champsamodif);
						// remplacement des champs à partir de la fin du fichier
						foreach ($champsamodiffromlast as $champ)
						{
							$contenu2 = substr_replace($contenu2, $champ['valeur'], $champ['position'], $champ['longueur']);
						}
						// écriture du contenu modifié dans le fichier
						fwrite($content, $contenu2);
						// Ajout du fichier dans le document
						$modelfile->addFile(PDF_PATH.$year.'_'.$numero_dispo."/content2.xml", 'content.xml');
						//print_r2($fieldstoinsert);

						// Modification du pied de page année / numero
						$styles = fopen(PDF_PATH.$year.'_'.$numero_dispo."/styles.xml", 'r+');
						$stylescontenu = fread($styles, filesize(PDF_PATH.$year.'_'.$numero_dispo."/styles.xml"));
						$stylesnew = $stylescontenu;
						fclose($styles);
						$styles = fopen(PDF_PATH.$year.'_'.$numero_dispo."/styles2.xml", 'w');
						$position_annee = 0;
						while (strpos($stylesnew, '$$$annee$$$',$position_annee) !== false)
						{
							$position_annee = strpos($stylesnew, '$$$annee$$$',$position_annee);
							$stylesnew = substr_replace($stylesnew, $fieldstoinsert[$modelfieldsarrange['annee']][0]['value'], $position_annee, 11);
						}
						$position_numero = 0;
						while (strpos($stylesnew, '$$$numero$$$',$position_numero) !== false)
						{
							$position_numero = strpos($stylesnew, '$$$numero$$$');
							$stylesnew = substr_replace($stylesnew, $fieldstoinsert[$modelfieldsarrange['numero']][0]['value'], $position_numero, 12);
						}
						fwrite($styles, $stylesnew);
						fclose($styles);
						// Ajout du fichier dans le document
						$modelfile->addFile(PDF_PATH.$year.'_'.$numero_dispo."/styles2.xml", 'styles.xml');
						$modelfile->close();

						// CONVERSION EN PDF
						$descriptorspec = array(
								0 => array("pipe", "r"),  // stdin
								1 => array("pipe", "w"),  // stdout
								2 => array("pipe", "w"),  // stderr
						);
						if (isset($_SERVER['SystemRoot']) && strpos($_SERVER['SystemRoot'], 'WINDOWS') === false)
						{
							$process = proc_open("unoconv --doctype=document --format=pdf \"".PDF_PATH.$decree->getFileName("odt")."\"", $descriptorspec, $pipes);
						}
						else
						{
							$process = proc_open("python.exe \"C:\Program Files\Unoconv\unoconv-0.8.2\unoconv\" --doctype=document --format=pdf \"".PDF_PATH.$decree->getFileName("odt")."\"", $descriptorspec, $pipes);
						}
						$stdout = stream_get_contents($pipes[1]);
						fclose($pipes[1]);

						$stderr = stream_get_contents($pipes[2]);
						fclose($pipes[2]);
						if ($stdout != "")
						{
							elog( "stdout : \n");
							elog($stdout);
							elog( "La création du document PDF a échoué. <br>");
							$message .= "<p class='alerte alerte-danger'>La création du document a échoué.</p>";
						}
						elseif ($stderr != "")
						{
							elog( "stderr :\n");
							elog($stderr);
							elog( "La création du document PDF a échoué. <br>");
							$message .= "<p class='alerte alerte-danger'>La création du document a échoué.</p>";
						}
						else
						{
							$message .= "<p class='alerte alerte-success'>Document enregistré.</p>";
						}
						?>
				<?php }
				else
				{
					elog("Le document "."./models/".$modelselected->getfile()." n'existe pas !");
				}
				if ($mode == 'create' || (isset($post_valide) && $post_valide == "Remplacer") || isset($post_duplique) || (isset($post_valide) && $post_valide == "Enregistrer" && isset($mod_status) && ($mod_status == STATUT_BROUILLON || $mod_status == STATUT_HORS_ZORRO)))
					{
						$mod_num = $numero_dispo;
						$mod_year = $year;
						$mod_decree_id = $decree->getId();
						if ($decree->getModelId() == 27)
						{
							$mod_status = STATUT_HORS_ZORRO;
						}
						else
						{
							$mod_status = STATUT_BROUILLON;
						}
						$mode = 'modif';
					}
				}
				else
				{
					$message .= "<p class='alerte alerte-danger'>Le numéro existe déjà pour cette année.</p>";
				}
			}
		}
		else
		{
			if(!$mod_decree_active)
			{
				$message .= "<p class='alerte alerte-danger'>Le modèle de document est désactivé.</p>";
			}
		}
	?>

	<?php // ------------------------------------------------------- AFFICHAGE ------------------------------------------------------- ?>

	<script>
	function ajouterValeur(divname, value='')
	{
		var table = document.getElementById("table_"+divname);
		var row = table.insertRow(-1);
		var rowindex = row.rowIndex;
		var nameindex = parseInt(rowindex+2,10);
		var cell0 = row.insertCell(0);
		var name = document.createElement("input");
		name.setAttribute("type", "text");
		name.setAttribute("id", divname+"[]");
		name.setAttribute("name", divname+"[]");
		if(value != '') {
			name.setAttribute("value", value);
		} else {
			name.setAttribute("value", document.getElementById(divname+"1").value);
		}
		name.setAttribute("readonly", true);
		document.getElementById(divname+"1").value = '';
		document.getElementById(divname+"1").focus();
		cell0.appendChild(name);
		var cell1 = row.insertCell(1);
		row.setAttribute("id", "row"+divname+nameindex);
		var moins = document.createElement("button");
		moins.innerText = "-";
		moins.setAttribute("onclick", "return supprimerValeur('moins"+divname+nameindex+"');");
		cell1.setAttribute("id", "moins"+divname+nameindex);
		cell1.appendChild(moins);
		return false;
	}

	function supprimerValeur(cellid)
	{
		var cell = document.getElementById(cellid);
		var row = document.getElementById(cell.parentNode.id);
		var rowindex = row.rowIndex;
		var table = row.parentNode;
		table.deleteRow(rowindex);
		return false;
	}

	function ajouterObjet(divname)
	{
		var idobj = document.getElementById(divname+"1").value;
		var ligne = document.getElementById("objet"+idobj);
		ligne.removeAttribute("hidden");
		var opt = document.getElementById("option"+idobj);
		opt.setAttribute("hidden", "");
		return false;
	}

	function supprimerObjet(cellid)
	{
		var ligne = document.getElementById("objet"+cellid);
		var newtarifpub = document.getElementById("newtarifpub"+cellid);
		var newtarifep = document.getElementById("newtarifep"+cellid);
		var opt = document.getElementById("option"+cellid);
		opt.removeAttribute("hidden");
		ligne.setAttribute("hidden", "");
		newtarifpub.value = '';
		newtarifep.value = '';
		return false;
	}

	function ajouterSalle(divname)
	{
		var idsalle = document.getElementById(divname+"1").value;
		var ligne = document.getElementById("salle"+idsalle);
		ligne.removeAttribute("hidden");
		var opt = document.getElementById("option"+idsalle);
		opt.setAttribute("hidden", "");
		return false;
	}

	function supprimerSalle(cellid)
	{
		var ligne = document.getElementById("salle"+cellid);
		var newtarifheure = document.getElementById("newtarifheure"+cellid);
		var newtarifdemi = document.getElementById("newtarifdemi"+cellid);
		var newtarifjour = document.getElementById("newtarifjour"+cellid);
		var opt = document.getElementById("option"+cellid);
		opt.removeAttribute("hidden");
		ligne.setAttribute("hidden", "");
		newtarifheure.value = '';
		newtarifdemi.value = '';
		newtarifjour.value = '';
		return false;
	}

	function activeLinked(divname){
		//alert(divname);
		var divs = document.getElementsByName('linked_'+divname);
		for (var i=0, c=divs.length; i<c; i++)
		{
			//alert(divs[i].id);
			var display = divs[i].getAttribute("style");
			if (display == 'display:none;')
			{
				divs[i].setAttribute("style", "display:block;");
			}
			else
			{
				divs[i].setAttribute("style", 'display:none;');
			}
		}
	}
	</script>
	<div id="contenu1">
	<?php
	if ($mode == 'modif')
	{
		// RÉCUPÉRATION DU DOCUMENT ET DE SES PARAMÈTRES
		$mod_decree = new decree($dbcon, null, null, $mod_decree_id);
		//print_r2($mod_decree);
		if ($mod_decree_id != NULL)
		{
			$mod_select_decree = $mod_decree->getDecree();
			// USER AUTORISÉ ?
			if ($user->hasAccessDecree($mod_select_decree))
			{
				$mod_decree_fields = $mod_decree->getFields();
				$access = true;
				?>
				<h2>Modification d'un document</h2>
				<?php
				//print_r2($mod_decree_fields);
			}
			else
			{
				elog("Utilisateur non autorisé à modifier le document.");
				$access = false;
				unset($mod_decree);
				unset($mod_select_decree);
				$mode = 'create';
			}
		}
		else
		{
			elog ("Erreur de paramètres : id $mod_decree_id.");
			$access = false;
			unset($mod_decree);
			$mode = 'create';
		}
	}
	else
	{ ?>
		<h2>Nouveau document</h2>
	<?php } ?>
		<div class="gauche">
		<?php if (sizeof($listModels) == 0 ) { ?>
			Vous n'avez accès à aucun modèle de document. <br>
		<?php } else { ?>
		<form class ="form-zorro" name="formselectdecree" action="create_decree.php" method="post">

		<input type="hidden" name='userid' value='<?php echo $userid;?>'>
		<select style="width:26em" name="selectarrete" id="selectarrete" onchange="this.form.submit()">
				<?php
				if (!isset($post_selectarrete)) { ?>
				<option value="" selected="selected">&nbsp;</option>
				<?php } else { ?>
					<option value="">&nbsp;</option>
				<?php }
				$type = 0;
				foreach ($listModels as $model) {
					$color = "";
					if ($model['active'] == 'N')
					{
						$color = "class='inactive'";
					}
					if ($model['iddecree_type'] != $type) {
						if ($type != 0) { ?>
							</optgroup>
						<?php } $type = $model['iddecree_type']; ?>
						<optgroup label="<?php echo $model['namedecree_type'];?>">
					<?php } if ((isset($post_selectarrete) && $post_selectarrete == $model['idmodel']) || (isset($mod_select_decree) && $access && $mod_select_decree['idmodel'] == $model['idmodel'])) { ?>
							<option value="<?php echo $model['idmodel'];?>" selected="selected" <?php echo $color;?>><?php echo $model['name'];?></option>
							<?php } else { ?>
							<option value="<?php echo $model['idmodel'];?>" <?php echo $color;?>><?php echo $model['name'];?></option>
				<?php } } ?>
				</optgroup>
		</select>
		</form>
		</div>
		<?php } ?>
		<?php if (isset($post_selectarrete) && $post_selectarrete != '' || (isset($mod_select_decree) && $access))
			{
				$selectarrete = isset($mod_select_decree) ? $mod_select_decree['idmodel'] : $post_selectarrete;
				$modelselected = new model($dbcon, $selectarrete);
				$urlselected = $modelselected->getfile();
			?>
			<!-- <h2>Paramétrage du document</h2> -->
			<?php $modelfields = $modelselected->getModelFields();
			?>
			<form name='find_person' method='post' action='create_decree.php'>
			<div class="gauche">
			<input type="hidden" name='userid' value='<?php echo $userid;?>'>
			<input type="hidden" name='selectarrete' value='<?php echo isset($post_selectarrete) ? $post_selectarrete : $mod_select_decree['idmodel'];?>'>
			<?php foreach ($modelfields as $modelfield)
			{
				//if ($modelfield['auto'] != 'O')
				//	echo $modelfield['web_name']." : ";//." (".$modelfield['datatype'].") nombre d'occurrences : ".$modelfield['number'];
				$hidden = '';
				if ($modelfield['lib_section'] != NULL)
				{
					if (isset($hasSection))
					{ ?>
						</div>
					<?php }
					$hasSection = true;?>
					<div class="section">
					<h3><?php echo $modelfield['lib_section']; ?></h3>
				<?php }
				if ($modelfield['linkedto'] != NULL)
				{
					if (!(isset($mod_decree_fields) && key_exists($modelfield['linkedto'], $mod_decree_fields)))
					{
						$hidden = "name= 'linked_".$ref->getModelFieldName($modelfield['linkedto'])."' style='display:none;'";
					}
				}?>
				<div id='<?php echo $modelfield['name'].'_div';?>' <?php echo $hidden;?>>
				<?php if ($modelfield['auto'] != 'O' && $modelfield['number'] != '0' && $modelfield['idfield_type'] != 10)
				{?>
					<label><?php echo $modelfield['web_name'];?></label>
				<?php } elseif ($modelfield['auto_value'] !== NULL && $modelfield['auto_value'] != '' && $modelfield['number'] != '0') { ?>
					<label><?php echo $modelfield['web_name'];?></label> <?php echo ($modelfield['datatype'] == 'group') ? $ldap->getStructureName($modelfield['auto_value']) : $modelfield['auto_value'];?>
					<input type="hidden" id='<?php echo $modelfield['name'].'1';?>' name='<?php echo $modelfield['name'].'1';?>' value="<?php echo $modelfield['auto_value'];?>">
				<?php } ?>
				<input type="hidden" id='<?php echo $modelfield['name'].'_number';?>' value=1>
				<?php
				switch ($modelfield['number']) {
					case '+': $nb_field = "1";
						?>

						<?php break;

					default: $nb_field = $modelfield['number'];
							;?>

						<?php break;
				}
				for ($i=1; $i <= $nb_field; $i++)
				{
					if ($modelfield['auto'] == 'O')
					{
						if ($modelfield['auto_value'] !== NULL && $modelfield['auto_value'] == '') { ?>
							<label><?php echo $modelfield['web_name'];?></label><!-- Automatique -->
					<?php }
					}
					else {
						switch ($modelfield['datatype']) {
							case 'user':
									findPerson($modelfield['name'],$i);
									if (isset($mod_decree_fields) && key_exists($modelfield['idmodel_field'], $mod_decree_fields))
									{
										echo "<script>document.getElementById('".$modelfield['name']."1').value = '".$mod_decree_fields[$modelfield['idmodel_field']][0]['value']."';</script>";
										echo "<script>document.getElementById('".$modelfield['name']."1').nextSibling.innerText = '".$mod_decree_fields[$modelfield['idmodel_field']][0]['value']."';</script>";
									}
									break;
							case 'group':
									findGroup($modelfield['name'],$i);
									if (isset($mod_decree_fields) && key_exists($modelfield['idmodel_field'], $mod_decree_fields))
									{
										$structurename = $ldap->getStructureInfos($mod_decree_fields[$modelfield['idmodel_field']][0]['value'])['superGroups'][$mod_decree_fields[$modelfield['idmodel_field']][0]['value']]['name'];?>
										<script>document.getElementById('<?php echo $modelfield['name'];?>1_ref').value = "<?php echo $structurename;?>";</script>
										<script>document.getElementById('<?php echo $modelfield['name'];?>1').value = "<?php echo $mod_decree_fields[$modelfield['idmodel_field']][0]['value']; ?>";</script>
									<?php
									}
									elseif (isset($_SESSION['description']) && isset($_SESSION['supannentiteaffectation']))
									{ ?>
										<script>document.getElementById('<?php echo $modelfield['name'];?>1_ref').value = "<?php echo $_SESSION['description'];?>";</script>
										<script>document.getElementById('<?php echo $modelfield['name'];?>1').value = "structures-<?php echo $_SESSION['supannentiteaffectation'];?>";</script>
										<script>majComposante(document.getElementById('<?php echo $modelfield['name'];?>1'));</script>
									<?php }
									else
									{
										elog('pas de valeur pour la structure référente.');
									}
									break;
							case 'student':
									findStudent($modelfield['name'],$i);
									if (isset($mod_decree_fields) && key_exists($modelfield['idmodel_field'], $mod_decree_fields))
									{
										$nometu = $ldap->getEtuInfos($mod_decree_fields[$modelfield['idmodel_field']][0]['value'])['displayname'];?>
										<script>document.getElementById('<?php echo $modelfield['name'];?>1_ref').value = "<?php echo $nometu;?>";</script>
										<script>document.getElementById('<?php echo $modelfield['name'];?>1').value = "<?php echo $mod_decree_fields[$modelfield['idmodel_field']][0]['value']; ?>";</script>
										<script>majEtudiant(document.getElementById('<?php echo $modelfield['name'];?>1'));</script>
									<?php
									}
									break;
							case 'year':
								$defaultyear = (isset($mod_year)) ? date('Y', mktime(0,0,0,1,1,$mod_year)): date('Y'); ?>
								<select style="width:26em" name="<?php echo $modelfield['name'].$i;?>" id="<?php echo $modelfield['name'].$i;?>">
									<!-- <option value="<?php echo $defaultyear - 1;?>"><?php echo $defaultyear - 1;?></option> -->
									<option value="<?php echo $defaultyear;?>" selected="selected"><?php echo $defaultyear;?></option>
									<!-- <option value="<?php echo $defaultyear + 1;?>"><?php echo $defaultyear + 1;?></option> -->
								</select>
								<?php break;
							case 'query':
								if ($rdbApo == false)
								{
									$message .= "<p class='alerte alerte-warning'>La connexion à Apogée est interrompue. Veuillez nous excuser pour la gêne occasionnée. </p>";
								}
								else
								{
									// récupérer et exécuter la requête
									$query = $modelselected->getQueryField($modelfield['idfield_type']);
									$result = $ref->executeQuery($query);
									// liste déroulante
									if ($modelfield['idfield_type'] == 3) { ?>
										<select style="width:26em" name="<?php echo $modelfield['name'].$i;?>" id="<?php echo $modelfield['name'].$i;?>" onchange="majMention2(this);majSpecialite(this);">
										</select>
										<?php if (isset($mod_decree_fields) && array_key_exists($modelfield['idmodel_field'], $mod_decree_fields))
										{ ?>
											<script>majMention(document.getElementById("<?php echo $modelfield['name'];?>1"), "<?php echo $mod_decree_id; ?>");</script>
										<?php } ?>
									<?php }
									elseif ($modelfield['idfield_type'] == 2)
									{ ?>
										<select style="width:26em" name="<?php echo $modelfield['name'].$i;?>" id="<?php echo $modelfield['name'].$i;?>" onchange="majMention(this);">
										</select>
										<?php if (isset($mod_decree_fields) && array_key_exists($modelfield['idmodel_field'], $mod_decree_fields))
										{ ?>
											<script>majDomaine(document.getElementById("<?php echo $modelfield['name'];?>1"), "<?php echo $mod_decree_id; ?>");</script>
										<?php } ?>
									<?php
									}
									elseif ($modelfield['idfield_type'] == 106)
									{ ?>
										<select style="width:26em" name="<?php echo $modelfield['name'].$i;?>" id="<?php echo $modelfield['name'].$i;?>"">
										</select>
										<?php if (isset($mod_decree_fields) && array_key_exists($modelfield['idmodel_field'], $mod_decree_fields))
										{ ?>
											<script>majMention2(document.getElementById("<?php echo $modelfield['name'];?>1"), "<?php echo $mod_decree_id; ?>");</script>
										<?php } ?>
									<?php
									}
									elseif ($modelfield['idfield_type'] == 8)
									{ ?>
										<select style="width:26em" name="<?php echo $modelfield['name'].$i;?>" id="<?php echo $modelfield['name'].$i;?>">
										</select>
									<?php
									}
									else
									{ ?>
										<select style="width:26em" name="<?php echo $modelfield['name'].$i;?>" id="<?php echo $modelfield['name'].$i;?>" onchange="activeLinked('<?php echo $modelfield['name'];?>');">
											<option value="">&nbsp;</option>
										<?php foreach($result as $value)
											{
												if (isset($mod_decree_fields) && array_key_exists($modelfield['idmodel_field'], $mod_decree_fields) && $mod_decree_fields[$modelfield['idmodel_field']][$i-1]['value'] == $value['value'])
												{?>
													<option value="<?php echo $value['value'];?>" selected="selected"><?php echo $value['value'];?></option>
												<?php }
												else
												{ ?>
													<option value="<?php echo $value['value'];?>"><?php echo $value['value'];?></option>
												<?php }
											} ?>
										</select>
									<?php
									}
								}
								break;
							case 'list':
								$listFields = $modelselected->getListField($modelfield['idfield_type']);
								$listSelected = array();
								if (isset($mod_decree_fields) && array_key_exists($modelfield['idmodel_field'], $mod_decree_fields))
								{
									$listSelected = array_column($mod_decree_fields[$modelfield['idmodel_field']],'value');
								}
								if (sizeof($listFields) > 0)
								{
									$alerte = array(); ?>
									<select style="width:26em" name="<?php echo $modelfield['name'].$i;?>" id="<?php echo $modelfield['name'].$i;?>" onchange="activeLinked('<?php echo $modelfield['name'];?>');">
										<option value="">&nbsp;</option>
									<?php foreach($listFields as $value)
									{
										if (isset($mod_decree_fields) && array_key_exists($modelfield['idmodel_field'], $mod_decree_fields) && $mod_decree_fields[$modelfield['idmodel_field']][$i-1]['value'] == $value['value'])
										{
											if ($value['tem_active'] == 'N')
											{
												$alerte[$value['value']] = true;
											} ?>
											<option value="<?php echo $value['value'];?>" selected="selected"><?php echo $value['value'];?></option>
										<?php }
										elseif ($value['tem_active'] == 'N')
												{
													if (in_array($value['value'],$listSelected))
													{
														$alerte[$value['value']] = true; ?>
														<option value="<?php echo $value['value'];?>"><?php echo $value['value']." (obsolète)";?></option>
													<?php }
										}
										else
										{ ?>
											<option value="<?php echo $value['value'];?>"><?php echo $value['value'];?></option>
										<?php }
									} ?>
									</select>
								<?php }
								break;
							case 'checkbox':
							case 'checkbox2':
								if (isset($mod_decree_fields) && array_key_exists($modelfield['idmodel_field'], $mod_decree_fields))
								{
									$selected = 'checked';
								}
								else
								{
									$selected = '';
								}
								?>
								<input type="checkbox" id="<?php echo $modelfield['name'].$i;?>" name="<?php echo $modelfield['name'].$i;?>" value="yes" <?php echo $selected;?> onchange="activeLinked('<?php echo $modelfield['name'];?>');">
								<?php break;
							case 'numero':
								$value = (isset($_POST[$modelfield['name'].$i])) ? "value='".$_POST[$modelfield['name'].$i]."'" : '';
								$value = (isset($mod_decree_fields) && array_key_exists($modelfield['idmodel_field'], $mod_decree_fields)) ? "value='".$mod_decree_fields[$modelfield['idmodel_field']][$i-1]['value']."'" : '';?>
								<input type='text' id='<?php echo $modelfield['name'].$i;?>' name='<?php echo $modelfield['name'].$i;?>' <?php echo $value;?> onchange="activeLinked('<?php echo $modelfield['name'];?>');">
								<?php break;
							case 'date':
								if(isset($mod_decree_fields) && array_key_exists($modelfield['idmodel_field'], $mod_decree_fields))
								{ ?>
									<input class="calendrier" type="date" name='<?php echo $modelfield['name'].$i;?>' id='<?php echo $modelfield['name'].$i;?>' size=10 value="<?php echo $mod_decree_fields[$modelfield['idmodel_field']][$i-1]['value'];?>" onchange="activeLinked('<?php echo $modelfield['name'];?>');">
								<?php }
								else
								{ ?>
									<input class="calendrier" type="date" name='<?php echo $modelfield['name'].$i;?>' id='<?php echo $modelfield['name'].$i;?>' size=10 value="<?php echo date('Y-m-d');?>" onchange="activeLinked('<?php echo $modelfield['name'];?>');">
								<?php }
								break;
							case 'textarea':
								$value = (isset($_POST[$modelfield['name'].$i])) ? $_POST[$modelfield['name'].$i] : '';
								$value = (isset($mod_decree_fields) && array_key_exists($modelfield['idmodel_field'], $mod_decree_fields)) ? $mod_decree_fields[$modelfield['idmodel_field']][$i-1]['value'] : '';?>
								<textarea rows=1 cols=50 id='<?php echo $modelfield['name'].$i;?>' name='<?php echo $modelfield['name'].$i;?>' value="<?php echo $value;?>" onchange="activeLinked('<?php echo $modelfield['name'];?>');"><?php echo $value;?></textarea>
								<?php break;
							case 'object':
								// Afficher liste déroulante objets promo
								$obj_decree = array();
								if (isset($mod_decree_fields) && key_exists($modelfield['idmodel_field'], $mod_decree_fields) && sizeof($mod_decree_fields[$modelfield['idmodel_field']]) > 0)
								{
									$obj_decree_id = array_column($mod_decree_fields[$modelfield['idmodel_field']], 'value', 'value');
									foreach ($obj_decree_id as $id => $o)
									{
										$obj_decree[] = $ref->getObjectPricesById($id);
									}
									$obj_decree = array_column($obj_decree, null, 'idobject_type');
								}
								$listobjects = $ref->getObjectsList();
								if (sizeof($listobjects) > 0)
								{ ?>
									<select style="width:26em" name="<?php echo $modelfield['name'].$i;?>" id="<?php echo $modelfield['name'].$i;?>" onchange="activeLinked('<?php echo $modelfield['name'];?>');">
										<option value="">&nbsp;</option>
									<?php foreach($listobjects as $value)
									{
										if (array_key_exists($value['idobject_type'], $obj_decree))
										{?>
											<option value="<?php echo $value['idobject_type'];?>" id="<?php echo "option".$value['idobject_type'];?>" hidden><?php echo $value['name'];?></option>
										<?php } else { ?>
											<option value="<?php echo $value['idobject_type'];?>" id="<?php echo "option".$value['idobject_type'];?>"><?php echo $value['name'];?></option>
										<?php }
									} ?>
									</select>
								<?php }
								break;
							case 'room':
								// Afficher liste déroulante salles
								$salle_decree = array();
								if (isset($mod_decree_fields) && key_exists($modelfield['idmodel_field'], $mod_decree_fields) && sizeof($mod_decree_fields[$modelfield['idmodel_field']]) > 0)
								{
									$salle_decree_id = array_column($mod_decree_fields[$modelfield['idmodel_field']], 'value', 'value');
									foreach ($salle_decree_id as $id => $s)
									{
										$salle_decree[] = $ref->getRoomPricesById($id);
									}
									$salle_decree = array_column($salle_decree, null, 'idroom_type');
								}
								$listsalles = $ref->getRoomsList();
								if (sizeof($listsalles) > 0)
								{ ?>
									<select style="width:26em" name="<?php echo $modelfield['name'].$i;?>" id="<?php echo $modelfield['name'].$i;?>" onchange="activeLinked('<?php echo $modelfield['name'];?>');">
										<option value="">&nbsp;</option>
									<?php foreach($listsalles as $value)
									{
										if (array_key_exists($value['idroom_type'], $salle_decree))
										{?>
											<option value="<?php echo $value['idroom_type'];?>" id="<?php echo "option".$value['idroom_type'];?>" hidden><?php echo $value['centre'].' - '.$value['name'];?></option>
										<?php } else { ?>
											<option value="<?php echo $value['idroom_type'];?>" id="<?php echo "option".$value['idroom_type'];?>"><?php echo $value['centre'].' - '.$value['name'];?></option>
										<?php }
									} ?>
									</select>
								<?php }
								break;
							default:
								if ($modelfield['idfield_type'] == 10) {
									if (isset($mod_decree_fields) && array_key_exists($modelfield['idmodel_field'], $mod_decree_fields) && array_key_exists($i-1, $mod_decree_fields[$modelfield['idmodel_field']])) {
										$composante = $ref->executeQuery(array('schema'=>'APOGEE',
												'query' => "SELECT cmp.cod_cmp, cmp.lib_web_cmp FROM composante cmp WHERE cmp.tem_en_sve_cmp = 'O' AND cmp.cod_cmp = '".$mod_decree_fields[$modelfield['idmodel_field']][$i-1]['value']."'"));
										if (is_array($composante) && array_key_exists(0, $composante))
										{
											$composante = $composante[0];
											$value = $composante['value'];
										}
										else
										{
											$composante = array('code' => '');
											$value = "";
										}?>
										<input type='hidden' id='<?php echo $modelfield['name'].$i;?>' name='<?php echo $modelfield['name'].$i;?>' value="<?php echo $composante['code'];?>" onchange="activeLinked('<?php echo $modelfield['name'];?>');">
										<input type='hidden' id='affichecomposante' value="<?php echo $value;?>" readonly>
								<?php }
									else
									{ ?>
										<input type='hidden' id='<?php echo $modelfield['name'].$i;?>' name='<?php echo $modelfield['name'].$i;?>' onchange="activeLinked('<?php echo $modelfield['name'];?>');">
										<input type='hidden' id='affichecomposante' value="" readonly>
									<?php }
								}
								else
								{
									$value = (isset($_POST[$modelfield['name'].$i])) ? $_POST[$modelfield['name'].$i] : '';
									$value = (isset($mod_decree_fields) && array_key_exists($modelfield['idmodel_field'], $mod_decree_fields)) ? $mod_decree_fields[$modelfield['idmodel_field']][$i-1]['value'] : '';?>
								<input type='text' id='<?php echo $modelfield['name'].$i;?>' name='<?php echo $modelfield['name'].$i;?>' value="<?php echo $value;?>" onchange="activeLinked('<?php echo $modelfield['name'];?>');">
							<?php } break;
						}
					}
				}
				if ($modelfield['number'] == '+')
				{
					if ($modelfield['datatype'] == 'object')
					{?>
						<button onclick="return ajouterObjet('<?php echo $modelfield['name'];?>');">+</button>
						<br><br>
						<?php
							if (sizeof($listobjects) > 0)
							{ ?>
								<table class="tableauobjet">
									<tr>
										<th class="enteteobjet" rowspan="2" colspan="2">Objet Promotionnel</th>
										<th class="enteteobjet" colspan="2">Prix Public (HT)</th>
										<th class="enteteobjet" colspan="2">Prix Étudiant / Personnel (HT)</th>
									</tr>
									<tr>
										<th>Ancien</th>
										<th>Nouveau</th>
										<th>Ancien</th>
										<th>Nouveau</th>
									</tr>
								<?php foreach($listobjects as $value)
								{
									$prix = $ref->getObjectTypePrices($value['idobject_type']);
									if (array_key_exists($value['idobject_type'], $obj_decree))
									{
										if ($obj_decree[$value['idobject_type']]['old_tarif_public'] != $prix['new_tarif_public'] || $obj_decree[$value['idobject_type']]['old_tarif_etu_pers'] != $prix['new_tarif_etu_pers'])
										{
											$message .= "<p class='alerte alerte-danger'>Attention, les anciens tarifs de l'objet : \"".$value['name']."\" enregistrés pour cet arrêté diffèrent du nouveau tarif en cours de validité. Veuillez enregistrer le document avant d'envoyer l'arrêté à la signature.</p>";
											$obj_decree[$value['idobject_type']]['old_tarif_public'] = $prix['new_tarif_public'];
											$obj_decree[$value['idobject_type']]['old_tarif_etu_pers'] = $prix['new_tarif_etu_pers'];
										}?>
										<tr id="<?php echo 'objet'.$value['idobject_type'];?>">
											<td class='buttonsuppr'><button onclick="return supprimerObjet('<?php echo $value['idobject_type'];?>');">-</button></td>
											<td class='intitule'><?php echo $value['name'];?></td>
											<td class='tarif'><input type="text" id="<?php echo 'anctarifpub'.$value['idobject_type'];?>" name="<?php echo 'anctarifpub'.$value['idobject_type'];?>" value="<?php echo $obj_decree[$value['idobject_type']]['old_tarif_public'];?>" readonly></td>
											<td class='tarif'><input type="text" id="<?php echo 'newtarifpub'.$value['idobject_type'];?>" name="<?php echo 'newtarifpub'.$value['idobject_type'];?>" value="<?php echo $obj_decree[$value['idobject_type']]['new_tarif_public'];?>" oninput="document.getElementById('<?php echo 'newtarifep'.$value['idobject_type'];?>').value = this.value * 0.9; return false;"></td>
											<td class='tarif'><input type="text" id="<?php echo 'anctarifep'.$value['idobject_type'];?>" name="<?php echo 'anctarifep'.$value['idobject_type'];?>" value="<?php echo $obj_decree[$value['idobject_type']]['old_tarif_etu_pers'];?>" readonly></td>
											<td class='tarif'><input type="text" id="<?php echo 'newtarifep'.$value['idobject_type'];?>" name="<?php echo 'newtarifep'.$value['idobject_type'];?>" value="<?php echo $obj_decree[$value['idobject_type']]['new_tarif_etu_pers'];?>"></td>
										</tr>
									<?php
									}
									else
									{
									?>
										<tr id="<?php echo 'objet'.$value['idobject_type'];?>" hidden>
											<td class='buttonsuppr'><button onclick="return supprimerObjet('<?php echo $value['idobject_type'];?>');">-</button></td>
											<td class='intitule'><?php echo $value['name'];?></td>
											<td class='tarif'><input type="text" id="<?php echo 'anctarifpub'.$value['idobject_type'];?>" name="<?php echo 'anctarifpub'.$value['idobject_type'];?>" value="<?php echo $prix['new_tarif_public'];?>" readonly></td>
											<td class='tarif'><input type="text" id="<?php echo 'newtarifpub'.$value['idobject_type'];?>" name="<?php echo 'newtarifpub'.$value['idobject_type'];?>" value="" oninput="document.getElementById('<?php echo 'newtarifep'.$value['idobject_type'];?>').value = this.value * 0.9; return false;"></td>
											<td class='tarif'><input type="text" id="<?php echo 'anctarifep'.$value['idobject_type'];?>" name="<?php echo 'anctarifep'.$value['idobject_type'];?>" value="<?php echo $prix['new_tarif_etu_pers'];?>" readonly></td>
											<td class='tarif'><input type="text" id="<?php echo 'newtarifep'.$value['idobject_type'];?>" name="<?php echo 'newtarifep'.$value['idobject_type'];?>" value=""></td>
										</tr>
								<?php }
								} ?>
								</table>
							<?php }

					}
					elseif ($modelfield['datatype'] == 'room')
					{?>
						<button onclick="return ajouterSalle('<?php echo $modelfield['name'];?>');">+</button>
						<br><br>
						<?php
						if (sizeof($listsalles) > 0)
						{ ?>
							<table class="tableausalle">
								<tr>
									<th class="entetesalle" rowspan="2" colspan="2">Salle</th>
									<th class="entetesalle" colspan="2">Tarif horaire</th>
									<th class="entetesalle" colspan="2">Tarif 1/2 journée</th>
									<th class="entetesalle" colspan="2">Tarif journée</th>
								</tr>
								<tr>
									<th>Ancien</th>
									<th>Nouveau</th>
									<th>Ancien</th>
									<th>Nouveau</th>
									<th>Ancien</th>
									<th>Nouveau</th>
								</tr>
							<?php foreach($listsalles as $value)
							{
								$prix = $ref->getRoomTypePrices($value['idroom_type']);
								if (array_key_exists($value['idroom_type'], $salle_decree))
								{
									if ($salle_decree[$value['idroom_type']]['old_tarif_heure'] != $prix['new_tarif_heure'] || $salle_decree[$value['idroom_type']]['old_tarif_demi'] != $prix['new_tarif_demi'] || $salle_decree[$value['idroom_type']]['old_tarif_jour'] != $prix['new_tarif_jour'])
									{
										$message .= "<p class='alerte alerte-danger'>Attention, les anciens tarifs de la salle : \"".$value['name']."\" enregistrés pour cet arrêté diffèrent du nouveau tarif en cours de validité. Veuillez enregistrer le document avant d'envoyer l'arrêté à la signature.</p>";
										$salle_decree[$value['idroom_type']]['old_tarif_heure'] = $prix['new_tarif_heure'];
										$salle_decree[$value['idroom_type']]['old_tarif_demi'] = $prix['new_tarif_demi'];
										$salle_decree[$value['idroom_type']]['old_tarif_jour'] = $prix['new_tarif_jour'];
									}?>
									<tr id="<?php echo 'salle'.$value['idroom_type'];?>">
										<td class='buttonsuppr'><button onclick="return supprimerSalle('<?php echo $value['idroom_type'];?>');">-</button></td>
										<td class='intitule'><?php echo $value['centre'].' - '.$value['name'];?></td>
										<td class='tarif'><input type="text" id="<?php echo 'anctarifheure'.$value['idroom_type'];?>" name="<?php echo 'anctarifheure'.$value['idroom_type'];?>" value="<?php echo $salle_decree[$value['idroom_type']]['old_tarif_heure'];?>" readonly></td>
										<td class='tarif'><input type="text" id="<?php echo 'newtarifheure'.$value['idroom_type'];?>" name="<?php echo 'newtarifheure'.$value['idroom_type'];?>" value="<?php echo $salle_decree[$value['idroom_type']]['new_tarif_heure'];?>"></td>
										<td class='tarif'><input type="text" id="<?php echo 'anctarifdemi'.$value['idroom_type'];?>" name="<?php echo 'anctarifdemi'.$value['idroom_type'];?>" value="<?php echo $salle_decree[$value['idroom_type']]['old_tarif_demi'];?>" readonly></td>
										<td class='tarif'><input type="text" id="<?php echo 'newtarifdemi'.$value['idroom_type'];?>" name="<?php echo 'newtarifdemi'.$value['idroom_type'];?>" value="<?php echo $salle_decree[$value['idroom_type']]['new_tarif_demi'];?>"></td>
										<td class='tarif'><input type="text" id="<?php echo 'anctarifjour'.$value['idroom_type'];?>" name="<?php echo 'anctarifjour'.$value['idroom_type'];?>" value="<?php echo $salle_decree[$value['idroom_type']]['old_tarif_jour'];?>" readonly></td>
										<td class='tarif'><input type="text" id="<?php echo 'newtarifjour'.$value['idroom_type'];?>" name="<?php echo 'newtarifjour'.$value['idroom_type'];?>" value="<?php echo $salle_decree[$value['idroom_type']]['new_tarif_jour'];?>"></td>
									</tr>
								<?php
								}
								else
								{
								?>
									<tr id="<?php echo 'salle'.$value['idroom_type'];?>" hidden>
										<td class='buttonsuppr'><button onclick="return supprimerSalle('<?php echo $value['idroom_type'];?>');">-</button></td>
										<td class='intitule'><?php echo $value['centre'].' - '.$value['name'];?></td>
										<td class='tarif'><input type="text" id="<?php echo 'anctarifheure'.$value['idroom_type'];?>" name="<?php echo 'anctarifheure'.$value['idroom_type'];?>" value="<?php echo $prix['new_tarif_heure'];?>" readonly></td>
										<td class='tarif'><input type="text" id="<?php echo 'newtarifheure'.$value['idroom_type'];?>" name="<?php echo 'newtarifheure'.$value['idroom_type'];?>" value=""></td>
										<td class='tarif'><input type="text" id="<?php echo 'anctarifdemi'.$value['idroom_type'];?>" name="<?php echo 'anctarifdemi'.$value['idroom_type'];?>" value="<?php echo $prix['new_tarif_demi'];?>" readonly></td>
										<td class='tarif'><input type="text" id="<?php echo 'newtarifdemi'.$value['idroom_type'];?>" name="<?php echo 'newtarifdemi'.$value['idroom_type'];?>" value=""></td>
										<td class='tarif'><input type="text" id="<?php echo 'anctarifjour'.$value['idroom_type'];?>" name="<?php echo 'anctarifjour'.$value['idroom_type'];?>" value="<?php echo $prix['new_tarif_jour'];?>" readonly></td>
										<td class='tarif'><input type="text" id="<?php echo 'newtarifjour'.$value['idroom_type'];?>" name="<?php echo 'newtarifjour'.$value['idroom_type'];?>" value=""></td>
									</tr>
							<?php }
							} ?>
							</table>
						<?php }
					}
					else
					{ ?>
					<button onclick="return ajouterValeur('<?php echo $modelfield['name'];?>');">+</button>
					<table id='<?php echo "table_".$modelfield['name'];?>' class="marge_table"></table>
					<br>
					<?php if (isset($mod_decree_fields) && key_exists($modelfield['idmodel_field'], $mod_decree_fields) && sizeof($mod_decree_fields[$modelfield['idmodel_field']]) > 1)
						{
							for($i = 1; $i < sizeof($mod_decree_fields[$modelfield['idmodel_field']]); $i++)
							{
								echo "<script>ajouterValeur('".$modelfield['name']."')</script>";
								echo "<script>document.getElementById('".$modelfield['name']."1').value = \"".$mod_decree_fields[$modelfield['idmodel_field']][$i]['value']."\";</script>";
								echo "<script>document.getElementById('".$modelfield['name']."1').nextSibling.innerText = \"".$mod_decree_fields[$modelfield['idmodel_field']][$i]['value']."\";</script>";
							}
						}
					}
					if (sizeof($alerte) > 0)
					{ ?>
						<p class='alerte alerte-danger'>Attention, 1 ou plusieurs option(s) sélectionnée(s) n'existe(nt) plus : <br>
							<?php foreach ($alerte as $key => $value) echo "¤ ".$key."<br>";?>
						</p>
					<?php }
				}
				?>
				</div>
			<?php }
			if (isset($hasSection))
			{ ?>
				</div>
			<?php }?>
			</div>
			<div class="droite">
			<?php if (isset($mod_year) && isset($mod_num))
			{ ?>
				<input type="hidden" id='mod_year' name='mod_year' value='<?php echo $mod_year;?>'>
				<input type="hidden" id='mod_num' name='mod_num' value='<?php echo $mod_num;?>'>
				<input type="hidden" id='mod_id' name='mod_id' value='<?php echo $mod_decree_id;?>'>
				<br>
				<?php // Contrôler l'état de la demande dans esignature
				if (isset($mod_decree))
				{ ?>
					<div id="aff_numero_div"><?php echo $mod_year.'-'.$mod_num;?>
					<?php if ($mod_decree->getModelId() == 27) {
						if ($mod_status == STATUT_HORS_ZORRO) { ?>
							<img src="img/valide_OK.svg" alt="Hors Zorro" title="Hors Zorro" width="40px">
							</div>
							<?php if ($mod_decree_active) { ?>
								<input type='submit' name='supprime' value='Supprimer' onclick="return confirm('Êtes-vous sûr de vouloir supprimer votre arrêté Hors Zorro ?')">
								<input type='submit' name='valide' value='Enregistrer'>
							<?php }
						}
						else
						{ ?>
								<img src="img/trash-alt-solid.svg" alt="Supprimé" title="Supprimé" width="40px">
							</div>
						<?php }

					}
					else
					{?>
						<?php switch ($mod_status) {
									case STATUT_BROUILLON : ?>
										<img src="img/file-signature-solid.svg" alt="Brouillon" title="Brouillon" width="40px">
									</div>
									<?php if ($mod_decree_active) { ?>
										<input type='submit' name='duplique' value='Dupliquer'>
										<input type='submit' name='supprime' value='Supprimer' onclick="return confirm('Êtes-vous sûr de vouloir supprimer votre brouillon ?')">
										<input type='submit' name='valide' value='Enregistrer'>
										<input type="submit" name='sign' onclick="return confirm('Envoyer à la signature ?')" value="Envoyer à la signature">
										<?php } break;
									case STATUT_EN_COURS : ?>
										<a href='<?php echo $mod_decree->getEsignUrl();?>' target='_blank'><img src="img/clock-solid.svg" alt="En cours de signature : <?php echo $mod_decree->getSignStep(); ?>" title="En cours de signature : <?php echo $mod_decree->getSignStep(); ?>" width="40px"></a>
									</div>
									<?php if ($mod_decree_active) { ?>
										<input type='submit' name='duplique' value='Dupliquer'>
										<input type='submit' name='supprime' value='Supprimer' onclick="return confirm('Êtes-vous sûr de vouloir supprimer la demande initiale ? La demande de signature sera également supprimée.')">
										<input type='submit' name='valide' value='Remplacer' onclick="return confirm('Êtes-vous sûr de vouloir remplacer la demande initiale ? La demande de signature sera également supprimée.')">
										<input type="submit" name='sign' onclick="return confirm('Envoyer à la signature ?')" value="Envoyer à la signature" disabled>
										<?php } break;
									case STATUT_VALIDE : ?>
										<a href='<?php echo $mod_decree->getEsignUrl();?>' target='_blank'><img src="img/valide_OK.svg" alt="Validé" title="Validé" width="40px"></a>
									</div>
									<?php if ($mod_decree_active) { ?>
										<input type='submit' name='duplique' value='Dupliquer'>
										<input type='submit' name='supprime' value='Supprimer' disabled>
										<input type='submit' name='valide' value='Remplacer' disabled>
										<input type="submit" name='sign' onclick="return confirm('Envoyer à la signature ?')" value="Envoyer à la signature" disabled>
										<?php $iddecree_type = $modelselected->getDecreeType()['iddecree_type'];
										if ($iddecree_type == '1' || $iddecree_type == '2') { ?>
											<!--<input type="submit" name='modificatif' value="Arrêté modificatif">-->
										<?php } ?>
										<?php } break;
									case STATUT_REFUSE : $motif = $mod_decree->getRefuseComment();?>
										<a href='<?php echo $mod_decree->getEsignUrl();?>' target='_blank'><img src="img/non_refuse.svg" alt="Refusé" title="Refusé : <?php echo $motif;?>" width="40px"></a>
									</div>
									<?php if ($mod_decree_active) { ?>
										<input type='submit' name='duplique' value='Dupliquer' disabled>
										<input type='submit' name='supprime' value='Supprimer' onclick="return confirm('Êtes-vous sûr de vouloir supprimer la demande initiale ? La demande de signature sera également supprimée.')">
										<input type='submit' name='valide' value='Remplacer' onclick="return confirm('Êtes-vous sûr de vouloir remplacer la demande initiale ? La demande de signature sera également supprimée.')">
										<input type="submit" name='sign' onclick="return confirm('Envoyer à la signature ?')" value="Envoyer à la signature" disabled>
										<?php } break;
									case STATUT_ANNULE : ?>
										<img src="img/trash-alt-solid.svg" alt="Supprimé" title="Supprimé" width="40px">
									</div>
									<?php if ($mod_decree_active) { ?>
										<input type='submit' name='duplique' value='Dupliquer'>
										<input type='submit' name='supprime' value='Supprimer' disabled>
										<input type='submit' name='valide' value='Remplacer' disabled>
										<input type="submit" name='sign' onclick="return confirm('Envoyer à la signature ?')" value="Envoyer à la signature" disabled>
										<?php $histo_refus_before_delete = $mod_decree->getRefusedCommentOnDelete();
											if (sizeof($histo_refus_before_delete) > 0)
											{ ?>
												<p class='alerte alerte-info'>Le document a été refusé pour le motif suivant : <?php echo $histo_refus_before_delete['refus_comment'];?></p>
										<?php } ?>
										<?php } break;
									case STATUT_ERREUR : ?>
										<img src="img/erreur1.svg" alt="Document non trouvé sur eSignature" title="Document non trouvé sur eSignature" width="40px">
									</div>
									<?php if ($mod_decree_active) { ?>
										<input type='submit' name='duplique' value='Dupliquer'>
										<input type='submit' name='supprime' value='Supprimer' disabled>
										<input type='submit' name='valide' value='Remplacer' disabled>
										<input type="submit" name='sign' onclick="return confirm('Envoyer à la signature ?')" value="Envoyer à la signature" disabled>
										<?php } break;
									case STATUT_SUPPR_ESIGN : ?>
										<img src="img/trash-alt-solid.svg" alt="Document supprimé d'eSignature" title="Document supprimé d'eSignature" width="40px">
									</div>
									<?php if ($mod_decree_active) { ?>
										<input type='submit' name='duplique' value='Dupliquer'>
										<input type='submit' name='supprime' value='Supprimer' disabled>
										<input type='submit' name='valide' value='Remplacer' disabled>
										<input type="submit" name='sign' onclick="return confirm('Envoyer à la signature ?')" value="Envoyer à la signature" disabled>
										<?php } break;
									case STATUT_CORBEILLE : ?>
										<a href='<?php echo $mod_decree->getEsignUrl();?>' target='_blank'><img src="img/trash-alt-solid.svg" alt="Document dans la corbeille d'eSignature" title="Document dans la corbeille d'eSignature" width="40px"></a>
									</div>
									<?php if ($mod_decree_active) { ?>
										<input type='submit' name='duplique' value='Dupliquer'>
										<input type='submit' name='supprime' value='Supprimer' onclick="return confirm('Êtes-vous sûr de vouloir supprimer la demande initiale ? La demande de signature sera également supprimée.')">
										<input type='submit' name='valide' value='Remplacer' onclick="return confirm('Êtes-vous sûr de vouloir remplacer la demande initiale ? La demande de signature sera également supprimée.')">
										<input type="submit" name='sign' onclick="return confirm('Envoyer à la signature ?')" value="Envoyer à la signature" disabled>
										<?php } break;
									default : break;
						}
					}
					if (isset($message)) {
						echo $message;
					}
					$histo_refus = $mod_decree->getRefuseHisto();
					if (sizeof($histo_refus) > 0)
					{ ?>
						<p class='alerte alerte-info'>Le document remplace une demande refusée pour le motif suivant : <?php echo $histo_refus['refus_comment'];?></p>
					<?php }
					//echo $mod_decree->getExportPath();?>
					<br>
				<?php }
			} else {?>
			<br><input type='submit' name='valide' value='Enregistrer'><br>
			<?php
				if (isset($message)) {
					echo $message;
				}
			} ?>
			</div>
			</div>
			</form>
			<div id="contenu2">
			<?php
			if (isset($mod_decree))
			{
				$filename = PDF_PATH.$mod_decree->getFileName();
				//print_r2($filename);
				if (file_exists($filename))
				{
					$doc_pdf = fopen($filename, 'r');
					$contenu_pdf = fread($doc_pdf, filesize($filename));
					$encodage = base64_encode($contenu_pdf);
					?>
					<?php echo '<iframe src=data:application/pdf;base64,' . $encodage . ' width="100%" height="500px">';
					echo "</iframe>";?>

					<br><br>

				<?php }
				else {	?>
					<!-- <p> pas de document PDF.</p> -->
				<?php }
			}
			?>
			</div>
	<?php }
	elseif (isset($access))
	{ ?>
		<p class="alerte alerte-warning"> Vous n'avez pas accès à ce document. </p>
	<?php }
	} ?>

</div>

</body>
</html>


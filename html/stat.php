<?php
require_once ('CAS.php');
include './include/casconnection.php';
require_once ('./include/fonctions.php');
require_once ('./include/dbconnection.php');
require_once ('./class/reference.php');
require_once ('./class/user.php');
require_once ('./class/model.php');
require_once ('./class/decree.php');
require_once ('./class/ldap.php');

$ref = new reference($dbcon, $rdbApo);
$userid = $ref->getUserUid();
//echo $_SESSION['uid'];
if (is_null($userid) or ($userid == ""))
{
	elog("Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
	header('Location: index.php');
	exit();
}
		
// Récupération des modeles auxquels à accès l'utilisateur
$menuItem = 'menu_stat';
require ("include/menu.php");
$liste_comp = array_column($ref->getListComp(), 'value', 'code');
$liste_year = $ref->getCreationYears();
if (isset($_SESSION['phpCAS']) && array_key_exists('user', $_SESSION['phpCAS']))
{
	$userCAS = new user($dbcon, $_SESSION['phpCAS']['user']);
	if ($userCAS->isSuperAdmin(false) || $userCAS->isDaji() || $userCAS->isAdminModel())
	{
		$composante_selected = '';
		if (isset($_POST['selectcomp']) && $_POST['selectcomp'] != '')
		{
			$post_selectcomp = $_POST['selectcomp'];
			$ldap = new ldap();
			$composante_selected = "structures-".$ldap->getSupannCodeEntiteFromAPO($post_selectcomp);
		}
		if (isset($_POST['selectyear']) && $_POST['selectyear'] != '')
		{
			$post_selectyear = $_POST['selectyear'];
		}
		if (isset($_POST['selectarrete']) && $_POST['selectarrete'] != '')
		{
			$post_selectarrete = $_POST['selectarrete'];
			$model_selected = new model($dbcon, $post_selectarrete);
			$model_selected_infos = $model_selected->getModelInfo();
			$list_fields = $model_selected->getModelFields();
			$export_path = $model_selected->getExportPath();
			$modelworkflow = $model_selected->getModelWorkflow();
			$model_decrees = $userCAS->getDecreesBy(array('idmodel' => $model_selected->getid(), 'createyear' => $post_selectyear, 'composante' => $composante_selected), -1);
			if (isset($post_selectcomp) && $post_selectcomp != '')
			{
				$liste_to_do = $model_selected->getListDecreesToEditForComp($post_selectcomp);
			}
			else
			{
				$liste_to_do = $model_selected->getListDecreesToEditForComp();
			}
			$query_field = $model_selected->getLastQuery();
			$idfield_periode = ($post_selectarrete == 12) ? 104 : 7; // idfield_type de la période 7 ou 104 pour capacité
			$liste_edit = array();
			$corresp_period = array("semestre 1" => "P1", "1ère année" => "P1", "semestre 2" => "P2", "2ème année" => "P2", "Annuel" => "Annuel");
			$decree_made_by_periode = array("Annuel" => array(), "P1" => array(), "P2" => array());
			$decree_doublon = array();
			$nb_decree_made = 0;
			$periode_weight = array("Annuel" => 1, "P1" => 0.5, "P2" => 0.5);
			$liste_etp_to_do = array_map('htmlspecialchars',array_column($liste_to_do,'value'));
			$decree_made = array(STATUT_VALIDE => $decree_made_by_periode, STATUT_EN_COURS => $decree_made_by_periode, "Validation de la présidence" => $decree_made_by_periode, "Visa de la composante" => $decree_made_by_periode, STATUT_BROUILLON => $decree_made_by_periode);
			foreach($model_decrees as $mdecree)
			{
				$decree = new decree($dbcon, null, null, $mdecree['iddecree']);
				$query_value =  $decree->getFieldForFieldType($query_field);
				$periode_value =  $decree->getFieldForFieldType($idfield_periode);
				if (in_array($query_value, $liste_etp_to_do))
				{
					$liste_edit[$query_value][] = array('statut' => $decree->getStatusAff(), 'periode' => $periode_value);

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
									/*else
									{
										$gerer_doublon = true;
									}
									if ($gerer_doublon && !array_key_exists($query_value, $decree_doublon))
									{
										$decree_doublon[] = $query_value;
									}*/
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
									/*if ($gerer_doublon && !array_key_exists($query_value, $decree_doublon))
									{
										$decree_doublon[] = $query_value;
									}*/
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
										/*if (!array_key_exists($query_value, $decree_doublon))
										{
											$decree_doublon[] = $query_value;
										}*/
									}
									else
									{
										$decree_made[$status][$p][] = $query_value;
										$nb_decree_made += $periode_weight[$p];
									}
									break;
								case STATUT_EN_COURS :
									$liste_statuts_sup = array($status, STATUT_VALIDE);
									$liste_statuts_inf = array(STATUT_BROUILLON);
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
												$gerer_doublon = true;
											}
										}
									}
									if ($gerer_doublon)
									{
										/*if (!array_key_exists($query_value, $decree_doublon))
										{
											$decree_doublon[] = $query_value;
										}*/
									}
									else
									{
										$decree_made[$status][$p][] = $query_value;
										$nb_decree_made += $periode_weight[$p];
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
		}
		else
		{
		}
?>
<div id="contenu1">
	<h2> Statistiques </h2>
	<?php
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
			if ($infos['iddecree_type'] == 1 || $infos['iddecree_type'] == 2)
			{
				$model = new model($dbcon, $idmodel);
				$listModels[] = $model->getModelInfo();
			}
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
	}
	?>
	<?php if (sizeof($listModels) == 0 ) { ?>
		<div class="gauche">
		Vous n'avez accès à aucun modèle de document. <br>
		</div>
	<?php } else { ?>
		<div class="recherche">
		<form class ="form-zorro" name="formselectdecree" action="stat.php" method="post">
		<input type="hidden" name='userid' value='<?php echo $userid;?>'>
		<select name="selectyear" id="selectyear">
		<?php
			if (!isset($post_selectyear)) {
				$post_selectyear = date('Y');
			} 
			foreach ($liste_year as $year) {
				if ((isset($post_selectyear) && $post_selectyear == $year)) { ?>
					<option value="<?php echo $year;?>" selected="selected"><?php echo $year."/".($year+1);?></option>
				<?php } else { ?>
					<option value="<?php echo $year;?>"><?php echo $year."/".($year+1);?></option>
				<?php }
			} ?>
		</select>
		<select style="width:14em" name="selectcomp" id="selectcomp">
			<?php
			if (!isset($post_selectcomp)) { ?>
			<option value="" selected="selected">Composante (facultatif)</option>
			<?php } else { ?>
				<option value="">Composante (facultatif)</option>
			<?php }
			foreach ($liste_comp as $codcmp => $liccmp) {
				if (isset($post_selectcomp) && $post_selectcomp == $codcmp) { ?>
					<option value="<?php echo $codcmp;?>" selected="selected"><?php echo $liccmp;?></option>
				<?php } else { ?>
					<option value="<?php echo $codcmp;?>"><?php echo $liccmp;?></option>
				<?php }
			} ?>
		</select>
		<select style="width:26em" name="selectarrete" id="selectarrete">
		        <?php
		        if (!isset($post_selectarrete)) { ?>
		        <option value="" selected="selected">Modèle (obligatoire)</option>
		        <?php } else { ?>
		            <option value="">Modèle (obligatoire)</option>
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
					<?php } if ((isset($post_selectarrete) && $post_selectarrete == $model['idmodel'])) { ?>
						<option value="<?php echo $model['idmodel'];?>" selected="selected" <?php echo $color;?>><?php echo $model['name'];?></option>
					<?php } else { ?>
						<option value="<?php echo $model['idmodel'];?>" <?php echo $color;?>><?php echo $model['name'];?></option>
					<?php }
		        } ?>
				</optgroup>
		</select>
		<input type='submit' name='Soumettre' value="Soumettre">
		</form>
	</div>
	<div class="gauche">
	<?php } ?>
	<?php if (isset($model_selected_infos))
	{
		//print_r2($model_selected_infos);
		?>
		<div class="stat">
			<label class="labstat">Nombre d'arrêtés annuels attendus : </label><?php if ($query_field == NULL) { echo "1" ;} else { echo sizeof($liste_to_do);} ?><br>
			<label class="labstat" title="On ne compte qu'une seule fois les arrêtés créés pour une mention. Un arrêté semestriel est compté 0.5. Si plusieurs arrêtés créés pour une mention couvrent la même période, la priorité est donnée au statut le plus avancé puis à la période la plus longue.">Nombre d'arrêtés créés ❔ : </label><?php echo $nb_decree_made;?>
			<ul>
				<li><label class="labsubstat">Validé : </label><?php echo sizeof($decree_made[STATUT_VALIDE]["Annuel"]) + (sizeof($decree_made[STATUT_VALIDE]["P1"]) / 2) + (sizeof($decree_made[STATUT_VALIDE]["P2"]) / 2); ?></li>
				<ul>
					<li><label class="labsubsubstat">Annuel : </label><?php echo sizeof($decree_made[STATUT_VALIDE]["Annuel"]);?></li>
					<?php if ($post_selectarrete == 12) { ?>
						<li><label class="labsubsubstat">1ère année : </label><?php echo sizeof($decree_made[STATUT_VALIDE]["P1"])/2;?></li>
						<li><label class="labsubsubstat">2ème année : </label><?php echo sizeof($decree_made[STATUT_VALIDE]["P2"])/2;?></li>
					<?php } else { ?>
						<li><label class="labsubsubstat">Semestre 1 : </label><?php echo sizeof($decree_made[STATUT_VALIDE]["P1"])/2;?></li>
						<li><label class="labsubsubstat">Semestre 2 : </label><?php echo sizeof($decree_made[STATUT_VALIDE]["P2"])/2;?></li>
					<?php } ?>
				</ul>
				<li><label class="labsubstat">En cours de signature : </label><?php echo sizeof($decree_made[STATUT_EN_COURS]["Annuel"]) + (sizeof($decree_made[STATUT_EN_COURS]["P1"]) / 2) + (sizeof($decree_made[STATUT_EN_COURS]["P2"]) / 2); ?></li>
				<ul>
					<li><label class="labsubsubstat">Présidence : </label><?php echo sizeof($decree_made["Validation de la présidence"]["Annuel"]) + (sizeof($decree_made["Validation de la présidence"]["P1"]) / 2) + (sizeof($decree_made["Validation de la présidence"]["P2"]) / 2); ?></li>
					<li><label class="labsubsubstat">Composante : </label><?php echo sizeof($decree_made["Visa de la composante"]["Annuel"]) + (sizeof($decree_made["Visa de la composante"]["P1"]) / 2) + (sizeof($decree_made["Visa de la composante"]["P2"]) / 2); ?></li>
				</ul>
				<li><label class="labsubstat">Brouillon : </label><?php echo sizeof($decree_made[STATUT_BROUILLON]["Annuel"]) + (sizeof($decree_made[STATUT_BROUILLON]["P1"]) / 2) + (sizeof($decree_made[STATUT_BROUILLON]["P2"]) / 2); ?></li>
			</ul>
			<label class="labstat">Nombre d'arrêtés NON créés : </label><?php echo sizeof($liste_to_do) - $nb_decree_made;?><br>
			<label class="labstat" title="La période des arrêtés en doublon est affichée en orange dans le tableau ci-dessous.">Nombre d'arrêtés validés en doublon ❔ : </label><span class='warning_doublon'><?php echo sizeof($decree_doublon);?></span><br><br>
		</div>
		<?php if (sizeof($liste_to_do) > 0)
		{ ?>
			<div>
				<table class="tableausimple">
					<tr><th class="titresimple" colspan=5>Arrêtés à produire</th></tr>
					<tr>
						<th class="titresimple">Code</th>
						<th class="titresimple">Libellé</th>
						<th class="titresimple">Composante</th>
						<th class="titresimple">Période</th>
						<th class="titresimple">Statut</th>
						<th class="titresimple">Suivi eSignature</th>
					</tr>
				<?php foreach ($liste_to_do as $todo)
				{ ?>
					<tr>
						<?php 
						$valeur = htmlspecialchars($todo['value']);
						$decree_edited = array_key_exists($valeur, $liste_edit);
						$rowspan =  $decree_edited ? sizeof($liste_edit[$valeur]) : 1; ?>
						<td rowspan=<?php echo $rowspan;?> ><?php echo $todo['code'];?></td>
						<td rowspan=<?php echo $rowspan;?>s><?php echo $todo['value'];?></td>
						<td rowspan=<?php echo $rowspan;?>><?php echo $liste_comp[$todo['cmp']];?></td>
						<?php if ($decree_edited) 
						{ ?>
							<?php ?>
							<?php $periode = $liste_edit[$valeur][0]['periode'] == '' ? "Annuel" : $liste_edit[$valeur][0]['periode'];
							$periode_doublon = (array_key_exists($valeur, $decree_doublon) && in_array($corresp_period[$periode], $decree_doublon[$valeur])) ? "class='warning_doublon' title='doublon'" : "";?>
							<td <?php echo $periode_doublon; ?>><?php echo $periode; ?></td>
							<td class="<?php echo $liste_edit[$valeur][0]['statut']['class'];?>" title="<?php echo $liste_edit[$valeur][0]['statut']['title'];?>"><?php echo $liste_edit[$valeur][0]['statut']['contenu']; ?></td>
							<?php $pos = strpos($liste_edit[$valeur][0]['statut']['contenu'], "signrequests");
							if ($pos !== FALSE) { ?>
								<td><a href="<?php echo 'info_signature.php?esignatureid='.substr($liste_edit[$valeur][0]['statut']['contenu'], $pos + 13, strpos($liste_edit[$valeur][0]['statut']['contenu'], "target") -2 - $pos - 13); ?>">=></a></td>
							<?php } else { ?>
								<td></td>
							<?php } ?>
						<?php } else { ?>
							<td></td>
							<td></td>
							<td></td>
						<?php } ?>
					</tr>
					<?php for($i = 1; $i < $rowspan; $i++)
					{ ?>
						<tr>
							<?php $periode = $liste_edit[$valeur][$i]['periode'] == '' ? "Annuel" : $liste_edit[$valeur][$i]['periode'];
							$periode_doublon = (array_key_exists($valeur, $decree_doublon) && in_array($corresp_period[$periode], $decree_doublon[$valeur])) ? "class='warning_doublon' title='doublon'" : "";?>
							<td <?php echo $periode_doublon; ?>><?php echo $periode; ?></td>
							<td class="<?php echo $liste_edit[$valeur][$i]['statut']['class'];?>" title="<?php echo $liste_edit[$valeur][$i]['statut']['title'];?>"><?php echo $liste_edit[$valeur][$i]['statut']['contenu']; ?></td>
							<?php $pos = strpos($liste_edit[$valeur][$i]['statut']['contenu'], "signrequests");
							if ($pos !== FALSE) { ?>
								<td><a href="<?php echo 'info_signature.php?esignatureid='.substr($liste_edit[$valeur][$i]['statut']['contenu'], $pos + 13, strpos($liste_edit[$valeur][$i]['statut']['contenu'], "target") -2 - $pos - 13); ?>">=></a></td>
							<?php } else { ?>
								<td></td>
						<?php } ?>
						</tr>
					<?php } ?>
				<?php } ?>
				</table>
			</div>
		<?php } ?>
	</div>
</div>
	<?php } ?>
<?php } else { ?>
<div id="contenu1">
	<h2> Accès interdit </h2>
</div>
<?php } } else { ?>
<div id="contenu1">
	<h2> Accès interdit </h2>
</div>
<?php } ?>
</body>
</html>


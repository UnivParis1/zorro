<?php
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
$user = new user($dbcon, $userid);
$user_apo = $user->getStructureCodApo();

// Récupération des modeles auxquels à accès l'utilisateur
$menuItem = 'menu_stat';
require ("include/menu.php");
$liste_comp = array_column($ref->getListComp(), 'value', 'code');
$liste_year = $ref->getCreationYears();

// Récupération des modeles auxquels à accès l'utilisateur
$superadmin = false;
if ($user->isSuperAdmin() || $user->isDaji())
{
	// donner accès à tous les modèles
	$superadmin = true;
	$models = $ref->getListModel();
	foreach ($models as $idmodel => $infos)
	{
		if ($infos['iddecree_type'] == 1 || $infos['iddecree_type'] == 2 || $infos['iddecree_type'] == 5 || $infos['iddecree_type'] == 6)
		{
			$model = new model($dbcon, $idmodel);
			$listModels[] = $model->getModelInfo();
		}
	}
}
else
{
	$roles = $user->getGroupeRoles($_SESSION['groupes'], null, true); // roles actifs de l'utilisateur
	$listModels = array();
	foreach ($roles as $role)
	{
		$model = new model($dbcon, $role['idmodel']);
		$listModels[] = $model->getModelInfo();
	}
	$listModels = $ref->sortModel($listModels);
	if (!$user->isAdminModel())
	{
		if (array_key_exists($user_apo, $liste_comp))
		{
			$liste_comp = array($user_apo => $liste_comp[$user_apo]);
		}
		else
		{
			$liste_comp = array();
		}
	}
}

if (isset($_SESSION['phpCAS']) && array_key_exists('user', $_SESSION['phpCAS']))
{
	$userCAS = new user($dbcon, $_SESSION['phpCAS']['user']);
	$composante_selected = '';
	if (isset($_POST['selectcomp']) && $_POST['selectcomp'] != '')
	{
		$post_selectcomp = $_POST['selectcomp'];
		$ldap = new ldap();
		if (!array_key_exists($post_selectcomp, $liste_comp))
		{
			$post_selectcomp = key($liste_comp);
		}
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
		if ($userCAS->getUid() == $user->getUid())
		{
			$model_decrees = $userCAS->getDecreesBy(array('idmodel' => $model_selected->getid(), 'createyear' => $post_selectyear, 'composante' => $composante_selected, 'allcomp' => "TRUE"), -1, 0, -1, false, true);
		}
		else
		{
			$model_decrees = $user->getDecreesBy(array('idmodel' => $model_selected->getid(), 'createyear' => $post_selectyear, 'composante' => $composante_selected, 'allcomp' => "TRUE"), -1, 0, -1, false, true);
		}
		if (isset($post_selectcomp) && $post_selectcomp != '')
		{
			$composante = $post_selectcomp;
		}
		else
		{
			$composante = NULL;
		}
		if (isset($post_selectyear))
		{
			$stat = $model_selected->getStats($model_decrees, $composante, substr($post_selectyear, 0, 4));
		}
		else
		{
			$stat = $model_selected->getStats($model_decrees, $composante);
		}
		$corresp_period = array("semestre 1" => "P1", "1ère année" => "P1", "semestre 2" => "P2", "2ème année" => "P2", "Annuel" => "Annuel");
	}
	else
	{
	}
?>
<div id="contenu1">
	<h2> Statistiques </h2>
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
				$post_selectyear = COD_ANU.'-'.(COD_ANU+1);
			} 
			foreach ($liste_year as $year) {
				if ((isset($post_selectyear) && $post_selectyear == $year)) { ?>
					<option value="<?php echo $year;?>" selected="selected"><?php echo $year;?></option>
				<?php } else { ?>
					<option value="<?php echo $year;?>"><?php echo $year;?></option>
				<?php }
			} ?>
		</select>
		<select style="width:14em" name="selectcomp" id="selectcomp">
			<?php
				if (sizeof($liste_comp) ==  1)
				{
					$codcmp = key($liste_comp); $liccmp = $liste_comp[$codcmp];
					?>
					<option value="<?php echo $codcmp;?>" selected="selected"><?php echo $liccmp;?></option>
				<?php } else { 
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
					}
				}
			?>
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
		?>
		<div class="stat">
			<label class="labstat">Nombre d'arrêtés annuels attendus : </label><?php if ($stat["query_field"] == NULL) { echo "1" ;} else { echo sizeof($stat["liste_to_do"]);} ?><br>
			<label class="labstat" title="On ne compte qu'une seule fois les arrêtés créés pour une mention. Un arrêté semestriel est compté 0.5. Si plusieurs arrêtés créés pour une mention couvrent la même période, la priorité est donnée au statut le plus avancé puis à la période la plus longue.">Nombre d'arrêtés créés ❔ : </label><?php echo $stat["nb_decree_made"];?>
			<ul>
				<li><label class="labsubstat">Validé : </label><?php echo sizeof($stat["decree_made"][STATUT_VALIDE]["Annuel"]) + (sizeof($stat["decree_made"][STATUT_VALIDE]["P1"]) / 2) + (sizeof($stat["decree_made"][STATUT_VALIDE]["P2"]) / 2); ?></li>
				<ul>
					<li><label class="labsubsubstat">Annuel : </label><?php echo sizeof($stat["decree_made"][STATUT_VALIDE]["Annuel"]);?></li>
					<?php if ($post_selectarrete == 12) { ?>
						<li><label class="labsubsubstat">1ère année : </label><?php echo sizeof($stat["decree_made"][STATUT_VALIDE]["P1"])/2;?></li>
						<li><label class="labsubsubstat">2ème année : </label><?php echo sizeof($stat["decree_made"][STATUT_VALIDE]["P2"])/2;?></li>
					<?php } else { ?>
						<li><label class="labsubsubstat">Semestre 1 : </label><?php echo sizeof($stat["decree_made"][STATUT_VALIDE]["P1"])/2;?></li>
						<li><label class="labsubsubstat">Semestre 2 : </label><?php echo sizeof($stat["decree_made"][STATUT_VALIDE]["P2"])/2;?></li>
					<?php } ?>
				</ul>
				<li><label class="labsubstat">En cours de signature : </label><?php echo sizeof($stat["decree_made"][STATUT_EN_COURS]["Annuel"]) + (sizeof($stat["decree_made"][STATUT_EN_COURS]["P1"]) / 2) + (sizeof($stat["decree_made"][STATUT_EN_COURS]["P2"]) / 2); ?></li>
				<ul>
					<li><label class="labsubsubstat">Présidence : </label><?php echo sizeof($stat["decree_made"]["Validation de la présidence"]["Annuel"]) + (sizeof($stat["decree_made"]["Validation de la présidence"]["P1"]) / 2) + (sizeof($stat["decree_made"]["Validation de la présidence"]["P2"]) / 2); ?></li>
					<li><label class="labsubsubstat">Composante : </label><?php echo sizeof($stat["decree_made"]["Visa de la composante"]["Annuel"]) + (sizeof($stat["decree_made"]["Visa de la composante"]["P1"]) / 2) + (sizeof($stat["decree_made"]["Visa de la composante"]["P2"]) / 2); ?></li>
				</ul>
				<li><label class="labsubstat">Brouillon : </label><?php echo sizeof($stat["decree_made"][STATUT_BROUILLON]["Annuel"]) + (sizeof($stat["decree_made"][STATUT_BROUILLON]["P1"]) / 2) + (sizeof($stat["decree_made"][STATUT_BROUILLON]["P2"]) / 2); ?></li>
			</ul>
			<label class="labstat">Nombre d'arrêtés NON créés : </label><?php echo sizeof($stat["liste_to_do"]) - $stat["nb_decree_made"];?><br>
			<label class="labstat" title="La période des arrêtés en doublon est affichée en orange dans le tableau ci-dessous.">Nombre d'arrêtés validés en doublon ❔ : </label><span class='warning_doublon'><?php echo sizeof($stat["decree_doublon"]);?></span><br><br>
		</div>
		<?php if (sizeof($stat["liste_to_do"]) > 0)
		{ ?>
			<div>
				<table class="tableausimple">
					<tr><th class="titresimple" colspan=6>Arrêtés à produire</th></tr>
					<tr>
						<th class="titresimple">Code</th>
						<th class="titresimple">Libellé</th>
						<th class="titresimple">Composante</th>
						<th class="titresimple">Période</th>
						<th class="titresimple">Statut</th>
						<th class="titresimple">Suivi eSignature</th>
					</tr>
				<?php foreach ($stat["liste_to_do"] as $todo)
				{ ?>
					<tr>
						<?php 
						$valeur = htmlspecialchars($todo['value']);
						$decree_edited = array_key_exists($valeur, $stat["liste_edit"]);
						$rowspan =  $decree_edited ? sizeof($stat["liste_edit"][$valeur]) : 1; ?>
						<td rowspan=<?php echo $rowspan;?> ><?php echo $todo['code'];?></td>
						<td rowspan=<?php echo $rowspan;?>><?php echo $todo['value'];?></td>
						<td rowspan=<?php echo $rowspan;?>><?php echo $liste_comp[$todo['cmp']];?></td>
						<?php if ($decree_edited) 
						{ ?>
							<?php ?>
							<?php $periode = $stat["liste_edit"][$valeur][0]['periode'] == '' ? "Annuel" : $stat["liste_edit"][$valeur][0]['periode'];
							$periode_doublon = (array_key_exists($valeur, $stat["decree_doublon"]) && in_array($corresp_period[$periode], $stat["decree_doublon"][$valeur])) ? "class='warning_doublon' title='doublon'" : "";?>
							<td <?php echo $periode_doublon; ?>><?php echo $periode; ?></td>
							<td class="<?php echo $stat["liste_edit"][$valeur][0]['statut']['class'];?>" title="<?php echo $stat["liste_edit"][$valeur][0]['statut']['title'];?>"><?php echo $stat["liste_edit"][$valeur][0]['statut']['contenu']; ?></td>
							<?php $pos = strpos($stat["liste_edit"][$valeur][0]['statut']['contenu'], "signrequests");
							if ($pos !== FALSE) {
								$esignatureid = substr($stat["liste_edit"][$valeur][0]['statut']['contenu'], $pos + 13, strpos($stat["liste_edit"][$valeur][0]['statut']['contenu'], "target") -2 - $pos - 13); ?>
								<td><a href="<?php echo 'info_signature.php?esignatureid='.$esignatureid; ?>"><?php echo $esignatureid;?></a></td>
							<?php } else { ?>
								<td></td>
							<?php } ?>
						<?php } else { ?>
							<td></td>
							<td><a href="<?php echo URL_BASE_ZORRO."/create_decree.php?new&idmodel=".$post_selectarrete."&comp=".$composante_selected."&etp=".$todo['code']; ?>" target="_blank">Créer</a></td>
							<td></td>
						<?php } ?>
					</tr>
					<?php for($i = 1; $i < $rowspan; $i++)
					{ ?>
						<tr>
							<?php $periode = $stat["liste_edit"][$valeur][$i]['periode'] == '' ? "Annuel" : $stat["liste_edit"][$valeur][$i]['periode'];
							$periode_doublon = (array_key_exists($valeur, $stat["decree_doublon"]) && in_array($corresp_period[$periode], $stat["decree_doublon"][$valeur])) ? "class='warning_doublon' title='doublon'" : "";?>
							<td <?php echo $periode_doublon; ?>><?php echo $periode; ?></td>
							<td class="<?php echo $stat["liste_edit"][$valeur][$i]['statut']['class'];?>" title="<?php echo $stat["liste_edit"][$valeur][$i]['statut']['title'];?>"><?php echo $stat["liste_edit"][$valeur][$i]['statut']['contenu']; ?></td>
							<?php $pos = strpos($stat["liste_edit"][$valeur][$i]['statut']['contenu'], "signrequests");
							if ($pos !== FALSE) {
								$esignatureid = substr($stat["liste_edit"][$valeur][$i]['statut']['contenu'], $pos + 13, strpos($stat["liste_edit"][$valeur][$i]['statut']['contenu'], "target") -2 - $pos - 13); ?>
								<td><a href="<?php echo 'info_signature.php?esignatureid='.$esignatureid; ?>"><?php echo $esignatureid;?></a></td>
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
<?php } ?>
</body>
</html>


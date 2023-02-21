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
	if ($userCAS->isSuperAdmin(false))
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
			$liste_valide = array();
			$decree_made_by_status = array("Validé" => 0, "En cours signature présidence" => 0, "En cours visa composante" => 0, 'Brouillon' => 0);
			$decree_made_by_periode = array("Annuel" => 0, "S1" => 0, "S2" => 0, "Y1" => 0, "Y2" => 0);
			$not_made = sizeof($liste_to_do);
			$liste_etp_to_do = array_column($liste_to_do,'value');
			foreach($model_decrees as $mdecree)
			{
				$decree = new decree($dbcon, null, null, $mdecree['iddecree']);
				$query_value =  $decree->getFieldForFieldType($query_field);
				$periode_value =  $decree->getFieldForFieldType($idfield_periode);
				if (in_array($query_value, $liste_etp_to_do) && !array_key_exists($query_value, $liste_edit))
				{
					$not_made--;
				}
				$liste_edit[$query_value][] = $decree->getStatusAff();
				switch ($decree->getStatus(false))
				{
					case STATUT_BROUILLON :
						$decree_made_by_status['Brouillon']++;
						break;
					case STATUT_EN_COURS :
						$signStep = $decree->getSignStep();
						if ($signStep == "Visa de la composante")
						{
							$decree_made_by_status['En cours visa composante']++;
						}
						elseif ($signStep == "Validation de la présidence")
						{
							$decree_made_by_status['En cours signature présidence']++;
						}
						break;
					case STATUT_VALIDE :
						$decree_made_by_status['Validé']++;
						if ($query_value != '' && !array_key_exists($query_value.$periode_value, $liste_valide) && in_array($query_value, $liste_etp_to_do))
						{
							$liste_valide[$query_value.$periode_value] = 1;
							switch ($periode_value)
							{
								case '' :
									$decree_made_by_periode["Annuel"]++;
									break;
								case 'semestre 1' :
									$decree_made_by_periode["S1"]++;
									break;
								case 'semestre 2' :
									$decree_made_by_periode["S2"]++;
									break;
								case 'première année' :
									$decree_made_by_periode["Y1"]++;
									break;
								case 'deuxième année' :
									$decree_made_by_periode["Y2"]++;
									break;
							}
						}
						break;
				}
			}
		}
		else
		{
		}
?>
<div id="contenu1">
	<h2> Statistiques </h2>
	<div class="gauche">
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
	}
	?>
	<?php if (sizeof($listModels) == 0 ) { ?>
		Vous n'avez accès à aucun modèle de document. <br>
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
	</div>
	<div class="gauche">
	<?php } ?>
	<?php if (isset($model_selected_infos))
	{
		//print_r2($model_selected_infos);
		?>
		<label>Nombre d'arrêtés annuels attendus : </label><?php if ($query_field == NULL) { echo "1" ;} else { echo sizeof($liste_to_do);} ?><br>
		<label>Nombre d'arrêtés validés (mentions et périodes distinctes) : </label><?php echo sizeof($liste_valide);?><br>
		<label> -> Annuel : </label><?php echo $decree_made_by_periode["Annuel"];?><br>
		<?php if ($post_selectarrete == 12) { ?>
			<label> -> 1ère année : </label><?php echo $decree_made_by_periode["Y1"];?><br>
			<label> -> 2ème année : </label><?php echo $decree_made_by_periode["Y2"];?><br><br>
		<?php } else { ?>
			<label> -> Semestre 1 : </label><?php echo $decree_made_by_periode["S1"];?><br>
			<label> -> Semestre 2 : </label><?php echo $decree_made_by_periode["S2"];?><br><br>
		<?php } ?>
		<label>Nombre d'arrêtés NON créés : </label><?php echo $not_made;?><br>
		<label>Nombre d'arrêtés créés : </label><?php echo sizeof($model_decrees);?><br>
		<label> -> Validés : </label><?php echo $decree_made_by_status["Validé"]; ?><br>
		<label> -> En cours de signature présidence : </label><?php echo $decree_made_by_status["En cours signature présidence"]; ?><br>
		<label> -> En cours de visa composante : </label><?php echo $decree_made_by_status["En cours visa composante"]; ?><br>
		<label> -> Brouillon : </label><?php echo $decree_made_by_status["Brouillon"]; ?><br><br>
		<?php if (sizeof($liste_to_do) > 0)
		{ ?>
			<div>
				<table class="tableausimple">
					<tr><th class="titresimple" colspan=4>Arrêtés à produire</th></tr>
					<tr>
						<th class="titresimple">Code</th>
						<th class="titresimple">Libellé</th>
						<th class="titresimple">Composante</th>
						<th class="titresimple">Statut</th>
					</tr>
				<?php foreach ($liste_to_do as $todo)
				{ ?>
					<tr>
						<?php 
						$valeur = htmlspecialchars($todo['value']);
						$decree_edited = array_key_exists($valeur, $liste_edit);
						$rowspan =  $decree_edited ? sizeof($liste_edit[$valeur]) : 1; ?>
						<td rowspan=<?php echo $rowspan;?> ><?php echo $todo['code'];?></td>
						<td rowspan=<?php echo $rowspan;?>><?php echo $todo['value'];?></td>
						<td rowspan=<?php echo $rowspan;?>><?php echo $liste_comp[$todo['cmp']];?></td>
						<?php if ($decree_edited) 
						{ ?>
							<td class="<?php echo $liste_edit[$valeur][0]['class'];?>" title="<?php echo $liste_edit[$valeur][0]['title'];?>"><?php echo $liste_edit[$valeur][0]['contenu']; ?></td>
						<?php } else { ?>
							<td></td>
						<?php } ?>
					</tr>
					<?php for($i = 1; $i < $rowspan; $i++)
					{ ?>
						<tr><td class="<?php echo $liste_edit[$valeur][$i]['class'];?>" title="<?php echo $liste_edit[$valeur][$i]['title'];?>"><?php echo $liste_edit[$valeur][$i]['contenu']; ?></td></tr>
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


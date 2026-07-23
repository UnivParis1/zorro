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
if (is_null($userid) or ($userid == ""))
{
	elog("Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
	header('Location: index.php');
	exit();
}
$user = new user($dbcon, $userid);

// Récupération des modeles auxquels à accès l'utilisateur
$menuItem = 'menu_visa';
require ("include/menu.php");

// Récupération des modeles auxquels à accès l'utilisateur
$superadmin = false;
$listModels = array();
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

if (isset($_SESSION['phpCAS']) && array_key_exists('user', $_SESSION['phpCAS']) && $superadmin)
{
	if (isset($_POST['selectarrete']) && $_POST['selectarrete'] != '')
	{
		$post_selectarrete = $_POST['selectarrete'];
		$model_selected = new model($dbcon, $post_selectarrete);
		$model_selected_infos = $model_selected->getModelInfo();

		$inactive = "N";
		$list_visas_actifs = $model_selected->getVisas($inactive);
		if (isset($_POST['inactive']) && $_POST['inactive'] == 'on')
		{
			$inactive = "O";
			$list_visas_inactifs = $model_selected->getVisas($inactive);
		}
		if (isset($_POST['addvisa']))
		{
			$posnewvisa = sizeof($list_visas_actifs) > 0 ? $list_visas_actifs[sizeof($list_visas_actifs)-1]['position'] + 1 : 1;
			$model_selected->newVisa($posnewvisa, $user->getid());
		}
		if (isset($_POST['submitmodifvisaactif']))
		{
			$i = 1;
			foreach($list_visas_actifs as $visa)
			{
				$modif = false;
				if (isset($_POST['idvisa'.$i]) && $_POST['idvisa'.$i] != $visa['idmodel_visa'])
				{
					$modif = true;
				}
				if (isset($_POST['visa'.$i]) && $_POST['visa'.$i] != $visa['content'])
				{
					$modif = true;
				}
				if (isset($_POST['temsuppr'.$i]) && $_POST['temsuppr'.$i] == 'O')
				{
					$model_selected->updateVisa($_POST['idvisa'.$i], $_POST['visa'.$i], 0, "N", $user->getid());
				}
				elseif ($modif)
				{
					$model_selected->updateVisa($_POST['idvisa'.$i], $_POST['visa'.$i], $i, isset($_POST['temsuppr'.$i]) && $_POST['temsuppr'.$i] == "O" ? "N":"O", $user->getid());
				}
				$i++;
			}
			$model_selected->updateModelFile();
			odt_to_pdf("models/".$model_selected->getfile());
		}
		
		$list_visas_actifs = $model_selected->getVisas("N");
	}
	?>

	<script>
	function modifactivevisa(id)
	{
		document.getElementById('idvisamodif').value = document.getElementById("idvisa"+id).value;
		document.getElementById('contentmodif').value = document.getElementById("visa"+id).value;
		document.getElementById('submitmodifvisa').form.submit();
	}

	function movevisa(idmoins, idplus)
	{
		var idvisaup, textup, checkup, styleup, temsupprup;
		idvisaup = document.getElementById('idvisa'+idmoins).value;
		textup = document.getElementById('visa'+idmoins).value;
		checkup = document.getElementById('temsuppr'+idmoins).checked;
		styleup = document.getElementById('tr'+idmoins).getAttribute("style");
		temsupprup = document.getElementById('temsuppr'+idmoins).value;
		document.getElementById('idvisa'+idmoins).value = document.getElementById('idvisa'+idplus).value;
		document.getElementById('visa'+idmoins).value = document.getElementById('visa'+idplus).value;
		document.getElementById('temsuppr'+idmoins).checked = document.getElementById('temsuppr'+idplus).checked;
		document.getElementById('tr'+idmoins).setAttribute("style", document.getElementById('tr'+idplus).getAttribute("style"));
		document.getElementById('temsuppr'+idmoins).value = document.getElementById('temsuppr'+idplus).value;
		document.getElementById('idvisa'+idplus).value = idvisaup;
		document.getElementById('visa'+idplus).value = textup;
		document.getElementById('temsuppr'+idplus).checked = checkup;
		document.getElementById('tr'+idplus).setAttribute("style", styleup);
		document.getElementById('temsuppr'+idplus).value = temsupprup;
	}

	function supprvisa(idvisa, nbvisa)
	{
		var i = parseInt(idvisa)+1;
		var j = parseInt(idvisa);
		while (i <= nbvisa)
		{
			movevisa(j, i);
			i++; j++;
		}
		document.getElementById('tr'+nbvisa).setAttribute("style", "background-color:#FF0000");
		document.getElementById('temsuppr'+nbvisa).value = "O";
	}
	</script>

	<div id="contenu1">
		<h2> Gestion des visas </h2>
		<?php if (sizeof($listModels) == 0 ) { ?>
			<div class="gauche">
			Vous n'avez accès à aucun modèle de document. <br>
			</div>
		<?php } else { ?>
			<div class="recherche">
			<form class ="form-zorro" name="formselectdecree" action="visa.php" method="post">
				<input type="hidden" name='userid' value='<?php echo $userid;?>'>
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
				<?php //<label>Afficher les visas désactivés</label>
				//$checkedinactive = isset($inactive) && $inactive == 'O' ? "checked='checked'" : ""; 
				//<input type="checkbox" name="inactive" title="Afficher les visas désactivés" php echo $checkedinactive; >?>
				<input type='submit' name='Soumettre' id="Soumettre" value="Soumettre">
			</form>
		</div>
		<div class="gauche">
		<?php } ?>
		<?php if (isset($model_selected_infos))
		{
			if (isset($list_visas_actifs))
			{
				$nb_visas = sizeof($list_visas_actifs);
				?>
				<form class="addvisa" name="formmodifvisaactif" action="visa.php" method="post">
					<input type="hidden" name='userid' id='userid' value='<?php echo $userid;?>'>
					<input type="hidden" name="selectarrete" id="selectarrete" value="<?php echo isset($post_selectarrete) ? $post_selectarrete : '';?>">
					<input type="hidden" name="inactive" id="inactive" value=<?php echo isset($inactive) && $inactive == 'O' ? 'on' : 'off';?>>
					<table id="tablevisa" name="tablevisa">
					<?php
					$i = 1;
					foreach($list_visas_actifs as $visa)
					{?>
						<tr id='<?php echo "tr".$i;?>' name='<?php echo "tr".$i;?>'>
							<td><?php echo $visa['position']; ?><input type="hidden" id="<?php echo 'idvisa'.$i;?>" name="<?php echo 'idvisa'.$i;?>" value="<?php echo $visa['idmodel_visa'];?>"></td>
							<td>
								<?php if ($i > 1) { ?>
								<button class="move" type="button" id="<?php echo 'up'.$i;?>" name="<?php echo 'up'.$i;?>" title="up" onclick="movevisa('<?php echo $i-1;?>','<?php echo $i;?>');">⬆️</button>
								<?php } ?> <br>
								<?php if ($i < $nb_visas) { ?>
								<button class="move" type="button" id="<?php echo 'down'.$i;?>" name="<?php echo 'down'.$i;?>" title="down" onclick="movevisa('<?php echo $i;?>','<?php echo $i+1;?>');">⬇️</button>
								<?php } ?>
							</td>
							<td>
								<textarea rows=5 cols=100 id='<?php echo 'visa'.$i;?>' name='<?php echo 'visa'.$i;?>' value="<?php echo $visa['content'];?>"><?php echo $visa['content'];?></textarea>
							</td>
							<td>
								<?php if ($visa['active'] == 'O') { ?>
								<button type="button" id="<?php echo 'suppr'.$i;?>" name="<?php echo 'suppr'.$i;?>" title="supprimer" onclick="supprvisa('<?php echo $i;?>','<?php echo $nb_visas;?>');">X</button>
								<?php } else { ?>
								<button type="button" id="<?php echo 'suppr'.$i;?>" name="<?php echo 'suppr'.$i;?>" title="rétablir" onclick="ajoutervisa('<?php echo $nb_visas;?>');">Rétablir</button>
								<?php } ?>
								<input type="hidden" id="<?php echo 'temsuppr'.$i;?>" name="<?php echo 'temsuppr'.$i;?>">
							</td>
						</tr>
							<?php $i++;
					}?>
					</table>
					<input type="submit" id="addvisa" name="addvisa" value="Ajouter un visa" onclick="return confirm('Les modifications non enregistrée vont être perdues. Souhaitez-vous continuer ?')">
					<input id="submitmodifvisaactif" name="submitmodifvisaactif" type="submit" value='Valider les modifications'>
					<input id="rollbackmodifvisaactif" name="rollbackmodifvisaactif" type="submit" value='Annuler les modifications' onclick="document.getElementById('Soumettre').form.submit();">
			</form>	
			<?php }
			if (isset($list_visas_inactifs))
			{
				$nb_visas = sizeof($list_visas_inactifs);
				?>
				<h3>Visas désactivés</h3>
				<form name="formmodifvisainactif" action="visa.php" method="post">
					<input type="hidden" name='userid' id='userid' value='<?php echo $userid;?>'>
					<input type="hidden" name="selectarrete" id="selectarrete" value="<?php echo isset($post_selectarrete) ? $post_selectarrete : '';?>">
					<input type="hidden" name="inactive" id="inactive" value=<?php echo isset($inactive) && $inactive == 'O' ? 'on' : 'off';?>>
					<table id="tablevisainactif" name="tablevisainactif">
					<?php
					foreach($list_visas_inactifs as $visa)
					{?>
						<tr id='<?php echo "tr".$i;?>' name='<?php echo "tr".$i;?>'>
							<td>
								<textarea rows=5 cols=100 id='<?php echo 'visa'.$i;?>' name='<?php echo 'visa'.$i;?>' value="<?php echo $visa['content'];?>"><?php echo $visa['content'];?></textarea>
							</td>
							<td>
								<button type="button" id="<?php echo 'retab'.$i;?>" name="<?php echo 'retab'.$i;?>" title="down" onclick="rétablirvisa('<?php echo $nb_visas;?>');">Rétablir</button>
							</td>
						</tr>
							<?php $i++;
					}?>
					</table>
					<input id="submitmodifvisainactif" name="submitmodifvisainactif" type="submit" value='Valider les modifications'>
				</form>	
			<?php } ?>
		</div>
	</div>
	<div id="contenu2">
		<?php 
		$filename = "models/".$model_selected->getfile();
		$filenamepdf = substr($filename, 0, -3)."pdf";// var_dump($filenamepdf);
		if (!file_exists($filenamepdf))
		{
			odt_to_pdf($filename);
		}
		if (file_exists($filenamepdf))
		{
			$doc_pdf = fopen($filenamepdf, 'r');
			$contenu_pdf = fread($doc_pdf, filesize($filenamepdf));
			$encodage = base64_encode($contenu_pdf);
			?>
			<?php echo '<iframe src=data:application/pdf;base64,' . $encodage . ' width="100%" height="500px">';
			echo "</iframe>";?>

			<br><br>

		<?php } ?>

	</div>
	<?php } ?>
<?php } else { ?>
<div id="contenu1">
	<h2> Accès interdit </h2>
</div>
<?php } ?>
</body>
</html>

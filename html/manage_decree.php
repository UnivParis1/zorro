<?php 
	require_once ('CAS.php');
	require_once './include/casconnection.php';
	require_once ('./include/casconnection.php');
	require_once ('./include/dbconnection.php');
	require_once ('./class/user.php');
	require_once ('./class/model.php');
	require_once ('./class/decree.php');
	require_once ('./class/reference.php');
	require_once ('./class/ldap.php');

	$ldap = new ldap();
	$ref = new reference($dbcon, $rdbApo);
	$userid = $ref->getUserUid();
	if (is_null($userid) or ($userid == ""))
	{
		elog("Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
		header('Location: index.php');
		exit();
	}

	$params = array();

    if (isset($_POST['selectarrete']))
    {
    	$post_selectarrete = $_POST['selectarrete'];
		$params['idmodel'] = $post_selectarrete;
    }
    if (isset($_POST['selectstatut']))
    {
		$post_selectstatut = $_POST['selectstatut'];
		$params['status'] = $post_selectstatut;
    }
    if (isset($_POST['contenu']) && $_POST['contenu'] != '')
    {
		$post_contenu = $_POST['contenu'];
		$params['contenu'] = $post_contenu;
    }
     
    // Récupération des modeles auxquels à accès l'utilisateur
    $user = new user($dbcon, $userid);
    if ($user->isSuperAdmin() || $user->isDaji())
    {
    	$ref = new reference($dbcon, $rdbApo);
    	$allmodels = $ref->getListModel();
    	foreach ($allmodels as $idmodel => $model)
    	{
    		$model = new model($dbcon, $idmodel);
    		$listModels[] = $model->getModelInfo();
    	}
    } else {
	    $roles = $user->getGroupeRoles($_SESSION['groupes'], 'model');
	    $listModels = array();
	    foreach ($roles as $role)
	    {
	    	$model = new model($dbcon, $role['idmodel']);
	    	$listModels[] = $model->getModelInfo();
	    }
    }
    $menuItem = "menu_manage";
    require ("include/menu.php");
?>
<div id="contenu1">
<h2> Consultation des arrêtés</h2>
<?php 
	//print_r2($roles);
	// echo "<br>";
	//print_r2($listModels);
	// echo "<br>";
?>
<div class="recherche">
	<form name="formselectdecree" action="manage_decree.php" method="post">
		<input type="hidden" name='userid' value='<?php echo $userid;?>'>
		<input type="text" name="contenu" id="contenu" value='<?php echo (isset($post_contenu)) ? $post_contenu : '';?>' placeholder="Contenu, numéro..."/>
		<select style="width:26em" name="selectarrete" id="selectarrete">
			<?php
			if (!isset($_POST['selectarrete'])) { ?>
				<option value="" selected="selected">Modèle</option>
			<?php } else { ?>
				<option value="">Modèle</option>
			<?php }
			$type = 0;
			foreach ($listModels as $model) {
				if ($model['iddecree_type'] != $type) {
					if ($type != 0) { ?>
						</optgroup>
					<?php } $type = $model['iddecree_type']; ?>
					<optgroup label="<?php echo $model['namedecree_type'];?>">
				<?php } if (isset($post_selectarrete) && $post_selectarrete == $model['idmodel']) { ?>
					<option value="<?php echo $model['idmodel'];?>" selected="selected"><?php echo $model['name'];?></option>
				<?php } else { ?>
					<option value="<?php echo $model['idmodel'];?>"><?php echo $model['name'];?></option>
				<?php }
			} ?>
			</optgroup>
		</select>
		<select style="width:26em" name="selectstatut" id="selectstatut">
			<?php
			if (!isset($_POST['selectstatut'])) { ?>
				<option value="" selected="selected">Statut</option>
			<?php } else { ?>
				<option value="">Statut</option>
			<?php }
			foreach ($ref->getStatuts() as $name => $value) {
				if (isset($post_selectstatut) && $post_selectstatut == $name) { ?>
					<option value="<?php echo $name;?>" selected="selected"><?php echo $value;?></option>
				<?php } else { ?>
					<option value="<?php echo $name;?>"><?php echo $value;?></option>
			<?php } } ?>
		</select>
		<input type='submit' name='rechercher' value='Rechercher'>
	</form>

<?php 
//$alldecrees = isset($post_selectarrete) ? $user->getAllDecrees($post_selectarrete) : $user->getAllDecrees();
$alldecrees = $user->getDecreesBy($params);
$nbdecree = sizeof($alldecrees); ?>
	<h4><?php echo $nbdecree;?> résultat(s).</h4>
</div>
<?php if ($nbdecree > 0) { ?>
	<table id="tableau_documents" class="tableausimple">
		<thead>
			<tr>
				<th class="titresimple" style='cursor: pointer;'>Numéro <font></font></th>
				<th class="titresimple" style='cursor: pointer;'>Document <font></font></th>
				<th class="titresimple" style='cursor: pointer;'>Modèle <font></font></th>
				<th class="titresimple" style='cursor: pointer;'>Service/UFR<font></font></th>
				<th class="titresimple" style='cursor: pointer;'>Créateur <font></font></th>
				<th class="titresimple" style='cursor: pointer;'>Date <font></font></th>
				<th class="titresimple" >Statut</th>
			</tr>
		</thead>
		<tbody>
	<?php 	foreach ($alldecrees as $decree) {
				$objdecree = new decree($dbcon, null, null, $decree['iddecree']);
				$nom_aff = $objdecree->getFileNameAff();?>
			<tr>
				<?php if ($decree['number'] == 0) {?>
					<td></td>
				<?php } else {?>
					<td class="cellulesimple"><?php echo $decree['year'].'/'.$decree['number'];?></td>
				<?php } ?>
				<!--  <td class="cellulesimple"><a href="create_decree.php?num=<?php echo $decree['number'];?>&year=<?php echo $decree['year'];?>"><?php echo $decree['decreetypename'].' '.$decree['modelname']; ?></a></td>-->
				<td class="cellulesimple" title="<?php echo $objdecree->getFileName(); ?>"><a href="create_decree.php?id=<?php echo $decree['iddecree'];?>"><?php echo $nom_aff; ?></a></td>
				<td class="cellulesimple"><?php echo $decree['modelname']; ?></td>
				<td class="cellulesimple"><?php echo $ldap->getStructureName($decree['structure']); ?></td>
				<td class="cellulesimple"><?php echo $decree['uid']; ?></td>
				<td class="cellulesimple date"><?php echo date('d/m/Y',strtotime($decree['majdate'])); ?></td>
				<?php
				$status = $decree['status'];
				$majdate = $decree['majdate'];
				if ($status == STATUT_EN_COURS || $status == STATUT_CORBEILLE)
				{
					$mod_decree = new decree($dbcon, null, null, $decree['iddecree']);
					$status = $mod_decree->getStatus();
					$majdate = $mod_decree->getMajDate();
				}
				switch ($status) {
					case STATUT_ANNULE :
						$contenu = "<a href='create_decree.php?id=".$decree['iddecree']."'><img src='img/supprimer.svg' alt='annulé' width='20px'></a>";
						$title = 'Annulé';
						$class = "img";
						break;
					case STATUT_REFUSE :
						$mod_decree = new decree($dbcon, null, null, $decree['iddecree']);
						$comment = $mod_decree->getRefuseComment();
						$contenu = "<a href='".ESIGNATURE_BASE_URL.ESIGNATURE_URL_DOC.$decree['idesignature']."' target='_blank'>".date('d/m/Y', strtotime($majdate))."</a>";
						$title = 'Refusé : '.$comment;
						$class = "red";
						break;
					case STATUT_BROUILLON :
						$contenu = "<a href='create_decree.php?id=".$decree['iddecree']."'><img src='img/brouillon.svg' alt='brouillon' width='20px'></a>";
						$title = 'Brouillon';
						$class = "img";
						break;
					case STATUT_HORS_ZORRO :
						$contenu = "<a href='create_decree.php?id=".$decree['iddecree']."'><img src='img/valide_OK.svg' alt='hors_zorro' width='20px'></a>";
						$title = 'Hors Zorro';
						$class = "img";
						break;
					case STATUT_VALIDE :
						$contenu = "<a href='".ESIGNATURE_BASE_URL.ESIGNATURE_URL_DOC.$decree['idesignature']."' target='_blank'>".date('d/m/Y', strtotime($majdate))."</a>";
						$title = 'Validé';
						$class = "green";
						break;
					case STATUT_EN_COURS :
						$contenu = "<a href='".ESIGNATURE_BASE_URL.ESIGNATURE_URL_DOC.$decree['idesignature']."' target='_blank'><img src='img/enattente.svg' alt='signature en cours' width='20px'></a>";
						$step = $mod_decree->getSignStep();
						$title = 'En cours de signature : '.$step;
						$class = "img";
						break;
					case STATUT_ERREUR :
						$contenu = "<a href='create_decree.php?id=".$decree['iddecree']."'><img src='img/erreur1.svg' alt='Document non trouvé sur eSignature' width='20px'></a>";
						$title = 'erreur';
						$class = "img";
						break;
					case STATUT_SUPPR_ESIGN :
						$contenu = "<a href='create_decree.php?id=".$decree['iddecree']."'><img src='img/supprimer.svg' alt='Document supprimé d\'eSignature' width='20px'></a>";
						$title = 'Document supprimé d\'eSignature';
						$class = "img";
						break;
					case STATUT_CORBEILLE :
						$contenu = "<a href='".ESIGNATURE_BASE_URL.ESIGNATURE_URL_DOC.$decree['idesignature']."' target='_blank'><img src='img/supprimer.svg' alt='Document dans la corbeille d\'eSignature' width='20px'></a>";
						$title = 'Document dans la corbeille d\'eSignature';
						$class = "img";
						break;
					default :
						$contenu = "<a href='create_decree.php?id=".$decree['iddecree']."'><img src='img/supprimer.svg' alt='annulé' width='20px'></a>";
						$title = 'Annulé';
						$class = "img";
						break;
				}?>
				<td class="<?php echo $class;?>" title="<?php echo $title;?>"><?php echo $contenu; ?></td>
			</tr>
		<?php } ?>
		</tbody>
</table>
<?php } ?>

</div>
<script>
const getCellValue = (tr, idx) =>
{
	if (tr.children[idx].className.indexOf('date', 1) != -1)
	{
		var tmpdate = tr.children[idx].innerText;
		var jour  = tmpdate.substring(0,2);
		var mois  = tmpdate.substring(3,5);
		var annee = tmpdate.substring(6,10);
		return annee+mois+jour;
	}
    return tr.children[idx].innerText || tr.children[idx].textContent;
}

const comparer = (idx, asc) => (a, b) => ((v1, v2) =>
    v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2)
    )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));

// do the work...
document.getElementById('tableau_documents').querySelectorAll('th').forEach(th => th.addEventListener('click', (() => {
    const table = th.closest('table');
    const tbody = table.querySelector('tbody');
    Array.from(tbody.querySelectorAll('tr'))
        .sort(comparer(Array.from(th.parentNode.children).indexOf(th), this.asc = !this.asc))
        .forEach(tr => tbody.appendChild(tr) );
    theader = table.querySelector('theader');

    //alert(Array.from(th.parentNode.querySelectorAll('th')));

    for (var thindex = 0 ; thindex < document.getElementById('tableau_documents').querySelectorAll('th').length; thindex++)
    {
        if (th.parentNode.children[thindex]!==null)
        {
            if (th.parentNode.children[thindex].querySelector('font')!==null)
            {
                th.parentNode.children[thindex].querySelector('font').innerText = ' ';
            }
        }
    }

    if (this.asc)
    {
        th.querySelector('font').innerHTML = '&darr;'; // fleche qui descend
    }
    else
    {
        th.querySelector('font').innerHTML = '&uarr;'; // fleche qui monte
    }

})));

document.getElementById('tableau_documents').querySelectorAll('th')[5].click(); // On simule le clic sur la 6e colonne pour faire afficher la fleche et initialiser le asc
document.getElementById('tableau_documents').querySelectorAll('th')[5].click(); // On simule le clic sur la 6e colonne pour trier par ordre chronologique inverse

</script>
</body>
</html>


<?php 
	require_once ('CAS.php');
	require_once './include/casconnection.php';
	require_once ('./include/casconnection.php');
	require_once ('./include/dbconnection.php');
	require_once ('./class/user.php');
	require_once ('./class/model.php');
	require_once ('./class/decree.php');
	require_once ('./class/reference.php');

	$ref = new reference($dbcon, $rdbApo);
	$userid = $ref->getUserUid();
	if (is_null($userid) or ($userid == ""))
	{
		elog("Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
		header('Location: index.php');
		exit();
	}

    if (isset($_POST['selectarrete']))
    {
    	$post_selectarrete = $_POST['selectarrete'];
    }
     
    // Récupération des modeles auxquels à accès l'utilisateur
    $user = new user($dbcon, $userid);
    if ($user->isSuperAdmin())
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
<?php if (sizeof($listModels) == 0 ) { ?>
Vous n'avez accès à aucun modèle d'arrêté. <br>
<?php } else { ?>
<label>Sélection du modèle</label> 
<form name="formselectdecree" action="manage_decree.php" method="post">
<input type="hidden" name='userid' value='<?php echo $userid;?>'>
<select style="width:26em" name="selectarrete" id="selectarrete" onchange="this.form.submit()">			             		
        <?php 
        if (!isset($_POST['selectarrete'])) { ?>
        <option value="" selected="selected">&nbsp;</option>
        <?php } else { ?>
            <option value="">&nbsp;</option>
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
		<?php } } ?>
		</optgroup> 
</select>
</form>
<?php } ?>


<p>Affichage des arrêtés pour le modèle et l'utilisateur</p>

<?php 
$alldecrees = isset($post_selectarrete) ? $user->getAllDecrees($post_selectarrete) : $user->getAllDecrees(); 
if (sizeof($alldecrees) > 0) { ?>
	<table id="tableau_documents" class="tableausimple">
		<thead>
			<tr>
				<th class="titresimple" style='cursor: pointer;'>Numéro <font></font></th>
				<th class="titresimple" style='cursor: pointer;'>Document <font></font></th>
				<th class="titresimple" style='cursor: pointer;'>Type <font></font></th>
				<th class="titresimple" style='cursor: pointer;'>UFR <font></font></th>
				<th class="titresimple" style='cursor: pointer;'>Créateur <font></font></th>
				<th class="titresimple" style='cursor: pointer;'>Création <font></font></th>
				<th class="titresimple" >Statut</th>
				<th class="titresimple" style='cursor: pointer;'>eSignature <font></font></th>
			</tr>
		</thead>
		<tbody>
	<?php 	foreach ($alldecrees as $decree) {
				$objdecree = new decree($dbcon, null, null, $decree['iddecree']);?>
			<tr>
				<?php if ($decree['status'] == STATUT_ANNULE) {?>
					<td></td>
				<?php } else {?>
					<td class="cellulesimple"><?php echo $decree['year'].'/'.$decree['number'];?></td>
				<?php } ?>
				<!--  <td class="cellulesimple"><a href="create_decree.php?num=<?php echo $decree['number'];?>&year=<?php echo $decree['year'];?>"><?php echo $decree['decreetypename'].' '.$decree['modelname']; ?></a></td>-->
				<td class="cellulesimple"><a href="create_decree.php?id=<?php echo $decree['iddecree'];?>"><?php echo substr($objdecree->getFileName(), 0, -4); ?></a></td>
				<td class="cellulesimple"><?php echo $decree['decreetypename']; ?></td>
				<td class="cellulesimple"><?php echo $decree['structure']; ?></td>
				<td class="cellulesimple"><?php echo $decree['uid']; ?></td>
				<td class="cellulesimple"><?php echo $decree['createdate']; ?></td>
				<?php
				$status = $decree['status'];
				$majdate = $decree['majdate'];
				if ($status == STATUT_EN_COURS)
				{
					$mod_decree = new decree($dbcon, null, null, $decree['iddecree']);
					$status = $mod_decree->getStatus();
					$majdate = $mod_decree->getMajDate();
				}
				switch ($status) {
					case STATUT_ANNULE :
						$contenu = "<img src='img/supprimer.svg' alt='annulé' width='20px'>";
						$title = 'annulé';
						$class = "img";
						break;
					case STATUT_REFUSE :
						$mod_decree = new decree($dbcon, null, null, $decree['iddecree']);
						$comment = $mod_decree->getRefuseComment();
						$contenu = date('d/m/Y', strtotime($majdate));
						$title = 'refusé : '.$comment;
						$class = "red";
						break;
					case STATUT_BROUILLON :
						$contenu = "<img src='img/brouillon.svg' alt='brouillon' width='20px'>";
						$title = 'brouillon';
						$class = "img";
						break;
					case STATUT_VALIDE :
						$contenu = date('d/m/Y', strtotime($majdate));
						$title = 'signé';
						$class = "green";
						break;
					case STATUT_EN_COURS :
						$contenu = "<img src='img/enattente.svg' alt='signature en cours' width='20px'>";
						$title = 'signature en cours';
						$class = "img";
						break;
					case STATUT_ERREUR :
						$contenu = "erreur";
						$title = 'erreur';
						$class = "red";
						break;
					default :
						break;
				}?>
				<td class='<?php echo $class;?>' title='<?php echo $title;?>'><?php echo $contenu; ?></td>
				<td class="cellulesimple"><?php echo $decree['idesignature']; ?></td>
			</tr>
		<?php } ?>
		</tbody>
</table>
<?php } ?>

</div>
<script>
const getCellValue = (tr, idx) =>
{
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

document.getElementById('tableau_documents').querySelectorAll('th')[1].click(); // On simule le clic sur la 2e colonne pour faire afficher la fleche et initialiser le asc

</script>
</body>
</html>


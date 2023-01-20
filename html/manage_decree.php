<?php 
	require_once ('CAS.php');
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
    if (isset($_POST['findnum']) && $_POST['findnum'] != '')
    {
		$post_findnum = $_POST['findnum'];
		$params['findnum'] = $post_findnum;
    }
    if (isset($_POST['findannee']) && $_POST['findannee'] != '')
    {
		$post_findannee = $_POST['findannee'];
		$params['year'] = $post_findannee;
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
	$listYears = $ref->getYears();
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
		<input type="hidden" name='userid' id='userid' value='<?php echo $userid;?>'>
		<input type="text" name="contenu" id="contenu" value='<?php echo (isset($post_contenu)) ? $post_contenu : '';?>' placeholder="Contenu..."/>
		<input type="text" name="findnum" id="findnum" value='<?php echo (isset($post_findnum)) ? $post_findnum : '';?>' placeholder="Numéro"/>
		<select style="width:26em" name="findannee" id="findannee">
		<?php if (!isset($_POST['selectarrete'])) { ?>
				<option value="" selected="selected">Année</option>
			<?php } else { ?>
				<option value="">Année</option>
			<?php }
			foreach ($listYears as $year)
			{
				if (isset($post_findannee) && $post_findannee == $year) { ?>
				<option value="<?php echo $year;?>" selected="selected"><?php echo $year;?></option>
			<?php } else { ?>
				<option value="<?php echo $year;?>"><?php echo $year;?></option>
			<?php }
			} ?>
		</select>
		<select style="width:26em" name="selectarrete" id="selectarrete">
			<?php
			if (!isset($_POST['selectarrete'])) { ?>
				<option value="" selected="selected">Modèle</option>
			<?php } else { ?>
				<option value="">Modèle</option>
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
				<?php } if (isset($post_selectarrete) && $post_selectarrete == $model['idmodel']) { ?>
					<option value="<?php echo $model['idmodel'];?>" selected="selected" <?php echo $color;?>><?php echo $model['name'];?></option>
				<?php } else { ?>
					<option value="<?php echo $model['idmodel'];?>" <?php echo $color;?>><?php echo $model['name'];?></option>
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
		<input type="hidden" id="orderby" value=5>
		<input type="hidden" id="desc" value="FALSE">
		<input type="hidden" id="nbaff" value=0>
		<input type="hidden" id="status" value=<?php echo isset($post_selectstatut) ? $post_selectstatut : '';?>>
		<input type="hidden" id="idmodel" value=<?php echo isset($post_selectarrete) ? $post_selectarrete : '';?>>
		<input type='submit' name='rechercher' value='Rechercher'>
	</form>

<?php 
//$alldecrees = isset($post_selectarrete) ? $user->getAllDecrees($post_selectarrete) : $user->getAllDecrees();
$alldecrees = $user->getDecreesBy($params, 0);
$nbdecree = sizeof($alldecrees);
$alldecrees = $user->getDecreesBy($params, 20); ?>
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
		<tbody id="post-data">
<?php include 'display_decrees.php';?>
<!--		</tbody>-->
		<!--<tbody id="post-data">--><!-- dynamically load posts via ajax --></tbody>
</table>
		<div id="ajax-load">Veuillez patienter...</div>
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
	var orderby = document.getElementById("orderby");
	orderby.setAttribute("value", th.cellIndex);
	var nbaff = document.getElementById("nbaff");
	nbaff.setAttribute("value", 0);

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
        th.querySelector('font').innerHTML = '&uarr;'; // fleche qui monte
		var desc = document.getElementById("desc");
		desc.setAttribute("value", "TRUE");
    }
    else
    {
        th.querySelector('font').innerHTML = '&darr;'; // fleche qui descend
		var desc = document.getElementById("desc");
		desc.setAttribute("value", "FALSE");
    }
	refreshtab();
})));

document.getElementById('tableau_documents').querySelectorAll('th')[5].click(); // On simule le clic sur la 6e colonne pour faire afficher la fleche et initialiser le asc
//document.getElementById('tableau_documents').querySelectorAll('th')[5].click(); // On simule le clic sur la 6e colonne pour trier par ordre chronologique inverse
</script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
</body>
</html>


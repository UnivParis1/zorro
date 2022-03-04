<?php 
	require_once ('CAS.php');
	include './include/casconnection.php';
	require_once ('./include/casconnection.php');
	require_once ('./include/dbconnection.php');
	require_once ('./class/user.php');
	require_once ('./class/model.php');
	require_once ('./class/reference.php');

    if (isset($_POST["userid"]))
        $userid = $_POST["userid"];
    else
        $userid = null;
    if (is_null($userid) or ($userid == "")) {
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
	<table class="tableausimple">
		<tr>
			<th class="titresimple">Numéro</th>
			<th class="titresimple">Document</th>
			<th class="titresimple">Type</th>
			<th class="titresimple">UFR</th>
			<th class="titresimple">Créateur</th>
			<th class="titresimple">Envoi</th>
			<th class="titresimple">Statut</th>
			<th class="titresimple">eSignature</th>
		</tr>
<?php 	foreach ($alldecrees as $decree) { ?>
		<tr>
			<?php if ($decree['status'] == 'a') {?>
				<td></td>
			<?php } else {?>
				<td class="cellulesimple"><?php echo $decree['year'].'/'.$decree['number'];?></td>
			<?php } ?>
			<!--  <td class="cellulesimple"><a href="create_decree.php?num=<?php echo $decree['number'];?>&year=<?php echo $decree['year'];?>"><?php echo $decree['decreetypename'].' '.$decree['modelname']; ?></a></td>-->
			<td class="cellulesimple"><a href="create_decree.php?id=<?php echo $decree['iddecree'];?>"><?php echo $decree['decreetypename'].' '.$decree['modelname']; ?></a></td>
			<td class="cellulesimple"><?php echo $decree['decreetypename']; ?></td>
			<td class="cellulesimple"><?php echo $decree['structure']; ?></td>
			<td class="cellulesimple"><?php echo $decree['uid']; ?></td>
			<td class="cellulesimple"><?php echo $decree['createdate']; ?></td>
			<td class="cellulesimple"><?php echo $decree['status']; ?></td>
			<td class="cellulesimple"><?php echo $decree['idesignature']; ?></td>
		</tr>
<?php } ?>
</table>
<?php } ?>

</div>
</body>
</html>


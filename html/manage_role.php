<?php
require_once ('CAS.php');
include './include/casconnection.php';
require_once ('./class/reference.php');
require_once ('./class/ldap.php');
require_once ('./include/dbconnection.php');
require_once ('./include/fonctions.php');

$ldap = new ldap();
if (isset($_POST["userid"]))
{
	$userid = $_POST["userid"];
}
else
{
	$userid = null;
}
if (is_null($userid) or ($userid == "")) {
	elog("Redirection vers index.php (UID de l'utilisateur=" . $uid . ")");
	header('Location: index.php');
	exit();
}
	
require ("include/menu.php");
?>	
<script>
function changerole(idgroupe, i){
	//alert(uid+' '+i+' ');
	document.getElementById('idgrouperole').value = idgroupe;
	document.getElementById('role').value = i;
	var role = 'selectrole'+idgroupe+'_'+i;
	document.getElementById('value').value = document.getElementById(role).value;
	document.getElementById('value').form.submit();
}
</script>

<div> Gestion des autorisations </div><br>
<?php 	// Récupération des utilisateurs



$select_allgroupes = "SELECT
	g.idgroupe,
    g.name,
    r.idrole,
    r.name as rolename,
    gr.idmodel,
    m.name as modelname,
	dt.iddecree_type,
    dt.name as decreetypename,
	gr.active
FROM
	groupe g
    LEFT JOIN groupe_role gr
		ON g.idgroupe = gr.idgroupe
	LEFT JOIN role r
		ON r.idrole = gr.idrole
    LEFT JOIN model m
		ON m.idmodel = gr.idmodel
	LEFT JOIN decree_type dt
		ON m.iddecree_type = dt.iddecree_type
ORDER BY g.name";

$ref = new reference($dbcon, $rdbApo);
$list_decree_type = $ref->getListDecreeType();
$list_roles = $ref->getListRole();
$nb_roles = sizeof($list_roles);

if (isset($_POST['idgrouperole']))
{
	//print_r2($_POST);
	$idgrouperole = $_POST['idgrouperole'];
	//echo "<br>";
	//print_r2($list_decree_type);
	//$nb_model_for_type = $list_decree_type[1]['nb_model'];
	$decreetype = 1;
	$pos_model_in_type = $_POST['role']; 
	//echo "nb_model * nb_roles : ".$list_decree_type[$decreetype]['nb_model'] * $nb_roles."<br>";
	//echo "pos_model_in_type : ".$pos_model_in_type."<br>";
	while ($list_decree_type[$decreetype]['nb_model'] * $nb_roles < $pos_model_in_type)
	{
		$pos_model_in_type -= $list_decree_type[$decreetype]['nb_model']*$nb_roles;
		$decreetype++;
	}
	$model = intval(ceil($pos_model_in_type/$nb_roles));
	$posmodelalpha = array_keys($ref->getListModel($decreetype))[$model-1];
	$idmodel = $ref->getListModel($decreetype)[$posmodelalpha]['idmodel'];
	$idrole = $nb_roles + $pos_model_in_type - $model*$nb_roles;
	$active = $_POST['value'] == 'O' ? 'O' : 'N';
	$updater = new user($dbcon, $userid);
	$updaterid = $updater->getid();
	$role = array();
	$role[] = array('idgroupe' => $idgrouperole, 'idmodel' => $idmodel, 'idrole'=> $idrole, 'createuserid' => $updaterid, 'updateuserid' => $updaterid, 'active' => $active);
	//echo "<br>Enregistrer la valeur $active pour l'utilisateur $uidrole le type $decreetype modele $model et le role $idrole <br>";
	//echo "<br>setroles() ".print_r2($role)."<br>";
	$updater->setGroupeRoles($role);
}
	
$nb_col = 0;
$list_model = array(); 
?> 
<table class="tableausimple"><tr><th rowspan="3" class="titresimple">Groupe</th>
<?php foreach($list_decree_type as $id_dt => $decree_type)
{
	$list_model[$id_dt] = $ref->getListModel($id_dt); 
	$nb_col += sizeof($list_model[$id_dt]); ?> 
	<th colspan="<?php echo sizeof($list_model[$id_dt]) * $nb_roles;?>" class="titresimple"><?php echo $decree_type['name'];?></th>
<?php 	
} 
$listgroupesroles = array();
$result = mysqli_query($dbcon, $select_allgroupes);
if (mysqli_error($dbcon))
{
	elog("Erreur a l'execution de la requete select all groupes.");
}
else {
	
	while ($res = mysqli_fetch_assoc($result))
	{
		// emplacement du droit dans le tableau somme(iddecree_type * nb_model) + somme(idmodel * nb_roles) + pos(role, list_roles)
		$position = 0;
		for ($i = 1; $i < $res['iddecree_type']; $i++)
		{
			$position += $nb_roles * $list_decree_type[$i]['nb_model'];
		}
		$position += (array_search($res['idmodel'], array_keys($list_model[$res['iddecree_type']])) * $nb_roles);
		$position += array_search($res['idrole'], array_keys($list_roles)) + 1;
		$listgroupesroles[$res['idgroupe']][$position] = $res;
	}
}
?>
</tr><tr>
<?php foreach ($list_model as $model_tp) {
		foreach ($model_tp as $model) { ?>
			<th colspan="<?php echo $nb_roles;?>" class="titresimple"><?php echo $model['name'];?></th>
<?php } } ?>
</tr><tr>
<?php for ($i=1; $i <= $nb_col; $i++)
{
	foreach($list_roles as $role)
	{ ?>
		<th class="titresimple"><?php echo $role['name'];?></th>
<?php }
} 
?> 
</tr><tr>
<?php foreach($listgroupesroles as $idgroupe => $roles) { ?>
<th class="cellulesimple" title="<?php echo $ref->getGroupeById($idgroupe)['name'];?>"><?php echo $ref->getGroupeById($idgroupe)['name']; ?></th>
	<?php for ($i = 1; $i <= $nb_col * $nb_roles; $i++) { 
		if (isset($roles[$i]) && $roles[$i]['active'] != NULL) {
			if ($roles[$i]['active'] == 'O') { 
		?>
			<th class="cellulesimple">
			<select style="width:4em" name="selectrole" id="<?php echo 'selectrole'.$idgroupe.'_'.$i; ?>" onchange="changerole('<?php echo $idgroupe;?>', '<?php echo $i;?>');">
				<option value="O" selected="selected"><?php echo 'O';?></option>
				<option value="N"><?php echo 'N';?></option>
			</select>
			</th>
			<?php } else { ?>
				<th class="cellulesimple">			
				<select style="width:4em" name="selectrole" id="<?php echo 'selectrole'.$idgroupe.'_'.$i; ?>" onchange="changerole('<?php echo $idgroupe;?>', '<?php echo $i;?>');">
					<option value="O"><?php echo 'O';?></option>
					<option value="N" selected="selected"><?php echo 'N';?></option>
				</select></th>
			<?php } }
		else 
		{
			?>
			<th class="cellulesimple">			
				<select style="width:4em" name="selectrole" id="<?php echo 'selectrole'.$idgroupe.'_'.$i; ?>" onchange="changerole('<?php echo $idgroupe;?>', '<?php echo $i;?>');">
					<option value="" selected="selected">&nbsp;</option>
					<option value="O"><?php echo 'O';?></option>
					<option value="N"><?php echo 'N';?></option>
				</select></th>
		<?php } }
		?>
</tr>
<?php } ?>
</table>

 <form name="formselectrole" action="manage_role.php" method="post">
	<input type="hidden" name='userid' value='<?php echo $userid;?>'>
	<input type="hidden" name='idgrouperole' id='idgrouperole' value=''>
	<input type="hidden" name='role' id='role' value=''>
	<input type="hidden" name='value' id='value' value=''>
	<input id="submitrole" type="submit" value=''>
</form>	 

</body>
</html>


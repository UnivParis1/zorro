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
function changerole(uid, i){
	//alert(uid+' '+i+' ');
	document.getElementById('uidrole').value = uid;
	document.getElementById('role').value = i;
	var role = 'selectrole'+uid+i;
	document.getElementById('value').value = document.getElementById(role).value;
	document.getElementById('value').form.submit();
}
</script>

<div> Gestion des autorisations </div><br>
<?php 	// Récupération des utilisateurs



$select_allusers = "SELECT
	u.iduser,
    u.uid,
    r.idrole,
    r.name as rolename,
    ur.idmodel,
    m.name as modelname,
	dt.iddecree_type,
    dt.name as decreetypename,
    ur.active
FROM
	user u
    LEFT JOIN user_role ur
		ON u.iduser = ur.iduser
	LEFT JOIN role r
		ON r.idrole = ur.idrole
    LEFT JOIN model m
		ON m.idmodel = ur.idmodel
	LEFT JOIN decree_type dt
		ON dt.iddecree_type = dt.iddecree_type";

$ref = new reference($dbcon, $rdbApo);
$list_decree_type = $ref->getListDecreeType();
$list_roles = $ref->getListRole();
$nb_roles = sizeof($list_roles);

if (isset($_POST['uidrole']))
{
	//var_dump($_POST);
	$uidrole = $_POST['uidrole'];
	//echo "<br>";
	//var_dump($list_decree_type);
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
	$idrole = $nb_roles + $pos_model_in_type - $model*$nb_roles;
	$active = $_POST['value'];
	$updateuser = new user($dbcon, $uidrole);
	$updateuserid = $updateuser->getid();
	$updater = new user($dbcon, $userid);
	$updaterid = $updater->getid();
	$role = array();
	$role[] = array('idmodel' => $model, 'idrole'=> $idrole, 'createuserid' => $updaterid, 'updateuserid' => $updaterid, 'active' => $active);
	//echo "<br>Enregistrer la valeur $active pour l'utilisateur $uidrole le type $decreetype modele $model et le role $idrole <br>";
	//echo "<br>setroles() ".var_dump($role)."<br>";
	$updateuser->setRoles($role);
}
	
$nb_col = 0;
$list_model = array();
?> 
<table class="tableausimple"><tr><th rowspan="3" class="titresimple">Utilisateur</th>
<?php foreach($list_decree_type as $id_dt => $decree_type)
{
	$list_model[$id_dt] = $ref->getListModel($id_dt); 
	$nb_col += sizeof($list_model[$id_dt]); ?> 
	<th colspan="<?php echo sizeof($list_model[$id_dt]) * $nb_roles;?>" class="titresimple"><?php echo $decree_type['name'];?></th>
<?php 	
} 
$listusersroles = array();
$result = mysqli_query($dbcon, $select_allusers);
if (mysqli_error($dbcon))
{
	elog("Erreur a l'execution de la requete du prochain numero d'arrete.");
}
else {
	
	while ($res = mysqli_fetch_assoc($result))
	{
		// emplacement du droit dans le tableau somme(iddecree_type * nb_model) + somme(idmodel * nb_roles) + pos(role, list_roles)
		$position = array_search($res['iddecree_type'], array_keys($list_decree_type)) * $list_decree_type[$res['iddecree_type']]['nb_model'];
		$position += (array_search($res['idmodel'], array_keys($list_model[$res['iddecree_type']])) * $nb_roles);
		$position += array_search($res['idrole'], array_keys($list_roles)) + 1;
		$listusersroles[$res['uid']][$position] = $res;
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
<?php foreach($listusersroles as $uid => $roles) { ?>
<th class="cellulesimple" title="<?php echo $ldap->getInfos($uid)['displayname'];?>"><?php echo $uid; ?></th>
	<?php for ($i = 1; $i <= $nb_col * $nb_roles; $i++) { 
		if (isset($roles[$i])) {
			if ($roles[$i]['active'] == 'O') { 
		?>
			<th class="cellulesimple">
			<select style="width:4em" name="selectrole" id="<?php echo 'selectrole'.$uid.$i; ?>" onchange="changerole('<?php echo $uid;?>', '<?php echo $i;?>');">
				<option value="O" selected="selected"><?php echo 'O';?></option>
				<option value="N"><?php echo 'N';?></option>
			</select>
			</th>
			<?php } else { ?>
				<th class="cellulesimple">			
				<select style="width:4em" name="selectrole" id="<?php echo 'selectrole'.$uid.$i; ?>" onchange="changerole('<?php echo $uid;?>', '<?php echo $i;?>');">
					<option value="O"><?php echo 'O';?></option>
					<option value="N" selected="selected"><?php echo 'N';?></option>
				</select></th>
			<?php } }
		else 
		{
			?>
			<th class="cellulesimple">			
				<select style="width:4em" name="selectrole" id="<?php echo 'selectrole'.$uid.$i; ?>" onchange="changerole('<?php echo $uid;?>', '<?php echo $i;?>');">
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
	<input type="hidden" name='uidrole' id='uidrole' value=''>
	<input type="hidden" name='role' id='role' value=''>
	<input type="hidden" name='value' id='value' value=''>
	<input id="submitrole" type="submit" value=''>
</form>	 

</body>
</html>


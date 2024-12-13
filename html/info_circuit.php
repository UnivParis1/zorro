<?php
    include './include/casconnection.php';    
    require_once ('./include/fonctions.php');
    require_once ('./class/decree.php');
    require_once ('./include/dbconnection.php');
    require_once ('./class/user.php');
    require_once ('./class/model.php');
    require_once ('./class/reference.php');
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
    
    $esignatureid = null;
    $error = "";
    if (isset($_POST["structure"]) && $_POST["structure"] != '"')
    {
        $structure = $_POST["structure"];
        //echo "structure : ";
        //var_dump($structure);
    }
    if (isset($_POST['selectarrete']))
    {
    	$post_selectarrete = $_POST['selectarrete'];
        //echo "modèle :";
        //var_dump($post_selectarrete);
    }
    
    $menuItem = 'menu_circuit';
    require ("include/menu.php");
    if (isset($_SESSION['phpCAS']) && array_key_exists('user', $_SESSION['phpCAS']))
    {
        $userCAS = new user($dbcon, $_SESSION['phpCAS']['user']);
        $decreesSign = $userCAS->getDecreesBy(array('esign' => 1), -1);
        if ($userCAS->isSuperAdmin())
        {
            $ldap = new ldap();
            $models = $ref->getListModel();
            foreach ($models as $idmodel => $infos)
            {
                $model = new model($dbcon, $idmodel);
                $listModels[] = $model->getModelInfo();
            } ?>
            
<div id="contenu1">
    <h2> Signataires circuit </h2>
    <div class="gauche">
        <div class='recherche'>
            <form name='infocircuit' method='post' action='info_circuit.php'>
                <select style="width:26em" name="selectarrete" id="selectarrete">
                <?php
                if (!isset($post_selectarrete)) { ?>
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
                    <?php } if ((isset($post_selectarrete) && $post_selectarrete == $model['idmodel']) || (isset($mod_select_decree) && $access && $mod_select_decree['idmodel'] == $model['idmodel'])) { ?>
                            <option value="<?php echo $model['idmodel'];?>" selected="selected" <?php echo $color;?>><?php echo $model['name'];?></option>
                            <?php } else { ?>
                            <option value="<?php echo $model['idmodel'];?>" <?php echo $color;?>><?php echo $model['name'];?></option>
                <?php } } ?>
                </optgroup>
            </select>
    <?php
            findGroup("structure");
            if (isset($structure))
            {
                $structurename = $ldap->getStructureInfos($structure)['superGroups'][$structure]['name'];?>
                <script>document.getElementById('structure_ref').value = "<?php echo $structurename;?>";</script>
                <script>document.getElementById('structure').value = "<?php echo $structure; ?>";</script>
            <?php
            }
            echo "<input type='hidden' name='userid' value='" . $userid . "'>";
            echo "<input type='submit' value='Soumettre' >";
            echo "</form>";
            echo "</div>";
        }

        if (isset($post_selectarrete) && isset($structure))
        {
            $model = new model($dbcon, $post_selectarrete);
            $responsables = $model->getModelWorkflow();
            $etape = 0;
            foreach($responsables as $res)
            {
                if ($res['idetape'] > $etape)
                {
                    echo "<br><B>Etape ".$res['idetape']."</B><br>";
                    $etape = $res['idetape'];
                }
                switch ($res['recipient_type']) {
					case 'email':
						echo $res['recipient_default_value']."<br>";
						break;
					case 'role':
						// récupérer les personnes associées au role $res['recipient_default_value'] et pour chacun ajouter son email
						$roles = $ldap->getStructureResp($structure);
						switch ($res['recipient_default_value']) {
							case 'RA':
								foreach ($roles as $role)
								{
									if ($role['role'] == 'Responsable administratif' || $role['role'] == 'Responsable administrative')
									{
										echo $role['mail']."<br>";
									}
								}
								break;
							case 'RESP':
								foreach ($roles as $role)
								{
									if ($role['role'] == 'Responsable')
									{
										echo $role['mail']."<br>";
									}
								}
								break;
							case 'DIR':
								foreach ($roles as $role)
								{
									if ($role['role'] == 'Directeur' || $role['role'] == 'Directrice')
									{
										echo $role['mail']."<br>";
									}
								}
								break;
							case 'ALL':
								foreach ($roles as $role)
								{
									echo $role['mail']."<br>";
								}
								break;
							default:
								break;
						}
						break;
					case 'group':
						// récupérer les membres de la structure $res['recipient_default_value'] et pour chacun ajouter son email
						$emails = $ldap->getEmailsForGroupUsers($res['recipient_default_value']);
                        echo "Structure : ".$res['recipient_default_value']."<br>";
						foreach ($emails as $email)
						{
							echo $email."<br>";
						}
						break;
					case 'creator':
						echo $ref->getUserMail()."<br>";
						break;
					default:
						break;
				}
            }
        } ?>
        </div>
    </div>
<?php } else { ?>
<div id="contenu1">
	<h2> Accès interdit </h2>
</div>
<?php } ?>
</body>
</html>



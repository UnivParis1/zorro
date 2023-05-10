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

    foreach ($alldecrees as $decree) {
        $objdecree = new decree($dbcon, null, null, $decree['iddecree']);
        $nom_aff = $objdecree->getFileNameAff();?>
    <tr>
        <?php if ($decree['number'] == 0) {?>
            <td><input type="hidden" class="postid" id="<?php echo $decree['iddecree'];?>"></td>
        <?php } else {?>
            <td class="cellulesimple"><input type="hidden" class="postid" id="<?php echo $decree['iddecree'];?>"><?php echo $decree['year'].'-'.$decree['number'];?></td>
        <?php } ?>
        <!--  <td class="cellulesimple"><a href="create_decree.php?num=<?php echo $decree['number'];?>&year=<?php echo $decree['year'];?>"><?php echo $decree['decreetypename'].' '.$decree['modelname']; ?></a></td>-->
        <td class="cellulesimple" title="<?php echo $objdecree->getFileName(); ?>"><a href="create_decree.php?id=<?php echo $decree['iddecree'];?>"><?php echo $nom_aff; ?></a></td>
        <td class="cellulesimple"><?php echo $decree['modelname']; ?></td>
        <td class="cellulesimple"><?php echo $ldap->getStructureName($decree['structure']); ?></td>
        <td class="cellulesimple"><?php echo $ldap->getDisplayName($decree['uid']); ?></td>
        <td class="cellulesimple date"><?php echo date('d/m/Y',strtotime($decree['majdate'])); ?></td>
        <?php
        $aff_statut = $objdecree->getStatusAff();?>
        <td class="<?php echo $aff_statut['class'];?>" title="<?php echo $aff_statut['title'];?>"><?php echo $aff_statut['contenu']; ?></td>
        <?php if ($objdecree->getIdEsignature() != NULL) { ?>
                <td><a href="<?php echo 'info_signature.php?esignatureid='.$objdecree->getIdEsignature(); ?>"><?php echo $objdecree->getIdEsignature();?></a></td>
            <?php } else { ?>
                <td></td>
        <?php } ?>
    </tr>
<?php } ?>

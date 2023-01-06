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
        <td class="cellulesimple"><?php echo $decree['uid']; ?></td>
        <td class="cellulesimple date"><?php echo date('d/m/Y',strtotime($decree['majdate'])); ?></td>
        <?php
        $status = $decree['status'];
        $majdate = $decree['majdate'];
        if ($status == STATUT_EN_COURS || $status == STATUT_CORBEILLE)
        {
            $mod_decree = new decree($dbcon, null, null, $decree['iddecree']);
            $status = $mod_decree->getStatus(false);
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

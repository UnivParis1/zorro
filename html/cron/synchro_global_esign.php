<?php
	require_once dirname(__FILE__,2).'/include/dbconnection.php';
	require_once dirname(__FILE__,2).'/class/decree.php';
	require_once dirname(__FILE__,2).'/class/reference.php';

    echo date("d/m/Y H:i:s")." début de la synchronisation globale. \n";
	$ref = new reference($dbcon, $rdbApo);
    $listdecree = $ref->getAllDecreeEnCoursSign();
    echo sizeof($listdecree)." demandes à synchroniser.\n";
    foreach($listdecree as $decree)
    {
        echo "statut de la demande ".$decree->getid()." avant synchro fin de circuit eSignature : ".$decree->getStatus(false)."\n";
        $decree->synchroEsignatureStatus($decree->getStatus(false));
        $newstatus = $decree->getStatus(false);
        echo "statut de la demande ".$decree->getid()." après synchro fin de circuit eSignature : ".$newstatus."\n";
        if ($newstatus == STATUT_REFUSE)
        {
            $comment = $decree->getRefuseComment(true);
            echo "commentaire de refus : ".$comment."\n";
        }
        elseif ($newstatus == STATUT_EN_COURS)
        {
            $step = $decree->getSignStep(true);
            echo "étape en cours : ".$step."\n";
        }
    }
    echo date("d/m/Y H:i:s")." fin de la synchronisation globale.\n";
?>
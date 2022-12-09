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
        echo "statut de la demande ".$decree->getid()." après synchro fin de circuit eSignature : ".$decree->getStatus(false)."\n";
    }
    echo date("d/m/Y H:i:s")." fin de la synchronisation globale.\n";
?>
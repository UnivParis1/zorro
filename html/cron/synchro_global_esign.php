<?php
	require_once ('../include/dbconnection.php');
	require_once ('../class/decree.php');
	require_once ('../class/reference.php');

    elog("début de la synchronisation globale");
	$ref = new reference($dbcon, $rdbApo);
    $listdecree = $ref->getAllDecreeEnCoursSign();
    foreach($listdecree as $decree)
    {
        elog ("statut de la demande ".$decree->getid()." avant synchro fin de circuit eSignature : ".$decree->getStatus(false));
        $decree->synchroEsignatureStatus($decree->getStatus(false));
        elog ("statut de la demande ".$decree->getid()." après synchro fin de circuit eSignature : ".$decree->getStatus(false));
    }
    elog("fin de la synchronisation globale");
?>
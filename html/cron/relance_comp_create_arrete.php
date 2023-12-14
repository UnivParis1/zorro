<?php
	require_once dirname(__FILE__,2).'/include/dbconnection.php';
	require_once dirname(__FILE__,2).'/class/reference.php';
	require_once dirname(__FILE__,2).'/class/user.php';

    echo date("d/m/Y H:i:s")." début de la relance création. \n";
    $user = new user($dbcon, CRON_USER);
    $ref = new reference($dbcon, $rdbApo);
    $list_comp = $ref->getListComp();
    $list_model = $ref->getListModel(1, true);
    if (isset($_GET['comp']))
    {
        $list_comp = array($list_comp[$_GET['comp']]);
    }
    if (isset($_GET['model']))
    {
        $list_model = array($list_model[$_GET['model']]); 
    }
    $year = (date('mm') >= '09') ? date('Y') : date('Y') - 1;
    $list_edit = $ref->getListDecreeStatusForCompModel($user, $list_comp, $list_model, $year);
    echo "<br>".date("d/m/Y H:i:s")." fin de la relance création.<br>";
?>
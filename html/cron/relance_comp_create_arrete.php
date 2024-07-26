<?php
	require_once dirname(__FILE__,2).'/include/dbconnection.php';
	require_once dirname(__FILE__,2).'/class/reference.php';
	require_once dirname(__FILE__,2).'/class/user.php';

    echo date("d/m/Y H:i:s")." début de la relance création. \n";
    $user = new user($dbcon, CRON_USER);
    $ref = new reference($dbcon, $rdbApo);
    $list_comp = array_column($ref->getListComp(), null, 'code');
    $param_cron = $ref->getParamsCron(1); // idcron = 1
    $jour_today = date("l");
    $date_today = date("Y-m-d");
    $decree_type = 1; // Composition de jury
    foreach ($param_cron as $cron)
    {
        if ($cron['cron_begin'] <= $date_today && $cron['cron_end'] >= $date_today)
        {
            if ($cron['cron_day'] == $jour_today)
            {
                // cron params de la forme dtype=*iddecree_type_choisi*;comp=*code_composante_choisi*;model=*idmodel_choisi*
                $list_params = explode(";",$cron['cron_params']);
                $p = array();
                foreach($list_params as $param)
                {
                    $temp = explode('=', $param);
                    $p[$temp[0]] = $temp[1];
                }
                if (key_exists('dtype', $p))
                {
                    $decree_type = $p['dtype'];
                }
                $list_model = $ref->getListModel($decree_type, true);
                if (key_exists('comp', $p))
                {
                    $list_comp = array($list_comp[$p['comp']]);
                }
                if (key_exists('model', $p))
                {
                    $list_model = array($p['model'] => $list_model[$p['model']]);
                }
                $year = NULL;
                $list_edit = $ref->getListDecreeStatusForCompModel($user, $list_comp, $list_model, $year);
            }
        }
    }
    echo "<br>".date("d/m/Y H:i:s")." fin de la relance création.<br>";
?>
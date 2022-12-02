<?php
	require_once ('../include/dbconnection.php');
	require_once ('../class/decree.php');
	require_once ('../class/reference.php');

	$ref = new reference($dbcon, $rdbApo);

    switch ($_SERVER['REQUEST_METHOD'])
    {
        case 'GET':
            elog(var_export($_GET, true));
            if (array_key_exists("signRequestId", $_GET) || array_key_exists("esignatureid", $_GET))  // Synchronisation d'un document avec le statut de eSignature
            {
                elog("synchronisation du statut du document");
                $status = "";
                $reason = "";
                $esignatureid = isset($_GET["signRequestId"]) ? $_GET["signRequestId"] : $_GET["esignatureid"];
                if ("$esignatureid" == "")
                {
                    $erreur = "Le paramètre esignature n'est pas renseigné.";
                    $result_json = array('status' => 'Error', 'description' => $erreur);
                    elog(" ERROR => " . $erreur);
                }
                else
                {
                    elog("id esignature :".$esignatureid);
                    if ($ref->idEsignatureExists($esignatureid))
                    {
                        $decree = $ref->getDecreeByIdEsignature($esignatureid);
                        if ($decree !== false)
                        {
                            elog ("statut de la demande ".$decree->getid()." avant synchro fin de circuit eSignature : ".$decree->getStatus(false));
                            $decree->synchroEsignatureStatus($decree->getStatus(false));
                            elog ("statut de la demande ".$decree->getid()." après synchro fin de circuit eSignature : ".$decree->getStatus(false));
                            $result_json = array('status' => 'Ok', 'description' => $erreur);
                        }
                        else
                        {
                            $result_json = array('status' => 'Error', 'description' => "Demande eSignature inconnue de Zorro.");
                        }
                    }
                }
            }
            else
            {
                $erreur = "Mauvais usage du WS mode GET => Le paramètre doit être : signRequestId ou esignatureid";
                elog("$erreur");
                $result_json = array('status' => 'Error', 'description' => $erreur);
            }
            break;
        case 'POST':
            $erreur = "Le mode POST n'est pas supporté dans ce WS";
            $result_json = array('status' => 'Error', 'description' => $erreur);
            elog(" Appel du WS en mode POST => Erreur = " . $erreur);
            break;
    }

    // headers for not caching the results
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    // headers to tell that result is JSON
    header('Content-type: application/json');
    // send the result now
    echo json_encode($result_json);
?>
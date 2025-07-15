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
    if (isset($_POST["esignatureid"]))
    {
        $esignatureid = $_POST["esignatureid"];
    }
    if (isset($_GET["esignatureid"]))
    {
        $esignatureid = $_GET["esignatureid"];
    }
    
    $menuItem = 'menu_signature';
    require ("include/menu.php");
    if (isset($_SESSION['phpCAS']) && array_key_exists('user', $_SESSION['phpCAS']))
    { ?>
        <div id="contenu1">
        <h2> Suivi eSignature </h2>
<?php   $userCAS = new user($dbcon, $_SESSION['phpCAS']['user']);
        $decreesSign = $userCAS->getDecreesBy(array('esign' => 1), -1);
        if ($userCAS->isSuperAdmin())
        {
            echo "<div class='recherche'>";
            echo "<form name='infosignature'  method='post' action='info_signature.php' >";
            echo "<select name='esignatureid' id='esignatureid'>";
            if ($esignatureid == NULL)
            {
                echo "<option value='' selected='selected'>Arrêté</option>";
            }
            else
            {
                echo "<option value=''>Arrêté</option>";
            }
            foreach ($decreesSign as $decree) 
            {
                if ($esignatureid != NULL && $esignatureid == $decree['idesignature'])
                {
                    echo "<option value='".$decree['idesignature']."' selected='selected'>".$decree['filename']."</option>";
                }
                else
                {
                    echo "<option value='".$decree['idesignature']."'>".$decree['filename']."</option>";
                }
            }
            echo "</select>";
            echo "<input type='hidden' name='userid' value='" . $userid . "'>";
            echo "<input type='submit' value='Soumettre' >";
            echo "</form>";
            echo "</div>";
        }

        if (!is_null($esignatureid))
        {
            //echo "L'identifiant eSignature du document est $esignatureid <br>";
            // Vérifier l'existence de l'identifiant en base
            $iddecree = $ref->idEsignatureExists($esignatureid);
            if (!$iddecree)
            {
                echo "Ce numéro ne provient pas de Zorro ! <br>";
            }
            else
            {
                $decree = new decree($dbcon, null, null, $iddecree);
                if ($userCAS->hasAccessDecree($decree->getDecree()))
                {
                    $create_date = new DateTime($decree->getCreateDate());
                    echo "Document Zorro : <a href='create_decree.php?id=" . $decree->getid() . "' target='_blank'>" . $decree->getFileNameAff() . "</a><br>";
                    echo "Date de création sur Zorro : ".$create_date->format("d/m/Y H:i:s")."<br><br>";
                    echo "Document sur eSignature : <a href='".$decree->getEsignUrl()."' target='_blank'>Lien</a> <br>";
                    $error = '';
                    $curl = curl_init();
                    $params_string = "";
                    $opts = array (
                        CURLOPT_URL => ESIGNATURE_BASE_URL . ESIGNATURE_CURLOPT_URL_GET_SIGNREQ . $esignatureid,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_PROXY => ''
                    );
                    curl_setopt_array($curl, $opts);
                    curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                    $json = curl_exec($curl);
                    $error = curl_error ($curl);
                    curl_close($curl);
                    if ($error != "")
                    {
                        elog(" Erreur Curl =>  " . $error);
                        echo "Anomalie lors de la récupération de la demande de signature.<br>";
                    }
                    $response = json_decode($json, true);
            
                    //echo "<br><pre>";
                    elog(var_export($response, true));
                    //echo "</pre><br>";
            
                    if (is_null($response))
                    {
                        $erreur = "La demande de signature est introuvable. <br>";
                        elog(" $erreur");
                        echo "<br>$erreur <br>";
                    }
                    elseif (isset($response['error']))
                    {
                        $erreur = "La demande de signature est en erreur. <br>";
                        elog(" $erreur");
                        echo "<br>$erreur <br>";
                    }
                    else // Tout est ok => on va récupérer les données du workflow
                    {
                        echo "Créateur de la demande sur eSignature : " . $response["parentSignBook"]["createBy"]["firstname"] . " " . $response["parentSignBook"]["createBy"]["name"] . "<br>";
                        //echo "Date de création : " . date("d/m/Y H:i:s", substr($response["parentSignBook"]["createDate"],0,strlen($response["parentSignBook"]["createDate"])-3)) . " (Valeur brute : " . $response["parentSignBook"]["createDate"] . ")<br>";
                        if (!is_int($response['parentSignBook']['createDate']))
                        {
                            $date = new DateTime($response['parentSignBook']['createDate'], new DateTimeZone('UTC'));
                            $date->setTimezone(new DateTimeZone('Europe/Paris'));
                            $displaydate = $date->format("d/m/Y H:i:s");
                        }
                        else
                        {
                            // FORMAT /1000 pour ôter les millisecondes
                            $displaydate = date("d/m/Y H:i:s", intdiv($response['parentSignBook']['createDate'], 1000));
                        }
                        echo "Date de création sur eSignature : " . $displaydate . "<br>";//. " (Valeur brute : " . $response["parentSignBook"]["createDate"] . ")<br>";
                        echo "<br>";
                        $nextstep = null;
                        $table = array();
                        $rowspan = 1;
                        foreach ($response["parentSignBook"]["liveWorkflow"]["liveWorkflowSteps"] as $numstep => $step)
                        {
                            $signedstep = false;
                           // echo "<B>Etape " . ($numstep+1) . " : ".$step['workflowStep']["description"]."</B><br>";
                            $etape = 'Etape '. ($numstep+1) . " : ".$step['workflowStep']["description"];
                            $table[$etape] = array();
                            $rowspan = $rowspan < sizeof($step["recipients"]) ? sizeof($step["recipients"]) : $rowspan;
                            foreach ($step["recipients"] as $esignatureuser)
                            {
                                $usersigned = false;
                                if ($esignatureuser["signed"])
                                {
                                    //echo " <span style='color:green'>";
                                    $usersigned = true;
                                    if ($decree->getSignStep() == 'Visa de la composante' && $decree->getNbSignForStep() == 1)
                                    {
                                        $signedstep = false;
                                        $signstepencours = true;
                                    }
                                    else
                                    {
                                        $signedstep = true;
                                    }
                                }
                              //  echo "&emsp;". $esignatureuser['user']['firstname'] . " " . $esignatureuser['user']['name'] . "<a href='".ANNUAIRE_URL.$esignatureuser['user']['email'] ."' target='_blank'>👤</a>" ."<br>";
                                $table[$etape][] = array('signe' => $usersigned, 'signataire' => $esignatureuser['user']['firstname'] . " " . $esignatureuser['user']['name'] . "<a href='".ANNUAIRE_URL.$esignatureuser['user']['email'] ."' target='_blank'>👤</a>");
                                if ($esignatureuser["signed"])
                                {
                                  //  echo " </span>";
                                }
                            }
                            if ($signedstep==false and is_null($nextstep))
                            {
                                $nextstep = $numstep;
                            }
                        }
                        echo "<B>Statut de la demande </B>: " . $decree->getStatusAff()['title'] . "<br><br>";
                        echo "<B>Circuit de signature</B> <br>";
                        echo "<table>";
                        echo "<tr rowspan=\"$rowspan\">";
                        $etapecourante = 1;
                        foreach($table as $step => $participants)
                        {
                            if ($etapecourante <= $nextstep || $nextstep === null)
                            {
                                echo "<th style=\"background-color:#99ff99\" title='validé'>";
                            }
                            else
                            {
                                if ($etapecourante == 1  && isset($signstepencours))
                                {
                                    echo "<th style=\"background-color:#ffcc66\" title='validation partielle'>";
                                }
                                else
                                {
                                    echo "<th style=\"background-color:#ff6666\" title='en attente'>";
                                }
                            }
                            echo "$step<br>";
                            foreach ($participants as $participant)
                            {
                                if ($participant["signe"])
                                {
                                    echo " <font color='green'>";
                                }
                                echo $participant["signataire"]."<br>";
                                if ($participant["signe"])
                                {
                                    echo " </font>";
                                }
                            }
                            $etapecourante++;
                            echo "<th><th colspan=\"5\"> </th>";
                        }
                        echo "</tr>";
                        echo "</table><br>";

                        if (!is_null($nextstep) and ($response["parentSignBook"]["status"]=='pending'))
                        {   // On affiche les infos de l'étape suivante si la demande n'est pas terminée
                           /* $currentstep = $response["parentSignBook"]["liveWorkflow"]["liveWorkflowSteps"][$nextstep];
                            echo "<B>En attente de l'étape : " . ($nextstep+1) . "</B><br>";
                            foreach ((array)$currentstep['recipients'] as $recipient)
                            {
                                echo "&emsp;" . $recipient['user']['firstname'] . " " . $recipient['user']['name'] . "<a href='".ANNUAIRE_URL.$recipient['user']['email'] . "' target='_blank'>👤</a>" . "<br>";
                                //echo "&emsp;Nom de l'étape : " . $currentstep['workflowStep']["description"] . "<br>";
                            }*/
                        }
                        else
                        {
                            echo "<B>Circuit terminé</B><br>";
                            if ($response["parentSignBook"]["status"]=='exported')
                            {
                                // Afficher un lien vers l'export nuxeo
                                //echo "<a href='".NUXEO_PREFIX.substr($decree->getExportPath(), 7)."/".$decree->getFileName()."' target='_blank'>Lien vers l'export Nuxeo</a> <br>";
                            }
                            if (isset($response["parentSignBook"]["endDate"]))
                            {
                                if (!is_int($response['parentSignBook']['endDate']))
                                {
                                    $date = new DateTime($response['parentSignBook']['endDate'], new DateTimeZone('UTC'));
                                    $date->setTimezone(new DateTimeZone('Europe/Paris'));
                                    $displaydatefin = $date->format("d/m/Y H:i:s");
                                }
                                else
                                {
                                    // FORMAT /1000 pour ôter les millisecondes
                                    $displaydatefin = date("d/m/Y H:i:s", intdiv($response['parentSignBook']['endDate'], 1000));
                                }
                                echo "Date de fin : " . $displaydatefin ."<br>";// . " (Valeur brute : " . $response["parentSignBook"]["endDate"] . ")<br>";
                            }
                            else
                            {
                                echo "Pas de date de fin définie. <br>";
                            }
                        }
                        echo "<br><br>";
                    }

                    // On appelle le WS eSignature pour récupérer le document correspondant au document
                    $curl = curl_init();
                    $opts = array(
                        CURLOPT_URL => ESIGNATURE_BASE_URL . ESIGNATURE_LAST_FILE . $esignatureid,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_PROXY => ''
                    );
                    curl_setopt_array($curl, $opts);
                    curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                    $pdf = curl_exec($curl);
                    $error = curl_error ($curl);
                    curl_close($curl);
                    if ($error != "")
                    {
                        $error = "Erreur Curl (récup PDF) =>  " . $error;
                        elog(" $error");
                        echo "Le document PDF n'a pas pu être récupéré. <br><br>";
                    }
                    if (stristr(substr($pdf,0,10),'PDF') === false)
                    {
                        $error = "Le WS n'a pas retourné un fichier PDF";
                        $error = "Erreur Curl (récup PDF) =>  " . $error;
                        elog(" $error");
                        echo "Le document PDF n'a pas pu être récupéré. <br><br>";
                    }

                    if ($error == '')
                    {
                        $encodage = base64_encode($pdf);

                    //echo "On affiche dans l'iFrame le document de la demande eSignature : $esignatureid <br><br>";
                        echo '<iframe src=data:application/pdf;base64,' . $encodage . ' width="100%" height="500px">';
                        echo "</iframe>";

                    }
                } else { ?>
                <div id="contenu1">
                    <h2> Accès interdit </h2>
                </div>
                <?php }
            }
        }
        ?>
    </div>
<?php } else { ?>
<div id="contenu1">
	<h2> Accès interdit </h2>
</div>
<?php } ?>
</body>
</html>



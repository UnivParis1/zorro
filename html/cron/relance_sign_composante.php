<?php
	require_once dirname(__FILE__,2).'/include/dbconnection.php';
	require_once dirname(__FILE__,2).'/class/decree.php';
	require_once dirname(__FILE__,2).'/class/reference.php';

    echo date("d/m/Y H:i:s")." début de la relance. \n";
	$ref = new reference($dbcon);
    $listdecree = $ref->getAllDecreeEnCoursSign();
    echo sizeof($listdecree)." demandes en cours de signature.\n";
    $emails = array();
    foreach($listdecree as $decree)
    {
        echo "statut de la demande ".$decree->getid()." avant synchro fin de circuit eSignature : ".$decree->getStatus(false)."<br>";
        $decree->synchroEsignatureStatus($decree->getStatus(false));
        $newstatus = $decree->getStatus(false);
        echo "statut de la demande ".$decree->getid()." après synchro fin de circuit eSignature : ".$newstatus."<br>";
        if ($newstatus == STATUT_REFUSE)
        {
            $comment = $decree->getRefuseComment(true);
            echo "commentaire de refus : ".$comment."<br>";
        }
        elseif ($newstatus == STATUT_EN_COURS)
        {
            $step = $decree->getSignStep(true);
            if ($step == 'Visa de la composante')
            {
                // récupérer les emails des utilisateurs dont on attend la signature
                $emails = array_merge($emails, $decree->getWaitingSign());
            }
            echo "étape en cours : ".$step."<br>";
        }
    }
    echo "emails : ";
    $u_emails = array_unique($emails);
    foreach ($u_emails as $ue) 
    {
        echo $ue."/n";
    }

    if (MODE_TEST == 'O')
    {
        // Paramétrer sendmail_FROM
        ini_set('sendmail_from', 'zorro@univ-paris1.fr');
        ini_set('SMTP', 'smtp.univ-paris1.fr');
        // Envoyer un email de relance
        $message = "<html>
                        <head>
                    
                        <meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">
                        <title></title>
                        </head>
                        <body>
                        <p>Bonjour,</p>
                        <p>Des arrêtés attendent une validation de votre part. <br>
                        Veuillez consulter votre parapheur électronique <a
                            href=\"".ESIGNATURE_BASE_URL.ESIGNATURE_SIGNBOOK."\">eSignature</a> dans votre ENT.</p>
                        <p>Cordialement,</p>
                        <p style='font-size:15px;'>Message automatique envoyé par l'application <a href=\"".URL_BASE_ZORRO."\">Zorro</a> de gestion des arrêtés<br>
                        </p>
                        </body>
                    </html>";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=UTF-8";
        $headers[] = "Reply-To: noreply@univ-paris1.fr";
        foreach ($u_emails as $mail)
        {
            if (mail($mail, "Des arrêtés attendent votre validation.", $message, implode("\r\n",$headers)))
            {
                echo "Email envoyé avec succès à ".$mail;
            }
            else
            {
                echo "Echec de l'envoi de mail à ".$mail;
            }
        }
    }
    echo date("d/m/Y H:i:s")." fin de la relance composante.<br>";
?>
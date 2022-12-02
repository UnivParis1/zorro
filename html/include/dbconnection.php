<?php

require_once dirname(__FILE__,2).'/include/const.php';
require_once dirname(__FILE__,2).'/include/fonctions.php';
	
global $rdbApo;
global $dbcon;

// Connexion à la base de données applicative
if (! $dbcon) {
	$dbcon = mysqli_connect(ZORRO_DB_HOST, ZORRO_DB_USER, ZORRO_DB_PWD);
	if (! $dbcon) {
		echo "Impossible d'effectuer la connexion à la base de données";
		exit();
	}
	mysqli_select_db($dbcon, ZORRO_DB_SCHEMA) or die("La sélection de la base a échoué");
	mysqli_query($dbcon, "set names utf8;");
	//mysqli_query($dbcon, "SET sql_mode='NO_ZERO_DATE'");
	$sql = "SELECT @@SESSION.sql_mode session";
	$query = mysqli_query($dbcon, $sql);
	$erreur = mysqli_error($dbcon);
	if ($erreur != "")
	{
		$errlog = "Erreur lors du chargement du sql_mode de session : " . $erreur;
		elog($errlog);
		mysqli_query($dbcon, "SET sql_mode='NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER'");
	}
	if (mysqli_num_rows($query) == 0)
	{
		$errlog = "Impossible de charger le sql_mode de session : " . $erreur;
		elog($errlog);
		mysqli_query($dbcon, "SET sql_mode='NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER'");
	}
	else
	{
		$result = mysqli_fetch_row($query);
		$sql_mode = $result[0];
		$sql_mode= str_replace('STRICT_TRANS_TABLES', '', $sql_mode);
		mysqli_query($dbcon, "SET sql_mode='$sql_mode'");
		//echo "Le sql_mode est : $sql_mode <br><br>";
	}
}
// CRÉATION DES CONSTANTES
$select_constant = "SELECT name, value FROM constant";
$query_constant = mysqli_query($dbcon, $select_constant);
if (!mysqli_error($dbcon))
{
	while ($row = mysqli_fetch_assoc($query_constant))
	{
		if (!defined($row['name']))
			define($row['name'], $row['value']);
	}
}
else
{
	$errlog = "Impossible de charger les constantes : " . $erreur;
	elog($errlog);
}

// Recuperation des parametres de connexion a Apogee (bdd Oracle)

if (!$rdbApo)
{
	
	$rdbApo = oci_pconnect(DB_APO_USER, DB_APO_PWD, DB_APO_HOST.':1521/'.DB_APO_DATABASE,"AL32UTF8"); 
	
	//if ($rdbApo == false)
	//	die("Connexion ".DB_APO_DATABASE." impossible ".OCIError($rdbApo)."\n");
		
}

if ($rdbApo !== false)
{
	$sql = "SELECT cod_anu FROM ANNEE_UNI WHERE eta_anu_iae = 'O'";
	$sth = oci_parse($rdbApo, $sql);
	$retour = '';
	if (oci_error($rdbApo))
	{
		elog("Erreur à la préparation de la requête COD_ANU");
	}
	else
	{
		oci_execute($sth);
		if (!oci_error($rdbApo))
		{
			if ($row = oci_fetch_assoc($sth))
			{
				$retour = $row['COD_ANU'];
			}
		}
		else
		{
			elog("Erreur à l\'exécution de la requête COD_ANU.");
			elog('Erreur : '.var_export(oci_error($rdbApo), true));
		}
		//echo "L'année universitaire de référence dans Apogée est $retour. <br> ";
	}
}


?>
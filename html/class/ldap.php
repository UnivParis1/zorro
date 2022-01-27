<?php

require_once './include/const.php';
require_once './include/fonctions.php';


class ldap {
	
	private $_con_ldap;
	
	function __construct()
	{
		$con_ldap = ldap_connect(LDAP_SERVER);
		ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		$this->_con_ldap = $con_ldap;
	}
	
	/*function isMemberOf($groupname)
	{
		$retour = FALSE;
		$uid = phpCAS::getUser();
		$r = ldap_bind($this->_con_ldap, LDAP_BIND_LOGIN, LDAP_BIND_PASS);
		$filtre = "(uid=$uid)";

		$dn = LDAP_SEARCH_BASE;
		$restriction = array(
				LDAP_MEMBER_ATTR
		);
		$sr = ldap_search($this->_con_ldap, $dn, $filtre, $restriction);
		$info = ldap_get_entries($this->_con_ldap, $sr);
		// echo "<br>Info = " . print_r($info,true) . "<br>";
		// Si l'utilisateur est au moins dans un groupe
		if (isset($info[0][$restriction[0]])) {
			if (in_array($groupname, $info[0][$restriction[0]])) {
				// L'utilisateur est dans le groupe recherché, on peut continuer
				$retour = TRUE;
			} else {
				$errlog = "Le groupe $groupname n'est pas défini pour l'utilisateur) !!!";
				elog($errlog);
			}
		}    // Pas de groupe pour cet utilisateur => On doit s'arréter
		else {
			$errlog = "L'utilisateur ne fait parti d'aucun groupe LDAP....";
			elog($errlog);
		}
		return $retour;
	}*/
	
	
	// récupérationd des noms, prénoms, adresse email, structure d'affectation
	function getInfos($uid)
	{
		$r = ldap_bind($this->_con_ldap);
		$filtre = "(uid=$uid)";
		$attributs = array('uid', 'displayName', 'mail', 'supannEntiteAffectation');
		$result = ldap_search($this->_con_ldap, "ou=people,dc=univ-paris1,dc=fr", $filtre, $attributs);
		$entries = ldap_get_entries($this->_con_ldap, $result);
		$infos_ldap = array('displayname' => $entries[0]['displayname'][0],
				'mail' => $entries[0]['mail'][0],
				'supannentiteaffectation' => $entries[0]['supannentiteaffectation'][0]);
		$result2 = ldap_search($this->_con_ldap, "ou=structures,dc=univ-paris1,dc=fr", "(supannCodeEntite=".$entries[0]['supannentiteaffectation'][0].")", array('ou', 'supannRefId')); 
		$entries2 = ldap_get_entries($this->_con_ldap, $result2);
		$infos_ldap['ou'] = $entries2[0]['ou'][0];
		$supannrefid = $entries2[0]['supannrefid'];
		foreach($supannrefid as $value)
		{
			if (substr($value, 0, 12) == '{APOGEE.CMP}')
			{
				$infos_ldap['supannrefid'] = substr($value, 12);
			}
		}
		foreach ($infos_ldap as $cle => $valeur)
		{
			$_SESSION[$cle] = $valeur;
		}
		return $infos_ldap;
	}
	
	
	function setUser($uid)
	{
		$_SESSION['uid'] = $uid;
		unset($_SESSION['issuperadmin']);
	}
}

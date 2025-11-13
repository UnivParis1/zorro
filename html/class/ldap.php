<?php

require_once dirname(__FILE__,2).'/include/const.php';
require_once dirname(__FILE__,2).'/include/fonctions.php';


class ldap {
	
	private $_con_ldap;
	
	function __construct()
	{
		$con_ldap = ldap_connect(LDAP_SERVER);
		ldap_set_option($con_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		$this->_con_ldap = $con_ldap;
	}
	
	function isMemberOf($uid, $groupname)
	{
		$retour = FALSE; 
		$r = ldap_bind($this->_con_ldap, LDAP_BIND_LOGIN, LDAP_BIND_PASS);
		$filtre = "member=uid=$uid,".LDAP_SEARCH_BASE_PEOPLE;
		
		$base = "cn=$groupname,".LDAP_SEARCH_BASE_GROUPS;
		$restriction = array(
				'cn'
		);
		$sr = ldap_search($this->_con_ldap, $base, $filtre, $restriction);
		$info = ldap_get_entries($this->_con_ldap, $sr);
		// echo "<br>Info = " . print_r($info,true) . "<br>";
		// Si l'utilisateur est au moins dans un groupe
		if (isset($info[0][$restriction[0]])) 
		{
			$retour = TRUE;
		}
		else 
		{
			$errlog = "L'utilisateur $uid ne fait pas parti du groupe $groupname...";
			elog($errlog);
		}
		return $retour;
	}
	
	
	// récupération des noms, prénoms, adresse email, structure d'affectation
	function getInfos($uid, $temUserApp = true)
	{
		$r = ldap_bind($this->_con_ldap, LDAP_BIND_LOGIN, LDAP_BIND_PASS);
		$filtre = "(uid=$uid)";
		$attributs = array('uid', 'displayName', 'mail', 'supannEntiteAffectation');
		$result = ldap_search($this->_con_ldap, LDAP_SEARCH_BASE_PEOPLE, $filtre, $attributs);
		$entries = ldap_get_entries($this->_con_ldap, $result);
		$infos_ldap = array();
		if (key_exists('count', $entries) && $entries['count'] > 0)
		{
			elog($filtre);
			$affectation = array_key_exists('supannentiteaffectation', $entries[0]) ? $entries[0]['supannentiteaffectation'][0] : null;
			$infos_ldap = array('displayname' => $entries[0]['displayname'][0],
					'mail' => $entries[0]['mail'][0],
					'supannentiteaffectation' => $affectation
			);
			if ($affectation != null)
			{
				$result2 = ldap_search($this->_con_ldap, LDAP_SEARCH_BASE_STRUCTURES, "(supannCodeEntite=".$affectation.")", array('ou', 'description', 'supannRefId')); 
				$entries2 = ldap_get_entries($this->_con_ldap, $result2);
				$infos_ldap['ou'] = $entries2[0]['ou'][0];
				$infos_ldap['description'] = $entries2[0]['description'][0];
				$supannrefid = $entries2[0]['supannrefid'];
				foreach($supannrefid as $value)
				{
					if (substr($value, 0, 12) == '{APOGEE.CMP}')
					{
						$infos_ldap['supannrefid'] = substr($value, 12);
					}
				}
			}
			else
			{
				elog("L'utilisateur $uid n'a pas d'affectation.");
				$infos_ldap['ou'] = NULL;
				$infos_ldap['description'] = NULL;
				$infos_ldap['supannrefid'] = NULL;
			}
			if ($temUserApp)
			{
				foreach ($infos_ldap as $cle => $valeur)
				{
					$_SESSION[$cle] = $valeur;
				}
			}
		}
		else
		{
			elog("Utilisateur $uid inconnu du ldap.");
		}
		return $infos_ldap;
	}
	
	
	function setUser($uid)
	{
		$_SESSION['uid'] = $uid;
		unset($_SESSION['issuperadmin']);
		unset($_SESSION['groupes']);
		unset($_SESSION['affectation']);
		unset($_SESSION['roles']);
		unset($_SESSION['subsuper']);
	}
	
	function getSupannCodeEntiteFromAPO($codApogee)
	{
		// ldapsearch -b ou=structures,dc=univ-paris1,dc=fr '(supannRefId={APOGEE.CMP}02)' supannCodeEntite
		$result = ldap_search($this->_con_ldap, LDAP_SEARCH_BASE_STRUCTURES, "(supannRefId={APOGEE.CMP}$codApogee)", array("supannCodeEntite"));
		$entries = ldap_get_entries($this->_con_ldap, $result);
		if ($entries !== FALSE && $entries['count'] > 0)
		{
			return $entries[0]['supanncodeentite'][0];
		}
		else
		{
			return NULL;
		}
	}
	
	function getStructureResp($structure)
	{
		// $structure est le supannCodeEntite 
		$retour = array();
		if (substr($structure, 0, 11) == 'structures-')
		{
			$structure = substr($structure, 11);
		}
		$curl = curl_init();
		$params = array('key' => 'structures-'.$structure, 'attrs' => 'roles');
		$walk = function( $item, $key, $parent_key = '' ) use ( &$output, &$walk ) {
			is_array( $item )
			? array_walk( $item, $walk, $key )
			: $output[] = http_build_query( array( $parent_key ?: $key => $item ) );
			
		};
		array_walk( $params, $walk );
		$params_string = implode( '&', $output );
		$curl_opt_url = WSGROUPS_URL.WSGROUPS_URL_GROUP.'?'.$params_string;
		//echo "<br>Output = " . $params_string . '<br><br>';
		
		$opts = array(
				CURLOPT_URL => $curl_opt_url,
				CURLOPT_POST => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_PROXY => ''
		);
		curl_setopt_array($curl, $opts);
		$json = curl_exec($curl);
		$error = curl_error ($curl);
		curl_close($curl);
		if ($error != "")
		{
			elog( "Erreur Curl = " . $error );
		}
		//print_r2($json);
		$tab = json_decode($json, true);
		if (is_array($tab) && key_exists('roles', $tab))
		{
			$roles = $tab['roles'];
			foreach($roles as $role)
			{
				if (!key_exists('mail', $role))
				{
					if ($role['uid'] != 'supannListeRouge')
					{
						$ldap_infos = $this->getInfos($role['uid'], false);
						$retour[$role['uid']] = array('uid' => $role['uid'], 'name' => $role['displayName'], 'mail' => $ldap_infos['mail'], 'role' => array_key_exists("supannRoleGenerique", $role) ? $role["supannRoleGenerique"][0] : '');
					}
				}
				else 
				{
					$retour[$role['uid']] = array('uid' => $role['uid'], 'name' => $role['displayName'], 'mail' => $role['mail'], 'role' => array_key_exists("supannRoleGenerique", $role) ? $role["supannRoleGenerique"][0] : '');
				}
			}
		}
		//print_r2($retour);
		return $retour;
	}

	function getStructureInfos($structure)
	{
		$retour = array();
		$curl = curl_init();
		if (substr($structure, 0, 11) == 'structures-')
		{
			$structure = substr($structure, 11);
		}
		$params = array('key' => 'structures-'.$structure, 'depth' => '10', 'filter_category' => 'structures');
		$walk = function( $item, $key, $parent_key = '' ) use ( &$output, &$walk ) {
			is_array( $item )
			? array_walk( $item, $walk, $key )
			: $output[] = http_build_query( array( $parent_key ?: $key => $item ) );
		};
		array_walk( $params, $walk );
		$params_string = implode( '&', $output );
		$curl_opt_url = WSGROUPS_URL.WSGROUPS_SUBSUPER_GROUPS."?".$params_string;
		//echo "<br>Output = " . $params_string . '<br><br>';

		$opts = array(
				CURLOPT_URL => $curl_opt_url,
				CURLOPT_POST => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_PROXY => ''
		);
		curl_setopt_array($curl, $opts);
		$json = curl_exec($curl);
		$error = curl_error ($curl);
		curl_close($curl);
		if ($error != "")
		{
			elog( "Erreur Curl = " . $error );
		}
		//print_r2($json);
		$tab = json_decode($json, true);
		if (is_array($tab) )
		{
			$retour = $tab;
		}
		if (isset($retour['superGroups']))
		{
			$retour['codeapo'] = array();
			foreach($retour['superGroups'] as $structuremere => $descrStruct)
			{
				$codeApo = $this->getInfoApo($structuremere);
				if ($codeApo != '')
				{
					$retour['codeapo'][] = $codeApo;
					break;
				}
			}
			if (sizeof($retour['subGroups']) > 0)
			{
				if (sizeof($retour['codeapo']) > 0)
				{
					$retour['codeapo'][] = $this->getCodeApoSubGroups($retour['subGroups']);
				}
				else
				{
					$retour['codeapo'] = $this->getCodeApoSubGroups($retour['subGroups']);
				}
				//$retour['subGroups'][$structurefille]['roles'] = $this->getStructureResp($descrStruct['key']);
			}
			foreach($retour['superGroups'] as $structuremere => $descrStruct)
			{
				$retour['superGroups'][$structuremere]['roles'] = $this->getStructureResp($structuremere);
			}
			foreach($retour['subGroups'] as $structurefille => $descrStruct)
			{
				$retour['subGroups'][$structurefille]['roles'] = $this->getStructureResp($descrStruct['key']);
			}
		}
		//print_r2($retour);
		return $retour;
	}

	function getUserAndStructureInfos($uid, $temUserApp = true)
	{
		$retour = array();
		$curl = curl_init();
		$curl_opt_url = WSGROUPS_URL.WSGROUPS_SEARCH_USERTRUSTED."?filter_uid=".$uid;

		$opts = array(
				CURLOPT_URL => $curl_opt_url,
				CURLOPT_POST => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_PROXY => ''
		);
		curl_setopt_array($curl, $opts);
		$json = curl_exec($curl);
		$error = curl_error ($curl);
		curl_close($curl);
		if ($error != "")
		{
			elog( "Erreur Curl = " . $error );
		}
		//print_r2($json);
		$tab = json_decode($json, true);
		//print_r2($tab);
		if (is_array($tab))
		{
			//print_r2($tab);
			$retour['affectation'] = isset($tab[0]['supannEntiteAffectationPrincipale-all']) ? $tab[0]['supannEntiteAffectationPrincipale-all'] : array();
			$retour['mail'] = isset($tab[0]['mail']) ? $tab[0]['mail'] : '';
			$retour['roles'] = isset($tab[0]['supannEntiteAffectationPrincipale-all']['key']) ? $this->getStructureResp($tab[0]['supannEntiteAffectationPrincipale-all']['key']): array();
			$retour['subsuper'] = isset($tab[0]['supannEntiteAffectationPrincipale-all']['key']) ? $this->getStructureInfos($tab[0]['supannEntiteAffectationPrincipale-all']['key']): array();
			if (isset($retour['subsuper']['superGroups']))
			{
				$retour['codeapo'] = '';
				foreach($retour['subsuper']['superGroups'] as $structuremere => $descrStruct)
				{
					$codeApo = $this->getInfoApo($structuremere);
					if ($codeApo != '')
					{
						$retour['codeapo'] = $codeApo;
						$retour['structcodeapo'] = $structuremere;
						break;
					}
				}
			}
		}
		if ($temUserApp)
		{
			foreach ($retour as $cle => $valeur)
			{
				$_SESSION[$cle] = $valeur;
			}
		}
		//print_r2($retour);
		return $retour;
	}

	function getInfoApo($structure)
	{
		if (substr($structure, 0, 11) == 'structures-')
		{
			$structure = substr($structure, 11);
		}
		$codeapo = '';
		$r = ldap_bind($this->_con_ldap);
		$result = ldap_search($this->_con_ldap, LDAP_SEARCH_BASE_STRUCTURES, "(supannCodeEntite=".$structure.")", array('supannRefId'));
		$entries = ldap_get_entries($this->_con_ldap, $result);
		$supannrefid = $entries[0]['supannrefid'];
		foreach($supannrefid as $value)
		{
			if (substr($value, 0, 12) == '{APOGEE.CMP}')
			{
				$codeapo = substr($value, 12);
			}
		}
		return $codeapo;
	}

	function getCodeApoSubGroups($subgroups)
	{
		$tabApo = array();
		foreach($subgroups as $subgroup)
		{
			if (key_exists('subGroups', $subgroup))
			{
				$tmp = $this->getCodeApoSubGroups($subgroup['subGroups']);
				if (sizeof($tabApo) > 0)
				{
					$tabApo[] = $tmp;
				} 
				else
				{
					$tabApo = $tmp;
				}
			}
			$codApo = $this->getInfoApo($subgroup['key']);
			if ($codApo != '')
			{
				$tabApo[] = $codApo;
			}
		}
		return $tabApo;
	}

	function getEtuInfos($uid)
	{
		$retour = array();
		$curl = curl_init();
		$curl_opt_url = WSGROUPS_URL.WSGROUPS_SEARCH_USERTRUSTED."?filter_uid=".$uid;
		$opts = array(
				CURLOPT_URL => $curl_opt_url,
				CURLOPT_POST => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_PROXY => ''
		);
		curl_setopt_array($curl, $opts);
		$json = curl_exec($curl);
		$error = curl_error ($curl);
		curl_close($curl);
		if ($error != "")
		{
			elog( "Erreur Curl = " . $error );
		}
		//print_r2($json);
		$tab = json_decode($json, true);
		//print_r2($tab);
		if (is_array($tab) && sizeof($tab) > 0)
		{
			//print_r2($tab);
			if (array_key_exists('supannEntiteAffectationPrincipale-all',$tab[0]) && array_key_exists('description',$tab[0]['supannEntiteAffectationPrincipale-all']))
			{
				$retour['infoetu'] = $tab[0]['supannEntiteAffectationPrincipale-all']['description'];
				if (array_key_exists('supannEtuInscription-all',$tab[0]) && is_array($tab[0]['supannEtuInscription-all']))
				{
					foreach($tab[0]['supannEtuInscription-all'] as $inscription)
					{
						// Affichage de toutes les étapes
						//if (array_key_exists('affect', $inscription) && array_key_exists('key', $tab[0]['supannEntiteAffectationPrincipale-all']) 
						//		&& $inscription['affect'] == $tab[0]['supannEntiteAffectationPrincipale-all']['key'] && array_key_exists('etape', $inscription))
						//{
							$retour['infoetu'] = htmlspecialchars($inscription['etape']).' - '.$retour['infoetu'];
						//}
					}
				}
			}
			$retour['nometu'] = array_key_exists('sn', $tab[0]) ? $tab[0]['sn'] : '';
			$retour['prenometu'] = array_key_exists('givenName', $tab[0]) ? $tab[0]['givenName'] : '';
			$retour['displayname'] = array_key_exists('displayName', $tab[0]) ? $tab[0]['displayName'] : '';
		}
		$r = ldap_bind($this->_con_ldap, LDAP_BIND_LOGIN, LDAP_BIND_PASS);
		$filtre = "(uid=$uid)";
		$attributs = array('uid', 'supannEtuId');
		if (!is_array($tab) || sizeof($tab) == 0)
		{
			$attributs = array('uid', 'supannEtuId','supannEntiteAffectationPrincipale', 'supannEtuEtape', 'sn', 'givenName', 'displayName');
		}
		$result = ldap_search($this->_con_ldap, LDAP_SEARCH_BASE_PEOPLE, $filtre, $attributs);
		$entries = ldap_get_entries($this->_con_ldap, $result);
		//elog(var_export($entries, true));
		if (sizeof($entries) > 0)
		{
			if (array_key_exists('supannetuid', $entries[0]))
			{
				$retour['numetu'] = $entries[0]['supannetuid'][0];
			}
			if (!is_array($tab) || sizeof($tab) == 0)
			{
				$retour['nometu'] = array_key_exists('sn', $entries[0]) ? $entries[0]['sn'][0] : '';
				$retour['prenometu'] = array_key_exists('givenname', $entries[0]) ? $entries[0]['givenname'][0] : '';
				$retour['displayname'] = array_key_exists('displayname', $entries[0]) ? $entries[0]['displayname'][0] : '';
				// TODO : Liste Rouge...
				$retour['infoetu'] = array_key_exists('supannentiteaffectationprincipale', $entries[0]) ? $entries[0]['supannentiteaffectationprincipale'][0] : '';
				$retour['infoetu'] = array_key_exists('supannetuetape', $entries[0]) ? $entries[0]['supannetuetape'][0].' - '.$retour['infoetu'] : $retour['infoetu'];
				$nbins = sizeof($entries[0]['supannetuetape']);
				if (array_key_exists('supannetuetape', $entries[0]) &&  $nbins > 2)
				{
					for($i = 1; $i < $nbins - 1; $i++)
					{
						$retour['infoetu'] = $entries[0]['supannetuetape'][$i].' - '.$retour['infoetu'];
					}
				}
			}
		}
		//elog(var_export($retour, true));
		//print_r2($retour);
		return $retour;
	}

	function getStructureName($supannCodeEntite)
	{
		if (substr($supannCodeEntite, 0, 11) != 'structures-')
		{
			$supannCodeEntite = 'structures-'.$supannCodeEntite;
		}
		$retour = '';
		$curl = curl_init();
		$curl_opt_url = WSGROUPS_URL.WSGROUPS_URL_GROUP.'?key='.$supannCodeEntite;
		$opts = array(
				CURLOPT_URL => $curl_opt_url,
				CURLOPT_POST => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_PROXY => ''
		);
		curl_setopt_array($curl, $opts);
		$json = curl_exec($curl);
		$error = curl_error ($curl);
		curl_close($curl);
		if ($error != "")
		{
			elog( "Erreur Curl = " . $error );
		}
		//print_r2($json);
		$tab = json_decode($json, true);
		//print_r2($tab);
		if (is_array($tab))
		{
			//print_r2($tab);
			$retour = $tab['name'];
		}
		//print_r2($retour);
		return $retour;
	}

	function getNomCourtStruct($structure)
	{
		$nom_court = NULL;
		$r = ldap_bind($this->_con_ldap, LDAP_BIND_LOGIN, LDAP_BIND_PASS);
		$supannCodeEntite = $structure;
		if (substr($supannCodeEntite, 0, 11) == 'structures-')
		{
			$supannCodeEntite = substr($supannCodeEntite, 11);
		}
		$result = ldap_search($this->_con_ldap, LDAP_SEARCH_BASE_STRUCTURES, "(supannCodeEntite=".$supannCodeEntite.")", array('ou'));
		$entries = ldap_get_entries($this->_con_ldap, $result);
		if (is_array($entries) && array_key_exists(0, $entries) && array_key_exists('ou', $entries[0]))
		{
			$nom_court = $entries[0]['ou'][0];
		}
		return $nom_court;
	}

	function getEmailsForGroupUsers($structure)
	{
		$emails = array();
		if (substr($structure, 0, 11) != 'structures-')
		{
			$structure = 'structures-'.$structure;
		}
		$curl = curl_init();
		$curl_opt_url = WSGROUPS_URL.WSGROUPS_URL_GROUP_USERS.'?key='.$structure;
		$opts = array(
				CURLOPT_URL => $curl_opt_url,
				CURLOPT_POST => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_PROXY => ''
		);
		curl_setopt_array($curl, $opts);
		$json = curl_exec($curl);
		$error = curl_error ($curl);
		curl_close($curl);
		if ($error != "")
		{
			elog( "Erreur Curl = " . $error );
		}
		$tab = json_decode($json, true);
		if (is_array($tab))
		{
			foreach($tab as $uid)
			{
				$mail = $this->getEmailUser($uid['uid']);
				if ($mail != '')
				{
					$emails[] = $mail;
				}
			}
		}
		return $emails;
	}

	function getEmailUser($uid)
	{
		$r = ldap_bind($this->_con_ldap, LDAP_BIND_LOGIN, LDAP_BIND_PASS);
		$filtre = "(uid=$uid)";
		$attributs = array('uid','mail');
		$result = ldap_search($this->_con_ldap, LDAP_SEARCH_BASE_PEOPLE, $filtre, $attributs);
		$entries = ldap_get_entries($this->_con_ldap, $result);
		if (sizeof($entries) > 0 && $entries['count'] > 0 && key_exists('mail', $entries[0]))
		{
			return $entries[0]['mail'][0];
		}
		return '';
	}

	function getDisplayName($uid)
	{
		$retour = array();
		$curl = curl_init();
		$curl_opt_url = WSGROUPS_URL.WSGROUPS_SEARCH_USERTRUSTED."?filter_uid=".$uid;
		$opts = array(
				CURLOPT_URL => $curl_opt_url,
				CURLOPT_POST => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_PROXY => ''
		);
		curl_setopt_array($curl, $opts);
		$json = curl_exec($curl);
		$error = curl_error ($curl);
		curl_close($curl);
		if ($error != "")
		{
			elog( "Erreur Curl = " . $error );
		}
		//print_r2($json);
		$tab = json_decode($json, true);
		//print_r2($tab);
		if (is_array($tab) && sizeof($tab) > 0)
		{
			if (isset($tab[0]['displayName']))
			{
				return $tab[0]['displayName'];
			}
		}
		return $uid;
	}
}

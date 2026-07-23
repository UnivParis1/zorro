<?php
function elog($message)
{
	error_log(basename(__FILE__) . " " . $message."\n");
}

function findPerson($name, $id = '')
{
	echo "<input id='".$name.$id."' name='".$name.$id."' placeholder='Nom et/ou prenom'/>";
	
	echo "<script>";
	//var input_elt = $( ".token-autocomplete input" );
	echo "$( '#".$name.$id."' ).autocompleteUser(
			'".WSGROUPS_URL.WSGROUPS_SEARCH_USERCAS."', { select: completionAgent, wantedAttr: 'displayName',
			wsParams: { allowInvalidAccounts: 0, filter_eduPersonAffiliation: 'employee|staff' } });
	";
	echo "</script>";

}

function findPresident($name, $id = '')
{
	echo "<input id='".$name.$id."' name='".$name.$id."' placeholder='Nom et/ou prenom'/>";
	
	echo "<script>";
	//var input_elt = $( ".token-autocomplete input" );
	echo "$( '#".$name.$id."' ).autocompleteUser(
			'".WSGROUPS_URL.WSGROUPS_SEARCH_USERCAS."', { select: completionPresident, wantedAttr: 'uid',
			wsParams: { allowInvalidAccounts: 0, filter_eduPersonAffiliation: 'employee|staff' } });
	";
	echo "</script>";

}
function findGroup($name, $id = '')
{
	echo "<input id='".$name.$id."_ref' name='".$name.$id."_ref' placeholder='Service référent'/>";
	echo "<input type='hidden' id='".$name.$id."' name='".$name.$id."' class='".$name.$id."_ref' onchange='majComposante(this)'/>";
	?> <button type="button" id="<?php echo $name.$id.'_effacer';?>" name="<?php echo $name.$id.'_effacer';?>" onclick="getElementById('<?php echo $name.$id.'_ref';?>').value='';getElementById('<?php echo $name.$id;?>').value='';return false;">x</button>
	<?php echo "<script>";
	echo "$( '#".$name.$id."_ref' ).autocompleteGroup(
			'".WSGROUPS_URL.WSGROUPS_SEARCH_GROUP."', { select: completionStructure, wantedAttr: 'key',
			wsParams: { filter_category: 'structures' } });
	";
	echo "</script>";
}

function findStudent($name, $id = '')
{
	echo "<input id='".$name.$id."_ref' name='".$name.$id."_ref' placeholder='Nom et/ou prenom'/>";
	echo "<input type='hidden' id='".$name.$id."' name='".$name.$id."' class='".$name.$id."_ref' onchange='majEtudiant(this)'/>";
	echo "<script>";
	//var input_elt = $( ".token-autocomplete input" );
	echo "$( '#".$name.$id."_ref' ).autocompleteUser(
			'".WSGROUPS_URL.WSGROUPS_SEARCH_USERCAS."', { select: completionStudent, wantedAttr: 'uid',
			wsParams: { allowInvalidAccounts: 0, filter_eduPersonAffiliation: 'student|alum' } });
	";
	echo "</script>";
}

function print_r2($val){
	echo '<pre>';
	print_r($val);
	echo  '</pre>';
}

function prepared_query($mysqli, $sql, $params, $types = "")
{
	$types = $types ?: str_repeat("s", count($params));
	$stmt = $mysqli->prepare($sql);
	$stmt->bind_param($types, ...$params);
	$stmt->execute();
	return $stmt;
}

function prepared_select($mysqli, $sql, $params = [], $types = "") {
	return prepared_query($mysqli, $sql, $params, $types)->get_result();
}

function odt_to_pdf($file)
{
	$message = '';
	// CONVERSION EN PDF
	$descriptorspec = array(
			0 => array("pipe", "r"),  // stdin
			1 => array("pipe", "w"),  // stdout
			2 => array("pipe", "w"),  // stderr
	);
	if (isset($_SERVER['SystemRoot']) && strpos($_SERVER['SystemRoot'], 'WINDOWS') !== false)
	{
		$process = proc_open("python.exe \"C:\Program Files\Unoconv\unoconv-0.8.2\unoconv\" --doctype=document --format=pdf \"".$file."\"", $descriptorspec, $pipes);
	}
	else
	{
		$process = proc_open("unoconv --doctype=document --format=pdf \"".$file."\"", $descriptorspec, $pipes);
	}
	$stdout = stream_get_contents($pipes[1]);
	fclose($pipes[1]);

	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[2]);
	if ($stdout != "")
	{
		elog( "stdout : \n");
		elog($stdout);
		elog( "La création du document PDF a échoué. <br>");
		$message .= "<p class='alerte alerte-danger'>La création du document a échoué.</p>";
	}
	elseif ($stderr != "")
	{
		elog( "stderr :\n");
		elog($stderr);
		elog( "La création du document PDF a échoué. <br>");
	}
	else
	{
		$message .= "<p class='alerte alerte-success'>Document enregistré.</p>";
	}
	return $message;
}
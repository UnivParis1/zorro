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
			'https://wsgroups.univ-paris1.fr/searchUserCAS', { select: completionAgent, wantedAttr: 'displayName',
			wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: 'employee|staff' } });
	";
	echo "</script>";

}

function findGroup($name, $id = '')
{
	echo "<input id='".$name.$id."_ref' name='".$name.$id."_ref' placeholder='Service référent'/>";
	echo "<input type='hidden' id='".$name.$id."' name='".$name.$id."' class='".$name.$id."_ref'/>";
	echo "<script>";
	echo "$( '#".$name.$id."_ref' ).autocompleteGroup(
			'https://wsgroups.univ-paris1.fr/searchGroup', { select: completionAgent, wantedAttr: 'key',
			wsParams: { filter_category: 'structures' } });
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
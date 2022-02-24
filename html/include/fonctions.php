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
			'https://wsgroups.univ-paris1.fr/searchUserCAS', { disableEnterKey: true, select: completionAgent, wantedAttr: 'displayName',
			wsParams: { allowInvalidAccounts: 0, showExtendedInfo: 1, filter_eduPersonAffiliation: 'employee|staff' } });
	";
	echo "</script>";

}

function print_r2($val){
	echo '<pre>';
	print_r($val);
	echo  '</pre>';
}


<?php
//add database configuration file
require_once ("./include/dbconnection.php");
require_once ("./class/user.php");
require_once ('./class/reference.php');

$limit = 10;
$alldecrees = array();
//fetch data from database
if (isset($_GET['last_id']))
{
    $params = array();
    if (isset($_GET['idmodel']))
    {
      $post_selectarrete = $_GET['idmodel'];
		  $params['idmodel'] = $post_selectarrete;
    }
    if (isset($_GET['status']))
    {
		  $post_selectstatut = $_GET['status'];
		  $params['status'] = $post_selectstatut;
    }
    if (isset($_GET['contenu']) && $_GET['contenu'] != '')
    {
		$post_contenu = $_GET['contenu'];
		$params['contenu'] = $post_contenu;
    }
    if (isset($_GET['year']) && $_GET['year'] != '')
    {
		$post_year = $_GET['year'];
		$params['year'] = $post_year;
    }
    if (isset($_GET['number']) && $_GET['number'] != '')
    {
		$post_number = $_GET['number'];
		$params['findnum'] = $post_number;
    }
    if (isset($_GET['allcomp']) && $_GET['allcomp'] != '')
    {
		$allcomp = $_GET['allcomp'];
		$params['allcomp'] = $allcomp;
    }
    $userid = $_GET['userid'];
    $user = new user($dbcon, $userid);
    $alldecrees = $user->getDecreesBy($params, 20, intval($_GET['last_id']), $_GET['orderby'], $_GET['desc']);
}
include('display_decrees.php');
?>

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
    $userid = $_GET['userid'];
    $user = new user($dbcon, $userid);
    $alldecrees = $user->getDecreesBy($params, 20, intval($_GET['last_id']), $_GET['orderby'], $_GET['desc']);
}
include('display_decrees.php');
?>

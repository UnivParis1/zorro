<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.8" />


<link rel="stylesheet"
	href="<?php echo WSGROUPS_URL;?>web-widget/jquery-ui.css"
	type="text/css" media="all"></link>
<link rel="stylesheet"
	href="<?php echo WSGROUPS_URL;?>web-widget/ui.theme.css"
	type="text/css" media="all"></link>
<link rel="stylesheet"
	href="<?php echo WSGROUPS_URL;?>web-widget/autocompleteUser.css"
	type="text/css" media="all"></link>
<script type="text/javascript">
<?php
    if (is_null($userid)) {
        echo "PROBLEME : L'utilisateur n'est pas renseigné ==> objet \$user!!!! <br>";
        exit();
    }
    else {
    	require_once './class/user.php';
    	require_once "./include/dbconnection.php";
    	$user = new user($dbcon, $userid);
    }
    if (isset($_SESSION['phpCAS']) && array_key_exists('user', $_SESSION['phpCAS']))
    {
		$userCAS = new user($dbcon, $_SESSION['phpCAS']['user']);
    }
    require_once('./class/ldap.php');
    $ldap = new ldap();
    require_once('./class/reference.php');
    $ref = new reference($dbcon, $rdbApo);

?>

	function montre(id)
	{
		var d = document.getElementById(id);
		for (var i = 1; i<=10; i++)
		{
			if (document.getElementById('smenuprincipal'+i))
			{
				document.getElementById('smenuprincipal'+i).style.display='none';
			}
		}
		if (d)
		{
			d.style.display='block';
		}
	}

	function cache(id, e)
	{
		var toEl;
		var d = document.getElementById(id);
		if (window.event)
			toEl = window.event.toElement;
		else if (e.relatedTarget)
			toEl = e.relatedTarget;
		if ( d != toEl && !estcontenupar(toEl, d) )
			d.style.display="none";
	}

// retourne true si oNode est contenu par oCont (conteneur)
	function estcontenupar(oNode, oCont)
	{
		if (!oNode)
			return; // ignore les alt-tab lors du hovering (empêche les erreurs)
		while ( oNode.parentNode )
		{
			oNode = oNode.parentNode;
			if ( oNode == oCont )
				return true;
		}
		return false;
	}

/* Demande d'affichage d'une fenetre au niveau du front office */
	function ouvrirFenetrePlan(url, nom) 
	{
   	window.open(url, nom, "width=520,height=500,scrollbars=yes, status=yes");
	}

</script>


<script type="text/javascript">window.prolongation_ENT_args = { current:'arrete' };</script>
<script type="text/javascript"
	src="https://ent.univ-paris1.fr/ProlongationENT/loader.js"></script>
<script src="javascripts/jquery-1.8.3.js"></script>
<script src="javascripts/jquery-ui.js"></script>
<script type="text/javascript" src="javascripts/ajax.js"></script>

<link
	href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css"
	rel="stylesheet" />
<script
	src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>

<script src="<?php echo WSGROUPS_URL;?>web-widget/autocompleteUser.js"></script>
<script src="<?php echo WSGROUPS_URL;?>web-widget/autocompleteUser-resources.html.js"></script>

<script>
    var completionAgent = function (event, ui)
    {
		// NB: this event is called before the selected value is set in the "input"
		var form = $(this).closest("form");
		var selectedInput = document.activeElement;
		form.find("[id='" + selectedInput.name + "']").val(ui.item.label);
		form.find("[class='" + selectedInput.name + "']").val (ui.item.value);

		return false;
    };

    var completionStructure = function (event, ui)
    {
		// NB: this event is called before the selected value is set in the "input"
		var form = $(this).closest("form");
		var selectedInput = document.activeElement;
		form.find("[id='" + selectedInput.name + "']").val(ui.item.label);
		form.find("[class='" + selectedInput.name + "']").val (ui.item.value);
		majComposante(form.find("[class='" + selectedInput.name + "']"));
		return false;
    };

    var completionStudent = function (event, ui)
    {
		// NB: this event is called before the selected value is set in the "input"
		var form = $(this).closest("form");
		var selectedInput = document.activeElement;
		form.find("[id='" + selectedInput.name + "']").val(ui.item.label);
		form.find("[class='" + selectedInput.name + "']").val (ui.item.value);
		majEtudiant(form.find("[class='" + selectedInput.name + "']"));
		return false;
    };
	
</script>

<!-- On rend la CSS "dynamique" en lui passant en paramètre le timestamp Unix de dernière modification du fichier -->
<!-- Donc à chaque changement de CSS, on force le chargement de la nouvelle CSS -->

<link rel="stylesheet" type="text/css" href="style/jquery-ui.css"
	media="screen"></link>
	<link rel="stylesheet" type="text/css"
	href="style/style.css?<?php echo filemtime('style/style.css')  ?>"
	media="screen"></link>
</head>
<?php if (!isset($menuItem)) { $menuItem = ''; }?>
<body>
	<div class="containerApp"> 
		<header id="header-zorro">
			<nav class="navigat" >
				<ul >
				<?php if ($ref->getModeMaintenance()) { ?>
					<li>MAINTENANCE</li>
				<?php } elseif (MODE_TEST == 'O') { ?>
					<li>MODE TEST</li>
					<?php } if ($rdbApo == false && $user->isSuperAdmin()) { ?>
					<li class="error">PROBLEME CONNEXION APOGEE</li>
					<?php } if ($user->isSuperAdmin() || (isset($_SESSION['groupes']) && sizeof($_SESSION['groupes']) > 0) || $user->isAdmin() || $user->isDaji()) { ?>
					<li id='menu_create' <?php  echo ($menuItem == 'menu_create') ? "class='navcourant'" : '';?> >
						<a href="create_decree.php">Nouveau document</a>
					</li>
					<li id='menu_manage' <?php echo ($menuItem == 'menu_manage') ? "class='navcourant'" : '';?> >
						<a href="manage_decree.php">Mes documents</a>
					</li>
					<?php } ?>
					<?php /*if ($user->isSuperAdmin() || $user->isDaji() || $user->isAdminModel()) { */?>
					<li id='menu_stat' <?php echo ($menuItem == 'menu_stat') ? "class='navcourant'" : '';?> >
						<a href="stat.php">Indicateurs</a>
					</li>
					<?php /* } */
					if ($user->isSuperAdmin()) { ?>
					<li id='menu_role' <?php echo ($menuItem == 'menu_role') ? "class='navcourant'" : '';?> >
						<a href="manage_role.php">Autorisations</a>
					</li>
					<li id='menu_model' <?php echo ($menuItem == 'menu_model') ? "class='navcourant'" : '';?> >
						<a href="manage_model.php">Modèles</a>
					</li>
					<?php }
					if ($user->isSuperAdmin() || $menuItem == 'menu_signature') { ?>
					<li id='menu_signature' <?php echo ($menuItem == 'menu_signature') ? "class='navcourant'" : '';?> >
						<a href="info_signature.php">Suivi eSignature</a>
					</li>
					<?php } ?>
					<?php if (isset($userCAS) && $userCAS->isSuperAdmin(false)) { ?>
					<li id='menu_admin' <?php echo ($menuItem == 'menu_admin') ? "class='navcourant'" : '';?> >
						<a href="admin_substitution.php">Changer d'utilisateur (<?php echo $user->getUid();?>)</a>
					</li>	
					<?php } ?>
					<?php if ($user->isSuperAdmin()) { ?>
					<li id='menu_maintenance' <?php echo ($menuItem == 'menu_maintenance') ? "class='navcourant'" : '';?> >
						<a href="maintenance.php">Mode Maintenance</a>
					</li>
					<?php } ?>
				</ul>
			</nav>
		</header>

<?php
require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/inc.language.php");

global $siteconfig;
global $USER_SESSION;


/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('admin_tools')); 
$tpl->set('page_specific_head_content', 
	"<link rel='stylesheet' type='text/css' media='screen' href='css/admin.css' />
	");
$tpl->set('site_user', $USER_SESSION);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/admin.tpl.php');

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>
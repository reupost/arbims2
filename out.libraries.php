<?php

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");

global $siteconfig;
global $USER_SESSION;

$session = new SessionMsgHandler();
$session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('libraries')); 
$tpl->set('page_specific_head_content', 
	"<link rel='stylesheet' type='text/css' media='screen' href='css/libraries.css' />
	");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('session_msg', $session_msg);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/libraries.tpl.php');
$bdy->set('user',$USER_SESSION);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>
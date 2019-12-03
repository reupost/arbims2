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

$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('biodiversity_data')); 
$tpl->set('page_specific_head_content', 
	"<link rel='stylesheet' type='text/css' media='screen' href='css/biodiversitydata.css' />
        ");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('session_msg', $session_msg);

$bdy = new MasterTemplate('templates/biodiversitydata.tpl.php');

$tpl->set('sf_content', $bdy);

echo $tpl->fetch('templates/layoutnew.tpl.php');
?>
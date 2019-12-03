<?php
require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");
require_once("models/usertext.php");

global $siteconfig;
global $USER_SESSION;

$session = new SessionMsgHandler();
$session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);

$user_text = GetUserText('intro', $USER_SESSION['language']);

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('arbims')); 
$tpl->set('page_specific_head_content', 
	"<link rel='stylesheet' type='text/css' media='screen' href='css/index.css' />
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
        <meta http-equiv='cache-control' content='max-age=0' />
        <meta http-equiv='cache-control' content='no-cache' />
        <meta http-equiv='expires' content='0' />
        <meta http-equiv='expires' content='Tue, 01 Jan 1980 1:00:00 GMT' />
        <meta http-equiv='pragma' content='no-cache' />
	");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('session_msg', $session_msg);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/index.tpl.php');
$bdy->set('user',$USER_SESSION);
$bdy->set('user_text',$user_text);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>
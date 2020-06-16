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

$partner_text = GetUserText('partners', $USER_SESSION['language']);

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('partners'));
$tpl->set('page_specific_head_content', 
	"<link rel='stylesheet' type='text/css' media='screen' href='css/partners.css' />
	");
$tpl->set('site_user', $USER_SESSION);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/partners.tpl.php');
$bdy->set('user',$USER_SESSION);
$bdy->set('partner_text',$partner_text);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>
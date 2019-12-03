<?php

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/inc.language.php");
require_once("models/singleuser.php");
require_once("includes/sessionmsghandler.php");

global $siteconfig;
global $USER_SESSION;

if ($USER_SESSION['siterole'] != 'admin') {
    header("Location: out.index.php"); //user does not have permission to do this
    exit;
}

$params = array();
$params['id'] = GetCleanInteger(isset($_CLEAN['id'])? $_CLEAN['id'] : 0);

$session = new SessionMsgHandler();
$session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);

$singleuser = new SingleUser();
$userdetails = $singleuser->GetUser($params['id']);

if (!isset($userdetails['id'])) { //no user with this ID
    header("Location: out.listusers.php"); 
    exit;
}

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('user_edit')); 
$tpl->set('page_specific_head_content', 
	"<link rel='stylesheet' type='text/css' media='screen' href='css/useredit.css' />
	<script type='text/javascript' src='js/user_edit.js'></script>");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('session_msg', $session_msg);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/user_edit.tpl.php');
$bdy->set('userdetails', $userdetails);
$bdy->set('session_msg', $session_msg);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>
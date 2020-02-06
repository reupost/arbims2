<?php

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/inc.language.php");
require_once("models/singleuser.php");
require_once("includes/sessionmsghandler.php");

global $siteconfig;
global $USER_SESSION;

/* page options */
$params = array();

$session = new SessionMsgHandler();
$session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('register'));
$tpl->set('page_specific_head_content', '<link rel="stylesheet" type="text/css" media="screen" href="css/register.css" />
        <script type="text/javascript" src="js/password.js?v=1.9"></script> 
        <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
        <script type="text/javascript">var captcha_public_key ="' . $siteconfig['recaptcha_public_key'] . '";
        var passwords_do_not_match = "' . getMLtext('passwords_do_not_match'). '";
        var username_not_valid = "' . getMLtext('username_not_valid') . '";
        var bad_captcha = "' . getMLtext('complete_captcha') . '";</script>');
$tpl->set('site_user', $USER_SESSION);
$tpl->set('session_msg', $session_msg);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/register.tpl.php');
$bdy->set('session_msg', $session_msg);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>
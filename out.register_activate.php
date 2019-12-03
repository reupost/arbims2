<?php

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/inc.language.php");
require_once("models/singleuser.php");
require_once("includes/sessionmsghandler.php");
require_once("includes/library_synch.php");

global $siteconfig;

/* page options */
$params = array();
$key = (isset($_CLEAN['k'])? $_CLEAN['k'] : '');

/* get model for page content */
$singleuser = new SingleUser();
$activate_res = $singleuser->ActivateAccount($key);
if ($activate_res == 0) {
    $activate_msg = getMLtext('register_activate_not_found');
} elseif ($activate_res == -2) {
    $activate_msg = getMLtext('register_activate_already_activated');
} else {
    $activate_msg = getMLtext('register_activate_success');
    $userdetails = $singleuser->GetUserFromPwd($key);
    if (isset($userdetails['username'])) {
        Library_ActivateUser($userdetails['username']); //for library
    }
}

/* start with any existing logged in user session */
global $USER_SESSION;

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('register_activate'));
$tpl->set('page_specific_head_content', '');
$tpl->set('site_user', $USER_SESSION);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/register_activate.tpl.php');
$bdy->set('msg',$activate_msg);


/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');

?>
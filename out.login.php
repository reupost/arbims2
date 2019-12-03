<?php

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/inc.language.php");
require_once("models/singleuser.php");
require_once("includes/sessionmsghandler.php");
require_once("includes/library_synch.php");

global $siteconfig;
global $USER_SESSION;

/* page options */
$CLEANPOST = Sanitize($_POST);
$params = array();
$params['username'] = (isset($CLEANPOST['username'])? $CLEANPOST['username'] : '');
$params['password'] = (isset($_POST['password'])? $_POST['password'] : ''); //use raw POST data since otherwise there is a problem with accented characters
$params['reminder'] = (isset($CLEANPOST['reminder'])? $CLEANPOST['reminder'] : 0);
$params['email'] = (isset($CLEANPOST['rememail'])? $CLEANPOST['rememail'] : '');

/* get model for page content */
$singleuser = new SingleUser();
//$singleuser->CreateUser('reuben','reupost@gmail.com','123jangleRr');
$user = array();
$user['id'] = 0;
$user['username'] = '';
$user['language'] = 'fr_FR';
$user['siterole'] = 'guest';
$user['email'] = '';
$loginok = 0; //assume not a login attempt
$loginlibok = 1; //assume no library login
if ($params['reminder']) {
	$remok = $singleuser->PasswordReset($params['username'], $params['email']);	
	if (!$remok) {
		$loginok = 2;	
	} else {
		$loginok = -2;
	}
} elseif ($params['username'] != '') {	
	$loginok = $singleuser->VerifyLogin($params['username'], $params['password'], $user);
	if ($loginok == -1) {        
        //delete any existing session        
        session_destroy();
        
        //start session
        session_start();        
                
        $USER_SESSION['id'] = $user['id'];
        $USER_SESSION['username'] = $params['username'];
        $USER_SESSION['language'] = $user['language']; 
        $USER_SESSION['siterole'] = $user['siterole'];
        $USER_SESSION['email'] = $user['email'];
		$singleuser->RegisterLogin($user['id']);
        setcookie("arbmis_lang", $user['language'], null, '/');      
        //session_register("USER_SESSION");         //deprecated
        $_SESSION['USER_SESSION'] = $USER_SESSION;
        
        //now try to auto-login to library        
        $loginlibok = Library_AttemptLogin($params['username'], $params['password']);
	}
}

$session = new SessionMsgHandler();
$session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);

$params['loginok'] = $loginok;
$params['loginlibok'] = $loginlibok;

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('sign_in'));
$tpl->set('page_specific_head_content', '<link rel="stylesheet" type="text/css" media="screen" href="css/login.css" />');
$tpl->set('site_user', $user);
$tpl->set('session_msg', $session_msg);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/login.tpl.php');
$bdy->set('params',$params);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>
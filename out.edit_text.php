<?php
require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/inc.language.php");
require_once("models/singleuser.php");
require_once("includes/sessionmsghandler.php");
require_once("models/usertext.php");

global $siteconfig;
global $USER_SESSION;

if ($USER_SESSION['siterole'] != 'admin') {
    header("Location: out.index.php"); //user does not have permission to do this
    exit;
}

$params = array();
$params['key'] = (isset($_CLEAN['key'])? $_CLEAN['key'] : '');
if (!in_array($params['key'], array('albertine','lakes','mountains','intro','partners'))) {
    header("Location: out.index.php"); //invalid key
    exit;
}
$params['lang'] = $USER_SESSION['language'];

$session = new SessionMsgHandler();
$session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);

$user_text = GetUserText($params['key'], $params['lang']);

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('edit_text')); 
$tpl->set('page_specific_head_content', 
	"<link rel='stylesheet' type='text/css' media='screen' href='css/edit_text.css' />
	<script type='text/javascript' src='js/tinymce/tinymce.min.js'></script>
    <script type='text/javascript'>
        tinymce.init({
        selector: 'textarea',
        height: 300,
        plugins: [
         'link image spellchecker table code'],   
        toolbar: 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image', 
        });
    </script>");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('session_msg', $session_msg);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/edit_text.tpl.php');
$bdy->set('params', $params);
$bdy->set('user_text', $user_text);
$bdy->set('session_msg', $session_msg);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>
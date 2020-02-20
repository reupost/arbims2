<?php

require_once("includes/config.php");
require_once("lib/magpierss/rss_fetch.inc");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");

require_once("models/gbif.php");


global $siteconfig;
global $USER_SESSION;

if ($USER_SESSION['siterole'] != 'admin') {
    header("Location: out.index.php"); //user does not have permission to do this
    exit;
}

$session = new SessionMsgHandler();
$session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);


/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('synchronise_with_gbif'));
$tpl->set('page_specific_head_content', "");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('session_msg', $session_msg);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/gbif_synch.tpl.php');

$gbif = new GBIF();
$output = $gbif->InitiateGBIFDownload();

$bdy->set('output', $output);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');

?>

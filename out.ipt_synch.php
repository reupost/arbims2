<?php

require_once("includes/config.php");
require_once("lib/magpierss/rss_fetch.inc");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");

require_once("models/ipt.php");


global $siteconfig;
global $USER_SESSION;

if ($USER_SESSION['siterole'] != 'admin') {
    header("Location: out.index.php"); //user does not have permission to do this
    exit;
}

$session = new SessionMsgHandler();
$session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);


/*
 * Problem: if the page times out because of a long-running query, and the user clickes 'refresh', then the same query
 * is re-run, causing a deadlock on the DB server.
 * Fixes: 
 * optimise queries - done
 * Change time-out (make it longer) - not done
 * Semaphor to prevent simultaneous run - done 
 * Split occurrence table from occurrence_processed - done
 * From 11 min to do a taxonomic tree rebuild it now takes 30 sec.
 * Might be better to put this in an offline process, not a webpage
 */

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('synchronise_with_ipt')); 
$tpl->set('page_specific_head_content', "");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('session_msg', $session_msg);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/ipt_synch.tpl.php');

$ipt = new IPT();
$output = $ipt->UpdateIPTResources();

$bdy->set('output', $output);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');

?>

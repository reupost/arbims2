<?php

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/pager.php");
require_once("models/tableoccurrence.php");
require_once("models/tabledatasets.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");
require_once("models/listoccurrence_base.php");

$occ = new OccurrenceController('lakes');
$page = $occ->GetOccurrenceList();
echo $page;
?>
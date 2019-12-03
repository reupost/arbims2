<?php

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/pager.php");
require_once("models/tablespecies.php");
require_once("models/tabledatasets.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");
require_once("models/listspecies_base.php");

$spp = new SpeciesController('lakes');
$page = $spp->GetSpeciesList();
echo $page;
?>
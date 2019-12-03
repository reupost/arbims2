<?php
require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/pager.php");
require_once("models/tablespecies.php");
require_once("includes/inc.language.php");
require_once("models/speciestree_base.php");

$spptree = new SpeciesTreeController('lakes');
$page = $spptree->GetSpeciesTree();
echo $page;
?>
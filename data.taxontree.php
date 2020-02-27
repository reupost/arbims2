<?php
require_once("models/tablespecies.php");

//TODO: verify inputs
$region = (isset($_GET['region'])? Sanitize($_GET['region']) : '');
$taxon_epithet = (isset($_GET['taxon_epithet'])? Sanitize($_GET['taxon_epithet']) : '');
$rank = (isset($_GET['rank'])? Sanitize($_GET['rank']) : '');
$cur_link = (isset($_GET['cur_link'])? Sanitize($_GET['cur_link']) : '');

$tblspecies = new TableSpecies();
$txt = $tblspecies->GetAccordionBelow($region, $taxon_epithet, $rank, true, $cur_link);
echo $txt;
//TODO: should really return JSON or something, not raw html
?>
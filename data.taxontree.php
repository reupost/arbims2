<?php
require_once("models/tablespecies.php");

//TODO: verify inputs
$region = (isset($_GET['region'])? Sanitize($_GET['region']) : '');
$taxon_epithet = (isset($_GET['taxon_epithet'])? Sanitize($_GET['taxon_epithet']) : '');
$rank = (isset($_GET['rank'])? Sanitize($_GET['rank']) : '');
$use_backbone = (isset($_GET['use_backbone'])? (trim(strtolower($_GET['use_backbone'])) == 'true' || trim(strtolower($_GET['use_backbone'])) == 't'? true : false) : false);
$use_other = (isset($_GET['use_other'])? (trim(strtolower($_GET['use_other'])) == 'true' || trim(strtolower($_GET['use_other'])) == 't'? true : false) : false);
$use_occ = (isset($_GET['use_occ'])? (trim(strtolower($_GET['use_occ'])) == 'true' || trim(strtolower($_GET['use_occ'])) == 't'? true : false) : false);
$cur_link = (isset($_GET['cur_link'])? Sanitize($_GET['cur_link']) : '');

$tblspecies = new TableSpecies();
$txt = $tblspecies->GetAccordionBelow($region, $taxon_epithet, $rank, $use_backbone, $use_other, $use_occ, true, $cur_link);
echo $txt;
//TODO: should really return JSON or something, not raw html
?>
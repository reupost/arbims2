<?php
require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/pager.php");
require_once("models/tableoccurrence.php");
require_once("models/tabledatasets.php");
require_once("models/maplayers.php");
require_once("includes/inc.language.php");
require_once("models/maps_base.php");

$map = new MapController('mountains');
$page = $map->GetMap();
echo $page;
?>
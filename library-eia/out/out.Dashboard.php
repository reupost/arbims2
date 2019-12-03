<?php
//    MyDMS. Document Management System
//    Copyright (C) 2010 Matteo Lucarelli
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include("../inc/inc.Settings.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

require_once("SeedDMS/Preview.php");

$mostDownloaded = $dms->getDashboardListing('mostdownloaded');
$mostRecent = $dms->getDashboardListing('mostrecent');
$recommended = $dms->getDashboardListing('recommended',5); //RR if more docs are recommended...?
$rated = $dms->getDashboardListing('mostrated');

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$rootfolder = $settings->_rootFolderID;

$view = UI::factory($theme, $tmp[1], array(
    'dms'=>$dms, 
    'user'=>$user, 
    'rootfolder'=>$rootfolder, 
    'previewWidthList'=>$settings->_previewWidthList, 
    'cachedir' => $settings->_cacheDir,
    'mostDownloaded'=>$mostDownloaded, 
    'mostRecent'=>$mostRecent, 
    'recommended'=>$recommended, 
    'mostRated'=>$rated)); //RR gets "views/Bootstrap/class.Dashboard.php"
if($view) {
	$view->show();
	exit;
}

?>

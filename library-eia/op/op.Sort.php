<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
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
include("../inc/inc.LogInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");


function getBoolValue($post_name)
{
  $out = false;
  if (isset($_POST[$post_name]))
    if ($_POST[$post_name]=="on")
      $out = true;

  return $out;
}

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

if (isset($_POST["action"])) $action=$_POST["action"];
else if (isset($_GET["action"])) $action=$_GET["action"];
else $action=NULL;

// --------------------------------------------------------------------------
if ($action == "saveSettings")
{
  // -------------------------------------------------------------------------
  // get values
  // -------------------------------------------------------------------------
  // SETTINGS - SITE - DISPLAY
	
	$settings->_sortUsersInList = $_POST["sortUsersInList"];
	$settings->_sortFoldersDefault = $_POST["sortFoldersDefault"];

  // -------------------------------------------------------------------------
  // save
  // -------------------------------------------------------------------------
  if (!$settings->save())
    UI::exitError(getMLText("admin_tools"),getMLText("settings_SaveError"));

	add_log_line(".php&action=savesettings");
}

$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_settings_saved')));


header("Location:../out/out.Sort.php");

?>

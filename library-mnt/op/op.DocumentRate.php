<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2011-2013 Uwe Steinmann
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



// apply document rating
//RR so guests cannot rate material.  Other users can apply one rating per document, so if they re-rate it, it overwrites their previous rating.

if (!isset($_GET["documentid"]) || !is_numeric($_GET["documentid"]) || intval($_GET["documentid"]) < 1) {
    UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))), getMLText("invalid_doc_id"));
}

$documentid = $_GET["documentid"];
$document = $dms->getDocument($documentid);

if (!is_object($document)) {
    UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))), getMLText("invalid_doc_id"));
}

if ($document->getAccessMode($user) < M_READ) {
    UI::exitError(getMLText("document_title", array("documentname" => $document->getName())), getMLText("access_denied"));
}

//RR guests cannot rate docs
if ($user->isGuest()) {
    UI::exitError(getMLText("document_title", array("documentname" => $document->getName())), getMLText("access_denied"));
}

//RR all good, so now add entry to documentdownloads table
$qryStr = "DELETE FROM tbldocumentrating WHERE documentid = " . $documentid . " AND userid = " . $user->getID();

$res = $db->getResult($qryStr);
if (!$res)
    echo "Error deleting any existing user rating for this document";
$qryStr = "INSERT INTO tbldocumentrating (documentid, userid, rating) VALUES (" . $documentid . ", " . $user->getID() . ", 1)";

$res = $db->getResult($qryStr);
if (!$res)
    echo "Error inserting document rating into database";
$session->setSplashMsg(array('type'=>'success', 'msg'=>getMLText('splash_document_rated')));
header('Location: ' . $_SERVER['HTTP_REFERER']);

exit();
?>
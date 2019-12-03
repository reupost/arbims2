<?php // $Id: file.php 246152 2007-11-14 10:49:27Z hholzgra $

	ini_set("include_path", ini_get("include_path").":/usr/local/apache/htdocs");
  require_once "HTTP/WebDAV/Server/Filesystem.php";
	$server = new HTTP_WebDAV_Server_Filesystem();
	$server->ServeRequest($_SERVER["DOCUMENT_ROOT"]);
?>
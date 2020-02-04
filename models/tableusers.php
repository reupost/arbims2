<?php

/*
 * Manage the database interface for the users collection
 */

require_once("models/table_base.php");

class TableUsers extends Table_Base {

    var $sql_listing = "SELECT *, to_char(lastlogindate,'DD Mon YYYY') AS last_login from \"user\"";

    var $fieldmap_orderby = array(
        "username" => "username",
        "email" => "email",
        "siterole" => "siterole, username",
        "last_login" => "lastlogindate, username",
        "activated" => "activated, username",        
    );
    var $fieldmap_filterby = array(
        "filtercontent" => "to_tsvector('english', lower(coalesce(username,'') || ' ' || coalesce(email,''))) @@ plainto_tsquery('***')",        
    );    

}

?>
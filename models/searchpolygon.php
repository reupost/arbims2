<?php
require_once("includes/config.php");
require_once("includes/inc.language.php");
require_once("models/table_base.php");


class SearchPolygon extends Table_Base {

    var $sql_listing = "SELECT * FROM search_polygon";
    var $fieldmap_orderby = array(
        "id" => "id"
    );
    var $fieldmap_filterby = array(
        "id" => "id"
    );

    public function WriteSearchPolygonToDB($polygon, &$save_msg) {
        global $siteconfig;

        $res = @pg_query_params("INSERT INTO search_polygon (polygon) VALUES ($1) RETURNING id", array($polygon));
        if (!$res) { $save_msg = getMLtext('sql_error'); return -1; }
        $row = pg_fetch_row($res);
        $new_id = $row['0'];
        return $new_id;
    }

    public function GetSearchPolygon($id) {
        $this->AddWhere('id','=',$id);
        $res = $this->GetRecords();
        if ($res) {
            return $res[0]['polygon'];
        } else {
            return ''; //not found
        }
    }
}


?>
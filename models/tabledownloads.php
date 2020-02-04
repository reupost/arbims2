<?php

/*
 * Manage the database interface for the downloads collection
 */

require_once("models/table_base.php");

class TableDownloads extends Table_Base {
    var $sql_listing = "SELECT d.id, d.numrecords, d.region, d.strquery, d.url_params, d.url_full, to_char(d.downloaddate,'DD Mon YYYY') download_date, 
extract(DAY from d.downloaddate) download_day, extract(MONTH from d.downloaddate) download_month, extract(YEAR from d.downloaddate) download_year,
 u.id as user_id, u.username, u.email, u.siterole
FROM log_downloads d 
LEFT JOIN 
\"user\" u ON d.user_id = u.id
";
     var $fieldmap_orderby = array(
        "download" => "d.id",
        "download_date" => "d.downloaddate",
        "username" => "u.username",
        "email" => "u.email",
        "numrecords" => "d.numrecords"
    );
    var $fieldmap_filterby = array(
        "username" => "u.username",
        "year" => "download_year",
        "region" => "region",
        "filtercontent" => "to_tsvector('english', lower(coalesce(u.username,'') || ' ' || replace(replace(coalesce(u.email,''), '@', ' '), '.', ' ')  || ' ' || coalesce(u.siterole,''))) @@ plainto_tsquery('***')",
    );

    protected function GetSQLlisting($orderby = '', $start = 0, $num = 0) {
        $sql = $this->sql_listing;

        $sql .= $this->GetWhereClause();
        if (!array_key_exists($orderby, $this->fieldmap_orderby)) {
            $sql .= " ORDER BY " . reset($this->fieldmap_orderby);
        } else {
            $sql .= " ORDER BY " . $this->fieldmap_orderby[$orderby];
        }

        if ($start != 0 || $num != 0) $sql .= " LIMIT " . $num . " OFFSET " . $start;

        return $sql;
    }

    public function GetFilterListBy($url_params) {
        $params = explode($url_params,'&');
        foreach($params as $param) {
            if (strtolower(substr($params,0,strlen("filterlistby="))) == "filterlistby=") {
                $paramArr = explode($param,"=");
                return $paramArr[1];
            }
        }
        return "";
    }

}

?>
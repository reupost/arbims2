<?php

/*
 * Manage the database interface for the datasets collection
 */

require_once("models/table_base.php");

class TableDatasets extends Table_Base {
    var $region = '';
    var $sql_listing = "SELECT d.*, d._regions[1] AS region_albertine, d._regions[2] AS region_mountains, d._regions[3] AS region_lakes, CASE WHEN d._has_occurrence THEN concat('<div class=\"color_show\" style=\"background-color:', d.color, '\"></div>') ELSE '' END as color_box, to_char(to_timestamp(d.date_timestamp),'DD Mon YYYY') pubdisplaydate, numrecs_occ.rcount occrecs, numrecs_occ_latlon.rcount occrecs_latlon, numrecs_tax.rcount taxrecs
FROM dataset d 
LEFT JOIN 
(#DATASETOCC#) numrecs_occ ON d.datasetid = numrecs_occ._datasetid
LEFT JOIN
(#DATASETOCC_LATLON#) numrecs_occ_latlon ON d.datasetid = numrecs_occ_latlon._datasetid
LEFT JOIN
(#DATASETTAX#) numrecs_tax ON d.datasetid = numrecs_tax._datasetid
";
    var $sql_datasetocc = "SELECT op._datasetid, count(*) rcount FROM occurrence_processed op JOIN dataset d ON op._datasetid = d.datasetid #REGION# GROUP BY op._datasetid"; /* to be inserted into SQL above with any dataset-specific where-clause */
    var $sql_datasetocc_latlon = "SELECT op._datasetid, count(*) rcount FROM occurrence_processed op JOIN dataset d ON op._datasetid = d.datasetid WHERE (op._decimallatitude IS NOT NULL AND op._decimallongitude IS NOT NULL #REGION#) GROUP BY op._datasetid";
    var $sql_datasettax = "SELECT _datasetid, count(*) rcount FROM taxon #REGION# GROUP BY _datasetid";
    var $fieldmap_orderby = array(
        "title" => "d.title",
        "date" => "d.date_timestamp",
        "creator" => "d._creator, d._creator_org",
        "contact" => "d._contact, d._contact_org",
        "region:albertine" => "d._regions[1]",
        "region:mountains" => "d._regions[2]",
        "region:lakes" => "d._regions[3]",
        "records_occ" => "occrecs",
        "records_occ_latlon" => "occrecs_latlon",
        "records_tax" => "taxrecs"
    );
    var $fieldmap_filterby = array(
        "has_occurrence" => "d._has_occurrence", //not used
        "has_taxon" => "d._has_taxon", //not used
        "creator" => "d._creator", //not used
        "region:albertine" => "d._regions[1]",  
        "region:mountains" => "d._regions[2]",  
        "region:lakes" => "d._regions[3]",  
        "filtercontent" => "to_tsvector('english', lower(coalesce(title,'') || ' ' || coalesce(_creator,'') || ' ' || coalesce(_contact,'') || ' ' || coalesce(_creator_org,'') || ' ' || coalesce(_contact_org,''))) @@ plainto_tsquery('***')",
        //"filtercontent" => "concat_ws(' ', d.title, d._creator, d._contact, d._creator_org, d._contact_org)"
        //this is crude but since the number of datasets is likely to be <100, the performance cost is ok
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
		
		$sql2 = $this->sql_datasetocc;
        switch ($this->region) {
            case "albertine": $sql2 = str_replace('#REGION#', "WHERE d._regions[1] = true" , $sql2); break;
            case "mountains": $sql2 = str_replace('#REGION#', "WHERE d._regions[2] = true" , $sql2); break;
            case "lakes"    : $sql2 = str_replace('#REGION#', "WHERE d._regions[3] = true" , $sql2); break;
            default         : $sql2 = str_replace('#REGION#', "" , $sql2); break;
        }
		$sql = str_replace('#DATASETOCC#', $sql2, $sql);
		$sql2 = $this->sql_datasetocc_latlon;
        switch ($this->region) {
            case "albertine": $sql2 = str_replace('#REGION#', "AND d._regions[1] = true" , $sql2); break;
            case "mountains": $sql2 = str_replace('#REGION#', "AND d._regions[2] = true" , $sql2); break;
            case "lakes"    : $sql2 = str_replace('#REGION#', "AND d._regions[3] = true" , $sql2); break;
            default         : $sql2 = str_replace('#REGION#', "" , $sql2); break;
        }
		$sql = str_replace('#DATASETOCC_LATLON#', $sql2, $sql);
		$sql2 = $this->sql_datasettax;
        switch ($this->region) {
            case "albertine": $sql2 = str_replace('#REGION#', "WHERE _regions[1] = true" , $sql2); break;
            case "mountains": $sql2 = str_replace('#REGION#', "WHERE _regions[2] = true" , $sql2); break;
            case "lakes"    : $sql2 = str_replace('#REGION#', "WHERE _regions[3] = true" , $sql2); break;
            default         : $sql2 = str_replace('#REGION#', "" , $sql2); break;
        }
		$sql = str_replace('#DATASETTAX#', $sql2, $sql);
        //echo $sql;
        return $sql;
    }
    
    public function SetRegion($reg) {
        $this->region = $reg;
    }
    //get dataset title based on datasetid (text) or database id (serial)
    public function GetDatasetTitle($datasetid = '', $id = 0) {
        if ($datasetid == '' && !$id) return 'Error: missing parameter in GetDatastTitle';
        $sql = "SELECT \"title\" from dataset WHERE ";
        if ($datasetid > '') {
            $sql .= " datasetid = $1";
            $res = pg_query_params($sql, array($datasetid));
        }
        if ($id) {
            $sql .= " id = $1";
            $res = pg_query_params($sql, array($id));
        }
        if (!$res) {
            return 'Error: bad query parameter in GetDatastTitle';
        } else {            
            $rw= pg_fetch_array($res);
            return $rw['title'];
        }
    }
    
    public function GetOccurrenceLegend($include_dataset_links = true, $region='') {
        $legend = "";
        $sql = "SELECT datasetid, title, concat('<div class=\"color_show\" style=\"background-color:', color, '\"></div>') as color_box FROM dataset WHERE _has_occurrence ";
        switch ($region) {
            case "albertine"    : $sql .= " AND _regions[1] = true "; break;
            case "mountains"    : $sql .= " AND _regions[2] = true "; break;
            case "lakes"        : $sql .= " AND _regions[3] = true "; break;
            default             : break;
        }
        $sql .= "ORDER BY title";
        $res = pg_query_params($sql, array());
        if (!$res) return $legend;
        while ($row = pg_fetch_array($res)) {
            $legend .= $row['color_box'] . "&nbsp;";
            if ($include_dataset_links) {
                $legend .= "<a href='out.dataset.php?datasetid=" . $row['datasetid'] . "' alt='" . htmlspecialchars(getMLtext('dataset')) . "' title='" . htmlspecialchars(getMLtext('dataset')) . "'>" . htmlspecialchars($row['title']) . "</a>";
            } else {
                $legend .= htmlspecialchars($row['title']);
            }
            $legend .= "<br/>";
        }
        return $legend;
    }
}

?>
<?php
require_once("includes/config.php");
require_once("includes/inc.language.php");

class SingleDataset {
    var $datasetid = "";
    
    public function SingleDataset($datasetid) {
        $this->datasetid = $datasetid;
    }
    
    public function GetViewableAttributes() {
        return array("title", "link", "dwca", "eml", "pubdate", "color", "_creator", "_creator_org", "_contact", "_contact_org", "_keywords", "_citation", "region_albertine", "region_mountains", "region_lakes");
    }
    
    public function GetAttributes() {        
        $attribs = array();
        
        $res = pg_query_params("SELECT *, _regions[1] as region_albertine, _regions[2] as region_mountains, _regions[3] as region_lakes FROM dataset WHERE datasetid = $1", array($this->datasetid));
        if (!$res) return $attribs; //error
        
        $row = pg_fetch_array($res, null, PGSQL_ASSOC);
        if (!$row) return $attribs; //no dataet with that datasetid
        
        $attribs = $row;
                
        return $attribs;
    }
    
    //javascript to validate form data before saving
    public function GetPresaveJavascriptCheck() {
        $js = "";
        
        return $js;
    }
    
    public function SetAttributes($data, &$save_msg) {              
        $res = pg_query_params("SELECT * FROM dataset WHERE datasetid = $1", array($data['datasetid']));
        if (!$res) { $save_msg = getMLtext('sql_error'); return 0; }
        $row = pg_fetch_array($res, null, PGSQL_ASSOC);
        if (!$row) { $save_msg = getMLtext('save_dataset_unknown', array('datasetid' => $data['datasetid'])); return 0; } //invalid datasetid
        
        if ($data['color'] != '') {
            if (!preg_match('/^#[a-f0-9]{6}$/i', $data['color'])) {
                $save_msg = getMLtext('save_dataset_invalid_color', array('datasetid' => $data['datasetid'])); 
                return 0; 
            } //invalid color hex
        } else {
            $data['color'] = "000000"; //no applicable color - not an occurrence dataset
        }
        $regions = "{";
        $regions .= ($data['region_albertine'] == 't'? 'true' : 'false') . ", ";
        $regions .= ($data['region_mountains'] == 't'? 'true' : 'false') . ", ";
        $regions .= ($data['region_lakes'] == 't'? 'true' : 'false');
        $regions .= "}";
        
        $save_msg = getMLtext('sql_error');
        $save_err = 0;
        $res = pg_query_params("UPDATE dataset SET (color, _regions) = ($1, $2) WHERE datasetid = $3", array($data['color'], $regions, $data['datasetid']));
        if (!$res) return 0;    
        //now update summaries and relevant occurrence summary grids
        //NOTE: Geoserver is configured not to cache the occurrence_* and occurrence_overview_* layers so that these changes will be visible immediately
        if ($row['_has_occurrence'] == 't') {
            $res = pg_query_params("UPDATE vw_occ_list1 SET dataset_color = $1 WHERE _datasetid = $2", array($data['color'], $data['datasetid']));        
            if (!$res) $save_err = 1;
            $res = pg_query_params("UPDATE vw_occ_list1 SET _regions = $1 WHERE _datasetid = $2", array($regions, $data['datasetid']));
            if (!$res) $save_err = 1;        

            // old data in form '{x,x,x}' where x = t|f
            $regions_previous = $row['_regions'];
            $old_albertine = substr($regions_previous, 1, 1);
            $old_mountains = substr($regions_previous, 3, 1);
            $old_lakes = substr($regions_previous, 5, 1);        
            //call updateoccurrence_grid_albertine();, _mountains, _lakes where the appropriate region bit has changed
            if ($old_albertine != $data['region_albertine']) $res = pg_query_params("SELECT updateoccurrence_grid_albertine()", array());
            if ($old_mountains != $data['region_mountains']) $res = pg_query_params("SELECT updateoccurrence_grid_mountains()", array());
            if ($old_lakes != $data['region_lakes']) $res = pg_query_params("SELECT updateoccurrence_grid_lakes()", array());
        }
        if ($row['_has_taxon'] == 't') {
            $res = pg_query_params("UPDATE taxon t SET _regions = d._regions FROM dataset d WHERE t._datasetid = d.datasetid AND t._datasetid = $1",array($data['datasetid']));
            if (!$res) $save_err = 1;
            $res = pg_query_params("UPDATE vw_spp_list1 tv SET _regions = d._regions FROM dataset d WHERE tv.datasetid = d.datasetid AND tv.datasetid = $1",array($data['datasetid']));
        }
        if ($save_err) return 0;
        
        $save_msg = getMLtext('save_dataset_saved');
        return -1;
    }
}
<?php
require_once("includes/config.php");
require_once("lib/magpierss/rss_fetch.inc");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/inc.language.php");
require_once("includes/sessionmsghandler.php");


define('TAXONOMIC_BACKBONE','taxonomicbackbone');

$OUTPUT = "";

//flatten metadata xml and put into db as <attrib><value> pairs
//return -1 on success, 0 on failure
function ProcessDwCMetadata($metadatafile, $datasetid) {
    
    $filename = GetFileWithoutExtFromPath($metadatafile);
    //clear any existing metadata for the dataset
    pg_query_params("DELETE FROM datasetmetadata WHERE (datasetid = $1 AND strfile = $2)", array($datasetid, $filename));
    
    if (!file_exists($metadatafile)) return 0; //no metadata file
        
    $metadata = simplexml_load_file($metadatafile);
    if (!$metadata) return 0; //invalid xml or some other error
    $meta_array = json_decode(json_encode($metadata), TRUE);
    $meta_flat = FlattenArray($meta_array);
    //Create simple one-dimensional array of key=>values, 
    //where key is composed of fully contextualised XML elements, nested elements being concatenated with '.'
    //attributes of XML elements are indicated with prefix '@'
    //empty elements are not included in the array
    //ready to be inserted into DB with each array element recorded as <dataset_id><attribute><value>

    //var_dump($eml_flat);    
    foreach ($meta_flat as $key=>$value) {
        pg_query_params("INSERT INTO datasetmetadata (datasetid, strfile, strattribute, strvalue) VALUES ($1, $2, $3, $4)", array($datasetid, $filename, $key, $value));
    }
    return -1;
}

//create SQL file from archive; run the SQL file (loading it into the limbo schema)
//return 0 on error or -1 on success (if error then $errormsg is set to aggregated command output
function ProcessDwCRecords($archive, $datasetid, &$errormsg) {
    global $siteconfig;
    
    $sqlfile = $archive . ".sql";
    //create SQL file
    $output = array();
    //echo "java -jar lib/dwca_import/dwca2sql.jar -ci -s " . $archive . " -o " . $sqlfile . " -p " . $datasetid . " -f true";
    //exit;
    $cleandatasetid = str_replace("-","_",$datasetid); //otherwise the SQL table name is invalid
	//echo "\"" . $siteconfig['path_java_exe'] . "\"  -jar lib/dwca_import/dwca2sql.jar -ci -s " . $archive . " -o " . $sqlfile . " -p " . $cleandatasetid . " -f true";
	//exit;
    exec("\"" . $siteconfig['path_java_exe'] . "\"  -jar lib/dwca_import/dwca2sql.jar -ci -s " . $archive . " -o " . $sqlfile . " -p " . $cleandatasetid . " -f true", $output);
    //from the dwca2sql code:
    /* System.out.println("----"+dwcaComponent+"----");
		if(isSuccessful()){
			System.out.println("Successfully generated:");
		}
		else{
			System.out.println("Could not be generated:");
		}
     */
    if (isset($output[1])) {
        if ($output[1] != "Successfully generated:") {
            $errormsg = implode(" ", $output);
            return 0;
        }
    } else {
        $errormsg = implode(" ", $output);
        return 0;
    }
    //import SQL into 'limbo' schema as a single transaction
    //NOTE: user for this command only has rights to limbo schema
    $output2 = array();
	echo "\"" . $siteconfig['path_psql_exe'] . "\" -U " . $siteconfig['limbo_user'] . " -d " . $siteconfig['dwc_db'] . " -1 -f \"" . $sqlfile . "\" -p " . $siteconfig['dwc_port'];
	
    exec("\"" . $siteconfig['path_psql_exe'] . "\" -U " . $siteconfig['limbo_user'] . " -d " . $siteconfig['dwc_db'] . " -1 -f \"" . $sqlfile . "\" -p " . $siteconfig['dwc_port'], $output2);
    if (isset($output2[0])) {
        if ($output2[0] != "DROP TABLE" && $output2[0] != "CREATE TABLE") {
            $errormsg = implode(" ", $output2);
            return 0; //since we use the 'ci' param for the dwca2sql the first line should be drop table (if it existed) or create table
        }
    } else {
        $errormsg = implode(" ", $output2);
        return 0;
    }
    
    return -1; //success
}


//unzip an archive file, and then update the db with the metadata and the DwC records
//returns -1 on success, 0 on failure
function ProcessDwCArchive($datasetid, $archive) {
    global $OUTPUT;
    $folderto = pathinfo(realpath($archive), PATHINFO_DIRNAME);
    $thefile = strtolower(GetFileFromPath($archive));    
    if ($thefile != 'eml.xml') { //i.e. it is a real archive (zip) file
        $ok = UnzipArchive($archive, $folderto);        
        if (!$ok) return 0; //failure
    }
    $res1 = -1;
    if (file_exists($folderto . "/eml.xml")) $res1 = ProcessDwCMetadata($folderto . "/eml.xml", $datasetid); 
    
    $res2 = -1;
    if (file_exists($folderto . "/meta.xml")) $res2 = ProcessDwCMetadata($folderto . "/meta.xml", $datasetid); 
    
    $errormsg = "";    
    $res3 = -1;    
    if ($thefile != 'eml.xml') {
        $res3 = ProcessDwCRecords($archive, $datasetid, $errormsg);        
        if ($errormsg != "") $OUTPUT .= getMLtext("ipt_sync_import_dataset_error") . ": " . $errormsg . "<br/>";
    }
    return (($res1 || $res2) && $res3); //need at least one metadata file and main dataset to pass
}

/* returns -1 if resource is already in DB list with timestamp >= newtimestamp
 * returns 1 if resource not in db
 * returns 0 otherwise (resource should be updated)
 * returns 99 if error
 */
function GetIPTResourceStatus($link, $newtimestamp) {
    global $OUTPUT;
    $result = pg_query_params("SELECT * FROM dataset WHERE (link = $1)", array($link));    
    $row = pg_fetch_array($result); //should only be one (at most)
    if (!$row) return 1; //resource is not in DB    
    if ($row["date_timestamp"] >= $newtimestamp) return -1; //already up to date
    return 0; //out of date
}

//updates the list of datasets from the IPT RSS feed.
//returns -1 on success, 0 on failure
function UpdateIPTResourcesStatus($iptRSS = "") {
    global $siteconfig;
    global $OUTPUT;
    if ($iptRSS == "") $iptRSS = $siteconfig['path_ipt'] . "/rss.do";
	
	$response_xml_data = file_get_contents($iptRSS); //otherwise MagpieRSS can't fetch for some reason.  Might want to get rid of MagpieRSS entirely
    define('MAGPIE_CACHE_ON', false); //don't cache
    $rss = fetch_rss($iptRSS);
    if ($rss === false) {
        $OUTPUT .= magpie_error();
        return 0; //could not get RSS feed
    }
    $res_to_save = "";
    //assume all resources should be removed. Then those that are found will be marked as not toremove
    //var_dump($rss->items);
    foreach ($rss->items as $item ) {
        //var_dump($item);
        $title = $item['title'];
        $link = $item['link'];
        $date_timestamp = $item['date_timestamp'];
        $pubdate = $item['pubdate'];
        $dwca = '';
        if (isset($item['ipt']['dwca'])) $dwca = $item['ipt']['dwca']; //has dataset, i.e. not just metadata publication
        $eml = $item['ipt']['eml']; //all resources should have metadata
        $datasetid = substr($eml, stripos($eml,"=")+1);
        $resourcestatus = GetIPTResourceStatus($link, $date_timestamp);
        $res_to_save .= "'" . pg_escape_string($datasetid) . "', ";
        if ($resourcestatus == 1) { //new
            $color = GetRandomColorHex();
            pg_query_params("INSERT INTO dataset (title, link, dwca, date_timestamp, pubdate, datasetid, eml, color) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)", array($title, $link, $dwca, $date_timestamp, $pubdate, $datasetid, $eml, $color));
        } elseif ($resourcestatus == 0) { //update
            $res = pg_query_params("UPDATE dataset SET title = $1, dwca = $2, date_timestamp = $3, pubdate = $4, datasetid = $5, addedtoportal = $6, eml = $7 WHERE link = $8", array($title, $dwca, $date_timestamp, $pubdate, $datasetid, 0, $eml, $link));
            if (!$res) { $OUTPUT .= "SQL error in UpdateIPTResourcesStatus";  }
        } 
    }
    //pg_query_params("UPDATE dataset SET toremove = true", array());
    if ($res_to_save != "") {
        $res_to_save = substr($res_to_save, 0, -2); //trim trailing comma
        $sql = "UPDATE dataset SET toremove = true WHERE datasetid NOT IN (" . $res_to_save . ")";
        $res = pg_query_params($sql, array());
    } else {
        $res = pg_query_params("UPDATE dataset SET toremove = true", array()); //all gone
    }
    return -1;
}

//downloads the IPT archive from the IPT server
//side-effect: updated localfilepath in dataset table, returns this path or "" if failure
function DownloadIPTArchive($dwcaURL, $emlURL) {
    global $siteconfig;
    
    if ($dwcaURL > '') {
        if (!stripos($dwcaURL,"=")) return ""; //invalid URL
        $filename = substr($dwcaURL,stripos($dwcaURL,"=")+1); //CHECK - assuming URL is of form http://ipt/archive.do?r=xxxx
    } else {
        if (!stripos($emlURL,"=")) return ""; //invalid URL
        $filename = substr($emlURL,stripos($emlURL,"=")+1); //CHECK - assuming URL is of form http://ipt/eml.do?r=echuya2012
    }
    $filedir = $siteconfig['path_basefolder']. '/' . $siteconfig['path_datasets'] . '/' . $filename;
    if (!is_dir($filedir)) mkdir($filedir);
    if (!is_dir($filedir)) { //try cleaning filename - maybe it contains invalid characters
        $filename = preg_replace('/[^\w-]/', '_', $filename); //replace non-alphanumeric chars with _
        //I think this is valid, but it is possible that this would make two different archives have the same name
        //e.g. my$archive and my_archive ==> my_archive
        $filedir = $siteconfig['path_basefolder']. '/' . $siteconfig['path_datasets'] . '/' . $filename;
        if (!is_dir($filedir)) mkdir($filedir);
    }
    if (!is_dir($filedir)) return ""; //could not make directory
    if ($dwcaURL > '') {
        $filename = $filename . ".zip";
        $filenamewithpath = $filedir . '/' . $filename;
        $res = file_put_contents($filenamewithpath, fopen($dwcaURL, 'r'));
        if ($res === FALSE) return ""; //error
        pg_query_params("UPDATE dataset SET localfilepath = $1 WHERE (dwca = $2)", array($filenamewithpath, $dwcaURL));
    } else {
        //EML comes as direct transfer, not a file.  Need to get content and write it to eml.xml file in directory
        $filename = "eml.xml";
        $filenamewithpath = $filedir . '/' . $filename;
        $res = file_put_contents($filenamewithpath, fopen($emlURL, 'r'));
        if ($res === FALSE) return ""; //error
        pg_query_params("UPDATE dataset SET localfilepath = $1 WHERE (eml = $2)", array($filenamewithpath, $emlURL));
    }
    return $filenamewithpath;
}

//get an array of field names from a table
function GetFieldNameArray($table) {
    $fieldnames = array();    
    $result = pg_query_params("SELECT * FROM " . $table . " LIMIT 1", array());
    $i = 0;
    while ($i < pg_num_fields($result)) { 
        $fieldnames[] = pg_field_name($result, $i);
        $i++;
    }
    return $fieldnames;
}

//compares two tables and returns an array of fields from $table that are present in $tablefilter and of the same type
//reserved fields in tablefilter are prefixed with '_' and are not available to be copied from table
function GetValidFieldNameArrayUsingTypeMatch($table, $tablefilter) {
    global $OUTPUT;
    $templatefields = array();
    $acceptablefields = array();
    
    //first load template / filter fieldnames and their types
    //these will be used to judge if the candidate fields are acceptable (in the list, have the same type)
    $template = pg_query_params("SELECT * FROM " . $tablefilter . " LIMIT 1", array());
    $i = 0;
    while ($i < pg_num_fields($template)) {
        if (substr(pg_field_name($template, $i),0,1) != "_") { //skip reserved fields
            $templatefields[pg_field_name($template, $i)] = pg_field_type($template, $i);
        }
        $i++;
    }
    //var_dump($templatefields);
    $candidate = pg_query_params("SELECT * FROM " . $table . " LIMIT 1", array());
    $i = 0;
    while ($i < pg_num_fields($candidate)) { 
        $candidatefield = pg_field_name($candidate, $i);
        if (array_key_exists(strtolower($candidatefield), $templatefields)) {            
            if ($templatefields[strtolower($candidatefield)] == pg_field_type($candidate, $i)) {
                $acceptablefields[] = pg_field_name($candidate, $i);                
                //field is ok
            } else {
                //fields is not of same type
                $OUTPUT .= strtolower($candidatefield) . " types: " . $templatefields[strtolower($candidatefield)] . " <> " . pg_field_type($candidate, $i) . "<br>";
            }
        } else {
            //field doesn't exist in template
            $OUTPUT .= "field " . strtolower($candidatefield) . " not found<br>";
        }
        $i++;
    }
    return $acceptablefields;
}

//compares two tables and returns an array of fields from $table that are present in $tablefilter and ignoring type (destination table uses text fields only)
//reserved fields in tablefilter are prefixed with '_' and are not available to be copied from table
function GetValidFieldNameArrayWithoutTypeMatch($table, $tablefilter) {
    $templatefields = array();
    $acceptablefields = array();
    
    //first load template / filter fieldnames and their types
    //these will be used to judge if the candidate fields are acceptable (in the list, have the same type)
    $template = pg_query_params("SELECT * FROM " . $tablefilter . " LIMIT 1", array());
    $i = 0;
    while ($i < pg_num_fields($template)) {
        if (substr(pg_field_name($template, $i),0,1) != "_") { //skip reserved fields
            $templatefields[pg_field_name($template, $i)] = pg_field_type($template, $i);
        }
        $i++;
    }
    //var_dump($templatefields);
    $candidate = pg_query_params("SELECT * FROM " . $table . " LIMIT 1", array());
    $i = 0;
    while ($i < pg_num_fields($candidate)) { 
        $candidatefield = pg_field_name($candidate, $i);
        if (array_key_exists(strtolower($candidatefield), $templatefields)) {                        
            $acceptablefields[] = pg_field_name($candidate, $i);                
            //field is ok            
        } else {
            //field doesn't exist in template
            //echo "field " . strtolower($candidatefield) . " not found<br>";
        }
        $i++;
    }
    return $acceptablefields;
}

//returns the first field in the primary key for the table, or field 'id' if this exists and there is no primary key defined
//or "" if there's nothing to use
//NOTE: composite priamry keys are not accommodated here
function GetPrimaryKeyFirstField($table) {    
    $sql = "SELECT pg_attribute.attname, format_type(pg_attribute.atttypid, pg_attribute.atttypmod) ";
    $sql .= "FROM pg_index, pg_class, pg_attribute ";
    $sql .= "WHERE pg_class.oid = '" . $table . "'::regclass AND indrelid = pg_class.oid AND ";
    $sql .= "pg_attribute.attrelid = pg_class.oid AND pg_attribute.attnum = any(pg_index.indkey)AND indisprimary;";
    
    $result = pg_query_params($sql, array());
    if (!$result) { echo "SQL error in GetPrimaryKeyFirstField"; exit; } //error
    $row = pg_fetch_array($result); //could be more than one if composite primary key
    if (!$row) {
        $flds = GetFieldNameArray($table);
        if (in_array("id", $flds)) return "id"; //default field if no actual primary key
        return "";
    } else {
        return $row['attname'];
        //note that composite primary keys are not accommodated here
    }
}

//populate metadata coordinate info for a dataset, or all occurrence records
//returns -1 on success, 0 on failure
function PopulateCoordinates($datasetid = "") {
    $overallres = -1;
    if ($datasetid != "") {
        $res = pg_query_params("SELECT UpdateOccurrence_Coordinates($1)", array($datasetid));
    } else {
        $res = pg_query_params("SELECT UpdateOccurrence_Coordinates()", array());
    }
    if ($res === false) $overallres = 0; //problem updating lat/long    
    
    return $overallres;
}

//rebuild the summary occurrence_grid coverage    
//Aug 2014: now populates all summary grid tables
function PopulateCoordinateGrid() {    
    $overallres = -1;    
    //reason for subtracting 0.5 is because grid is otherwise shifted 'right' and 'up' due to rounding.
    $res = pg_query_params("SELECT UpdateOccurrence_Grid_Albertine()", array());
    if ($res === false) $overallres = 0; //problem updating occurrence_grid
    $res = pg_query_params("SELECT UpdateOccurrence_Grid_Mountains()", array());
    if ($res === false) $overallres = 0; //problem updating occurrence_grid 
    $res = pg_query_params("SELECT UpdateOccurrence_Grid_Lakes()", array());
    if ($res === false) $overallres = 0; //problem updating occurrence_grid 
    return $overallres;
}

//logic for setting the synthetic field 'species' from the various possible DwC fields
function GetSetSpeciesSQL() {    
	$sql = "CASE WHEN coalesce(specificepithet,'') = '' THEN ";
	$sql .= "	CASE WHEN coalesce(scientificname,'') != '' AND lower(taxonrank) IN ('species','subspecies','subsp.','variety','var.') THEN ";
	$sql .= "		lower(SUBSTRING(scientificname FROM POSITION(' ' IN scientificname)+1)) ";
	$sql .= "	ELSE ";
    $sql .= "       CASE WHEN coalesce(acceptednameusage,'') != '' THEN ";
	$sql .= "		    lower(SUBSTRING(acceptednameusage FROM POSITION(' ' IN acceptednameusage)+1)) ";
    $sql .= "	    ELSE ";
	$sql .= "           NULL ";
    $sql .= "       END ";
	$sql .= "	END ";
	$sql .= "ELSE ";
	$sql .= "	CASE WHEN coalesce(infraspecificepithet,'') = '' THEN ";
	$sql .= "		lower(specificepithet) ";
	$sql .= "	ELSE ";
	$sql .= "		lower(concat(specificepithet, ' ', taxonrank, ' ', infraspecificepithet)) ";
	$sql .= "	END ";
	$sql .= "END ";    
    return $sql;
}

//populate metadata taxonomic info for a dataset, or all occurrence records
//returns -1 on success, 0 on failure
function PopulateHigherTaxonomy($datasetid = "") {
    $overallres = -1;
        
    //work up the scale from genus->family->order->class->phylum->kingdom, using whatever got on previous level to populate next level
    $taxon_hierarchy = array('species','genus','family','order','class','phylum','kingdom');
    
    //first disable autovacuum for the duration of the process
    pg_query_params("ALTER TABLE occurrence_processed SET ( autovacuum_enabled = FALSE, toast.autovacuum_enabled = FALSE )", array());
    
    //add temporary indexes to source fields (note: ignore 'species' since this is a synthetic field not in DwC)
    for ($tax = 1; $tax < count($taxon_hierarchy); $tax++) {
        $sql = "CREATE INDEX idx_occurrence_" . $taxon_hierarchy[$tax] . " ON occurrence (\"" . $taxon_hierarchy[$tax] . "\")";  
        $res = pg_query_params($sql, array());
    }
    
    //first blank any existing taxonomic metadata
    if ($datasetid != "") {
        //remove indexes on fields which will be updated, these indexes will be added again at the end
        for ($tax = 0; $tax < count($taxon_hierarchy); $tax++) {
            $sql = "DROP INDEX IF EXISTS idx_occurrence_processed__" . $taxon_hierarchy[$tax];
            $res = pg_query_params($sql, array());
        }
        $res = pg_query_params("UPDATE occurrence_processed SET _species = NULL, _genus = NULL, _family = NULL, _order = NULL, _class = NULL, _phylum = NULL, _kingdom = NULL WHERE (_datasetid = $1)", array($datasetid));
    } else {
        //quickest to drop and recreate fields - this automatically drops the indexes as well
        for ($tax = 0; $tax < count($taxon_hierarchy); $tax++) {
            $sql = "ALTER TABLE occurrence_processed DROP IF EXISTS _" . $taxon_hierarchy[$tax];
            $res = pg_query_params($sql, array());
            $sql = "ALTER TABLE occurrence_processed ADD COLUMN _" . $taxon_hierarchy[$tax] . " text";
            $res = pg_query_params($sql, array());
        }
    }
    if ($res === false) $overallres = 0; //problem blanking data
    
    //overwrite if data exists in occ table (i.e. only prescribe according to taxon backbone if occ data not specified)
    
    //fill in all levels that have explicit data in the occurrence table
    //note: it is _much_ faster to update all columns simultaneously rather than update each column separately
    $sql = "UPDATE occurrence_processed op SET ";
    $sql .= "_species = " . GetSetSpeciesSQL() . ",";
    $sql .= "_genus = CASE WHEN o.\"genus\" = '' THEN NULL ELSE initcap(o.\"genus\") END, ";
    $sql .= "_family = CASE WHEN o.\"family\" = '' THEN NULL ELSE initcap(o.\"family\") END, ";
    $sql .= "_order = CASE WHEN o.\"order\" = '' THEN NULL ELSE initcap(o.\"order\") END, ";
    $sql .= "_class = CASE WHEN o.\"class\" = '' THEN NULL ELSE initcap(o.\"class\") END, ";
    $sql .= "_phylum = CASE WHEN o.\"phylum\" = '' THEN NULL ELSE initcap(o.\"phylum\") END, ";
    $sql .= "_kingdom = CASE WHEN o.\"kingdom\" = '' THEN NULL ELSE initcap(o.\"kingdom\") END ";
    $sql .= "FROM occurrence o WHERE op._id = o._id ";
    if ($datasetid != "") {
        $sql .= "AND o._datasetid = '" . pg_escape_string($datasetid) . "'";
    }
    //echo $sql;
    $res = pg_query_params($sql, array());
    
    //genus: special case - extract from scientificName OR acceptedNameUsage if there was no explicit genus set
    if ($datasetid != "") {
        //process entries with no genus explicitly set
        $res = pg_query_params("UPDATE occurrence_processed op SET _genus = initcap(substr(o.scientificname, 0, strpos(o.scientificname,' '))) FROM occurrence o WHERE (strpos(o.scientificname,' ')>0 AND (o.genus IS NULL OR o.genus='') AND o._datasetid = $1 AND op._id = o._id)", array($datasetid));
        if ($res === false) $overallres = 0;
        $res = pg_query_params("UPDATE occurrence_processed op SET _genus = initcap(substr(o.acceptednameusage, 0, strpos(o.acceptednameusage,' '))) FROM occurrence o WHERE (strpos(o.acceptednameusage,' ')>0 AND (o.genus IS NULL OR o.genus='') AND o._datasetid = $1 AND op._id = o._id)", array($datasetid));
        if ($res === false) $overallres = 0;
    } else {
        $res = pg_query_params("UPDATE occurrence_processed op SET _genus = initcap(substr(o.scientificname, 0, strpos(o.scientificname,' '))) FROM occurrence o WHERE (strpos(o.scientificname,' ')>0 AND (o.genus IS NULL OR o.genus='') AND op._id = o._id)", array());
        if ($res === false) $overallres = 0;
        $res = pg_query_params("UPDATE occurrence_processed op SET _genus = initcap(substr(o.acceptednameusage, 0, strpos(o.acceptednameusage,' '))) FROM occurrence o WHERE (strpos(o.acceptednameusage,' ')>0 AND (o.genus IS NULL OR o.genus='') AND op._id = o._id)", array());
        if ($res === false) $overallres = 0;
    }
    
    //add output indexes again to assist with table join to taxon table
    for ($tax = 0; $tax < count($taxon_hierarchy); $tax++) {
        $sql = "CREATE INDEX idx_occurrence_processed__" . $taxon_hierarchy[$tax] . " ON occurrence_processed (_" . $taxon_hierarchy[$tax] . ")";  
        $res = pg_query_params($sql, array());
    }
    
    //process remaining taxonomic hierarchy entries
    //note, start at family (=2) and work up
    //TODO: this process is ok, but if there are higher-level taxonomic entries then they should be matched as well
    //eg. consider taxonomic backbone entries:
    // order family genus
    //   x     y      z
    //   a     b      z
    // if occurrence record with genus = z is found, cannot simply assign family to it without considering if it has order information
    // to distringuish between y/b family option
    //   x     ?      z
    for ($tax = 2; $tax < count($taxon_hierarchy); $tax++) {
        $sql = "UPDATE occurrence_processed op SET _" . $taxon_hierarchy[$tax] . " = t.\"" . $taxon_hierarchy[$tax] . "\" FROM taxon t WHERE (op._" . $taxon_hierarchy[$tax-1] . " = t.\"" . $taxon_hierarchy[$tax-1] . "\" AND (op._" . $taxon_hierarchy[$tax] . " IS NULL) AND t._datasetid = '" . pg_escape_string(TAXONOMIC_BACKBONE) . "'";
        if ($datasetid != "") {
            $sql .= " AND op._datasetid = '" . pg_escape_string($datasetid) . "')";
        } else {
            $sql .= ")";
        }
        $res = pg_query_params($sql, array());
        if ($res === false) $overallres = 0;
    }
    
    //remove temporary indexes
    for ($tax = 1; $tax < count($taxon_hierarchy); $tax++) {
        $sql = "DROP INDEX IF EXISTS idx_occurrence_" . $taxon_hierarchy[$tax];
        $res = pg_query_params($sql, array());
    }
    //re-enable autovacuum
    pg_query_params("ALTER TABLE occurrence_processed SET ( autovacuum_enabled = TRUE, toast.autovacuum_enabled = TRUE )", array());
    
    return $overallres;
}

//create placeholder records in one-to-one table occurrence_processed
function CreateOccurrenceProcessed($datasetid) {
    $res = pg_query_params("INSERT INTO occurrence_processed (_id, _datasetid) SELECT _id, _datasetid FROM occurrence o WHERE (o._datasetid = $1)", array($datasetid));
    if ($res === false) return 0; //problem
    return -1;
}

//migrate valid content from limbo dataset into main db and do post-processing
//return -1 on success, 0 on any failures 
function ImportDwCData($filename) {
    global $siteconfig;
    
    $overallres = -1;
    $datasetid = GetFileWithoutExtFromPath($filename);
    //debugging
    //$OUTPUT .= "Importing DWCdata from " . $datasetid;
    //scan limbo schema for any tables prefixed with the datasetid    
    $cleandatasetid = strtolower(str_replace("-","_",$datasetid));
    $result = pg_query_params("SELECT table_name FROM information_schema.tables WHERE (table_schema=$1 AND table_name LIKE $2 || '_%')", array($siteconfig['schema_limbo'], $cleandatasetid));
    if (!$result) { echo "SQL error in ImportDwCData"; exit; } //error
    while ($table = pg_fetch_array($result)) {
        $source_id_field = GetPrimaryKeyFirstField($table['table_name']); // "" if none
        //make sure limbo table fields are in the main db table, and [current not checking] fieldtypes are compatible.
        //occurrence tables must be compared to the main schema occurrence table
        if ($table['table_name'] == $cleandatasetid . "_occurrence") {
            pg_query_params("UPDATE dataset SET _has_occurrence = true WHERE (datasetid = $1)", array($datasetid));
            //first delete any existing records for this dataset
            pg_query_params("DELETE FROM occurrence WHERE (_datasetid = $1)",array($datasetid));
            $sql = "INSERT INTO occurrence (";
            $fieldnamearray = GetValidFieldNameArrayWithoutTypeMatch($table['table_name'], 'occurrence');
            foreach ($fieldnamearray as $field) {
                $sql .= "\"" . strtolower($field) . "\", "; //my simpledwc table has lowercase field names to simplify use in postgreSQL
            }
            $sql .= " _datasetid";
            if ($source_id_field != "") $sql .= ", _sourcerecordid";
            $sql .= ") SELECT ";
            foreach ($fieldnamearray as $field) {
                $sql .= "\"" . $field . "\", "; //source table might have mixed case field names
            }
            $sql .= "'" . pg_escape_string($datasetid) . "' as _datasetid";
            if ($source_id_field != "") $sql .= ", \"" . $source_id_field . "\" as _sourcerecordid";
            $sql .= " FROM " . $table['table_name'];
            $res = pg_query_params($sql,array()); //copy valid fields from dataset across
            if ($res === false) $overallres = 0; //problem inserting into main occurrence table
            // now create occurrence_processed records
            $res = CreateOccurrenceProcessed($datasetid);
            if (!$res) {
                $overallres = 0;
            } else {
                //now process lat/long fields
                $res = PopulateCoordinates($datasetid);
                if (!$res) $overallres = 0;
                //now process taxon information
                $res = PopulateHigherTaxonomy($datasetid);
                if (!$res) $overallres = 0;
            }
        }
        //TODO: need a way to tell 'master taxonomic backbone' from other checklist uploads
        if ($table['table_name'] == $cleandatasetid . "_taxon") {
            pg_query_params("UPDATE dataset SET _has_taxon = true WHERE (datasetid = $1)", array($datasetid));
            pg_query_params("DELETE FROM taxon WHERE (_datasetid = $1)",array($datasetid));
            $sql = "INSERT INTO taxon (";
            $fieldnamearray = GetValidFieldNameArrayWithoutTypeMatch($table['table_name'], 'taxon');
            foreach ($fieldnamearray as $field) {
                $sql .= "\"" . strtolower($field) . "\", "; //my table has lowercase field names to simplify use in postgreSQL
            }
            $sql .= " _datasetid";
            if ($source_id_field != "") $sql .= ", _sourcerecordid";
            $sql .= ") SELECT ";
            foreach ($fieldnamearray as $field) {
                $sql .= "\"" . $field . "\", "; //source table might have mixed case field names
            }
            $sql .= "'" . pg_escape_string($datasetid) . "' as _datasetid";
            
            if ($source_id_field != "") $sql .= ", \"" . $source_id_field . "\" as _sourcerecordid";
            $sql .= " FROM " . $table['table_name'];
            $res = pg_query_params($sql,array()); //copy valid fields from dataset across
            if ($res === false) $overallres = 0; //problem inserting into main taxon table
            
            //now fix capitalisation
            pg_query_params("UPDATE taxon SET kingdom = initcap(coalesce(kingdom,'')), phylum = initcap(coalesce(phylum,'')), \"class\" = initcap(coalesce(\"class\",'')), \"order\" = initcap(coalesce(\"order\",'')), family = initcap(coalesce(family,'')), genus = initcap(coalesce(genus,'')), species = " . GetSetSpeciesSQL() . " WHERE _datasetid = $1",array($datasetid));
            //populate scientificname where possible
            pg_query_params("UPDATE taxon SET scientificname = trim(both from (concat(genus,' ',specificepithet,' ',(trim(both from (concat(infraspecificepithet,' ',scientificnameauthorship))))))) WHERE (_datasetid = $1 AND coalesce(scientificname,'') = '' AND genus > '' AND specificepithet > '')",array($datasetid));
            //now add _species_wth_synof data
            pg_query_params("UPDATE taxon SET _species_with_synof = concat(species,' = ',tax_syns.currentname) FROM (SELECT t1._id, t2.scientificname as currentname from taxon t1 JOIN taxon t2 ON t1.acceptednameusageid = t2.taxonid AND t1._datasetid = t2._datasetid) tax_syns WHERE tax_syns._id = taxon._id AND _datasetid = $1",array($datasetid));
            //set taxon regional affiliation.  This needs to be updated when a dataset is updated.
            pg_query_params("UPDATE taxon t SET _regions = d._regions FROM dataset d WHERE t._datasetid = d.datasetid AND t._datasetid = $1",array($datasetid));
            //now rebuild occurrence data taxonomic details if the dataset is the master backbone dataset
            if ($datasetid == TAXONOMIC_BACKBONE) PopulateHigherTaxonomy();
        }
    }
    return $overallres;
}

//returns value of DB semaphor to prevent rerun while already running the process
// returns -1 if semaphor is set, 0 otherwise
function IsSemaphorSet() {
    global $OUTPUT;
    $result = pg_query_params("SELECT * FROM semaphor", array());
    if (!$result) { echo "Error in CheckSemaphor"; exit; }
    $row = pg_fetch_array($result);
    if ($row) return -1; //sempahor is set
    return 0;
}

function SetSemaphor() {
    UnsetSemaphor();
    $result = pg_query_params("INSERT INTO semaphor VALUES (DEFAULT)", array());
    return $result;
}

function UnsetSemaphor() {
    $result = pg_query_params("TRUNCATE semaphor", array());
    return $result;
}

//remove datasets and associated taxon / occurrence records with toremove = true
//returns -1 if any rows deleted, 0 if nothing done 
//(in which case grid summaries need to be rebuilt, 
//if it removes the taxon backbone, best to leave data in occurrence records until a new taxon backbone is loaded
function RemoveObsoleteResources() {
    $result = pg_query_params("DELETE FROM dataset WHERE toremove = true", array());
    //this cascade deletes in occurrence, taxon and datasetmetadata tables
    if (pg_affected_rows($result)) return -1; //fix up summary coordinate grid if anything done
    return 0;
}

//populates dataset-level metadata fields from the datasetmetadata dump table
//returns -1 on success, 0 on faiulre
function PopulateDatasetCleanMetadata($datasetid = "") {        
    //_has_occurrence, _has_taxon - these are set when importing the data
    //following functions sets the follwing in the main dataset table from the datasetmetadata table           
    //_creator
    //_creator_org
    //_contact
    //_contact_org    
    //_keywords
    //_citation
    
    $res = pg_query_params("SELECT UpdateDataset_CleanMetadata()", array());
    return -1; //assume success
}

function UpdateSummaryViewTables() {
    //The functions below do the following:
    //drop indexes
    //truncate tables
    //reinsert into tables
    //recreate indexes
    
    $res_spp = pg_query_params("SELECT UpdateSummary_Spp()", array());    
    $res_occ_list = pg_query_params("SELECT UpdateSummary_OccList()", array());
    $res_occ_sum = pg_query_params("SELECT UpdateSummary_OccSum()", array());
    
    //check if any are zero? but that is not necessarily wrong
    
    return -1;
}

function UpdateIPTResources($iptRSS = "") {
    global $siteconfig;
    global $OUTPUT;
    if ($iptRSS == "") $iptRSS = $siteconfig['path_ipt'] . "/rss.do";
    $already_running = IsSemaphorSet();
    if ($already_running) {
        $OUTPUT .= getMLtext("ipt_sync_already_running");
        $OUTPUT .= "<br/>" . getMLtext('ipt_sync_force_unlock') . ": <a href='op.ipt_synch_unlock.php'>" . getMLtext('ipt_sync_force_unlock_link') . "</a>";
        return 0;
    }
    SetSemaphor();
    $OUTPUT .= getMLtext("ipt_sync_start", array("iptRSS" => $iptRSS)) . ":<br/>";
    $res = UpdateIPTResourcesStatus($iptRSS); //updates the table of IPT resources where addedtoportal = false means it needs to be reprocessed
    if (!$res) {
        $OUTPUT .= getMLtext("ipt_sync_fail") + "<br/>";
        UnsetSemaphor();
        return 0;
    }    
    $recreatesummaries = RemoveObsoleteResources();
    $result = pg_query_params("SELECT * FROM dataset WHERE (addedtoportal = false)", array());
    if (!$result) { $OUTPUT .= "<br/><br/>SQL error in UpdateIPTResources"; return 0; }
    while ($row = pg_fetch_array($result)) {        
        $recreatesummaries = -1;
        //echo $row['dwca'];
        $OUTPUT .= getMLtext("ipt_sync_dataset", array("dataset" => $row['datasetid'])) . ":<br/>";        
        $OUTPUT .= getMLtext("ipt_sync_dwca", array("dwca" => ($row['dwca'] > ''? $row['dwca'] : $row['eml']))) . ": ";         
        $localfile = DownloadIPTArchive($row['dwca'], $row['eml']); //get the file: either DWCA zip file or EML.XML
        if ($localfile != "") {
            $res = ProcessDwCArchive($row['datasetid'], $localfile); //unpack file, put metadata into datasetmetadata table, put dwc table(s) into limbo schema
            if (!$res) {
                $OUTPUT .= getMLtext("ipt_sync_error_unzip") . "<br/>";
            } else {
                PopulateDatasetCleanMetadata($row['datasetid']);
                $res = ImportDwCData($localfile); //now parse across into main DB and fix up geometry
                if (!$res) {
                    $OUTPUT .= getMLtext("ipt_sync_error_import") . "<br/>";
                } else {
                    pg_query_params("UPDATE dataset SET addedtoportal = true WHERE (link = $1)", array($row['link']));
                    $OUTPUT .= getMLtext("ipt_sync_done"). "<br/>";
                }
            }
        } else {
            $OUTPUT .= getMLtext("ipt_sync_error_download") . "<br/>";"Download failed!<br/>";
        }
    }
    if ($recreatesummaries) {
        PopulateCoordinateGrid();
        UpdateSummaryViewTables();        
    }
    $OUTPUT .= getMLtext("ipt_sync_finished_checking") . "<br/>";
    UnsetSemaphor();
    return -1;
}

global $siteconfig;
global $USER_SESSION;

if ($USER_SESSION['siterole'] != 'admin') {
    header("Location: out.index.php"); //user does not have permission to do this
    exit;
}

$session = new SessionMsgHandler();
$session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);

UpdateIPTResources();

/*
 * Problem: if the page times out because of a long-running query, and the user clickes 'refresh', then the same query
 * is re-run, causing a deadlock on the DB server.
 * Fixes: 
 * optimise queries - done
 * Change time-out (make it longer) - not done
 * Semaphor to prevent simultaneous run - done 
 * Split occurrence table from occurrence_processed - done
 * From 11 min to do a taxonomic tree rebuild it now takes 30 sec.
 * Might be better to put this in an offline process, not a webpage
 */

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('synchronise_with_ipt')); 
$tpl->set('page_specific_head_content', "");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('session_msg', $session_msg);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/ipt_synch.tpl.php');
$bdy->set('output', $OUTPUT);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');

?>

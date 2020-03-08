<?php
define('TAXONOMIC_BACKBONE','taxonomicbackbone');

require_once("../web/includes/config.php");
global $siteconfig;
//require_once($siteconfig['path_basefolder'] . "/models/table_base.php");

class GBIF_load {

    private function log($text) {
        global $siteconfig;
        $timestamp = date("Y-m-d h:i:sa");
        file_put_contents($siteconfig['path_basefolder'] . '/logs/gbif_'.date("Y-m-d").'.txt', $timestamp . ' ' . $text . PHP_EOL, FILE_APPEND);
    }

    public function log_summary($text) {
        global $siteconfig;
        $timestamp = date("Y-m-d h:i:sa");
        file_put_contents($siteconfig['path_basefolder'] . '/logs/gbif_summary_'.date("Y-m").'.txt', $timestamp . ' ' . $text . PHP_EOL, FILE_APPEND);
    }

    private function GetFileWithoutExtFromPath($filepath) {
        $arrPath = explode("/",$filepath);
        $filenameWithExt = $arrPath[count($arrPath)-1];
        $arrName = explode(".",$filenameWithExt);
        if (count($arrName) > 1) array_pop($arrName); //remove extension
        return implode(".", $arrName);
    }

    //returns value of DB semaphor to prevent rerun while already running the process
// returns -1 if semaphor is set, 0 otherwise
    function IsSemaphorSet()
    {

        $result = pg_query_params("SELECT * FROM semaphor", array());
        if (!$result) {
            $this->log("Error in IsSemaphorSet");
            exit;
        }
        $row = pg_fetch_array($result);
        if ($row) return -1; //semaphor is set
        return 0;
    }
    function SetSemaphor()
    {
        $this->UnsetSemaphor();
        $result = pg_query_params("INSERT INTO semaphor VALUES (DEFAULT)", array());
        return $result;
    }
    function UnsetSemaphor()
    {
        $result = pg_query_params("TRUNCATE semaphor", array());
        return $result;
    }

    //get an array of field names from a table
    function GetFieldNameArray($table)
    {
        $fieldnames = array();
        $result = pg_query_params("SELECT * FROM " . $table . " LIMIT 1", array());
        $i = 0;
        while ($i < pg_num_fields($result)) {
            $fieldnames[] = pg_field_name($result, $i);
            $i++;
        }
        return $fieldnames;
    }

    //returns the first field in the primary key for the table, or field 'id' if this exists and there is no primary key defined
//or "" if there's nothing to use
//NOTE: composite primary keys are not accommodated here
    function GetPrimaryKeyFirstField($table)
    {
        $sql = "SELECT pg_attribute.attname, format_type(pg_attribute.atttypid, pg_attribute.atttypmod) ";
        $sql .= "FROM pg_index, pg_class, pg_attribute ";
        $sql .= "WHERE pg_class.oid = '" . $table . "'::regclass AND indrelid = pg_class.oid AND ";
        $sql .= "pg_attribute.attrelid = pg_class.oid AND pg_attribute.attnum = any(pg_index.indkey)AND indisprimary;";

        $this->log("Getting primary key first field with " . $sql);
        $result = pg_query_params($sql, array());
        if (!$result) {
            $this->log("SQL error in GetPrimaryKeyFirstField");
            exit;
        } //error
        $row = pg_fetch_array($result); //could be more than one if composite primary key
        if (!$row) {
            $flds = $this->GetFieldNameArray($table);
            if (in_array("id", $flds)) return "id"; //default field if no actual primary key
            return "";
        } else {
            return $row['attname'];
            //note that composite primary keys are not accommodated here
        }
    }

    //compares two tables and returns an array of fields from $table that are present in $tablefilter and ignoring type (destination table uses text fields only)
//reserved fields in tablefilter are prefixed with '_' and are not available to be copied from table
    function GetValidFieldNameArrayWithoutTypeMatch($table, $tablefilter)
    {
        $this->log("GetValidFieldNameArrayWithoutTypeMatch - Enter - [" . $table . ", " . $tablefilter . "]");

        $templatefields = array();
        $acceptablefields = array();

        //first load template / filter fieldnames and their types
        //these will be used to judge if the candidate fields are acceptable (in the list, have the same type)
        $template = pg_query_params("SELECT * FROM " . $tablefilter . " LIMIT 1", array());
        $i = 0;
        while ($i < pg_num_fields($template)) {
            if (substr(pg_field_name($template, $i), 0, 1) != "_") { //skip reserved fields
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
        $this->log("GetValidFieldNameArrayWithoutTypeMatch - Leave");
        return $acceptablefields;
    }

    //create placeholder records in one-to-one table occurrence_processed
    function CreateOccurrenceProcessed()
    {
        $this->log("CreateOccurrenceProcessed - Enter");

        $this->log("GBIF data: truncating occurrence_processed");
        pg_query_params("TRUNCATE occurrence_processed", array());

        $this->log("Creating occurrence record stubs in occurrence_processed");
        $res = pg_query_params("INSERT INTO occurrence_processed (_id, datasetkey) SELECT _id, datasetkey FROM occurrence o", array());
        $this->log("CreateOccurrenceProcessed - Leave");

        if ($res === false) return 0; //problem
        return -1;
    }

    //populate metadata coordinate info for a dataset, or all occurrence records
//returns -1 on success, 0 on failure
    function PopulateCoordinates()
    {
        $this->log("PopulateCoordinates - Enter");

        $overallres = -1;
        $this->log("Temporarily dropping spatial indexes from occurrence_processed");
        $spatial_idxs = array("_deci_latitude", "_deci_longitude", "_decimallatitude", "_decimallongitude");
        foreach ($spatial_idxs as $spatial_idx) {
            //$spatial_idxs_joined = implode(", ", $spatial_idxs); TODO - can drop all at once
            $sql = "DROP INDEX IF EXISTS idx_occurrence_processed_" . $spatial_idx;
            $res = pg_query_params($sql, array());
        }

        $res = pg_query_params("SELECT UpdateOccurrence_Coordinates()", array());

        if ($res === false) $overallres = 0; //problem updating lat/long
        $this->log("Recreating spatial indexes on occurrence_processed");
        foreach ($spatial_idxs as $spatial_idx) {
            $sql = "CREATE INDEX IF NOT EXISTS idx_occurrence_processed_" . $spatial_idx . " ON occurrence_processed (" . $spatial_idx . ")";
            $res = pg_query_params($sql, array());
        }
        $this->log("PopulateCoordinates - Leave");
        return $overallres;
    }

    function GetSetSpeciesSQL()
    {
        $sql = "CASE WHEN coalesce(specificepithet,'') = '' THEN ";
        $sql .= "	CASE WHEN coalesce(scientificname,'') != '' AND taxonrank IN ('SPECIES','SUBSPECIES','VARIETY','FORM') THEN ";
        $sql .= "		scientificname ";
        $sql .= "	ELSE ";
        $sql .= "       CASE WHEN coalesce(acceptednameusage,'') != '' THEN ";
        $sql .= "		    acceptednameusage ";
        $sql .= "	    ELSE ";
        $sql .= "           NULL ";
        $sql .= "       END ";
        $sql .= "	END ";
        $sql .= "ELSE ";
        $sql .= "	CASE WHEN coalesce(infraspecificepithet,'') = '' THEN ";
        $sql .= "		concat(genus, ' ', specificepithet) ";
        $sql .= "	ELSE ";
        $sql .= "		concat(genus, ' ' , specificepithet, ' ', taxonrank, ' ', infraspecificepithet) ";
        $sql .= "	END ";
        $sql .= "END ";
        return $sql;
    }

    //populate metadata taxonomic info for a dataset, or all occurrence records
//returns -1 on success, 0 on failure
    function PopulateHigherTaxonomy()
    {
        $this->log("PopulateHigherTaxonomy - Enter");

        $overallres = -1;

        //work up the scale from genus->family->order->class->phylum->kingdom, using whatever got on previous level to populate next level
        $taxon_hierarchy = array('species', 'genus', 'family', 'order', 'class', 'phylum', 'kingdom');

        //first disable autovacuum for the duration of the process
        pg_query_params("ALTER TABLE occurrence_processed SET ( autovacuum_enabled = FALSE, toast.autovacuum_enabled = FALSE )", array());

        //add temporary indexes to source fields (note: ignore 'species' since this is a synthetic field not in DwC)

        //$this->log("PopulateHigherTaxonomy - Adding temporary taxon indexes to occurrence");
        //for ($tax = 1; $tax < count($taxon_hierarchy); $tax++) {
        //    $sql = "CREATE INDEX IF NOT EXISTS idx_occurrence_" . $taxon_hierarchy[$tax] . " ON occurrence (\"" . $taxon_hierarchy[$tax] . "\")";
        //    $res = pg_query_params($sql, array());
        //}

        //first blank any existing taxonomic metadata
        $this->log("PopulateHigherTaxonomy - dropping higher taxonomy fields and recreating them (quicker than deleting indexes)");
        //quickest to drop and recreate fields - this automatically drops the indexes as well
        for ($tax = 0; $tax < count($taxon_hierarchy); $tax++) {
            $sql = "ALTER TABLE occurrence_processed DROP IF EXISTS _" . $taxon_hierarchy[$tax];
            $res = pg_query_params($sql, array());
            $sql = "ALTER TABLE occurrence_processed ADD COLUMN _" . $taxon_hierarchy[$tax] . " text";
            $res = pg_query_params($sql, array());
        }
        if ($res === false) $overallres = 0; //problem blanking data

        //overwrite if data exists in occ table (i.e. only prescribe according to taxon backbone if occ data not specified)

        //fill in all levels that have explicit data in the occurrence table
        //note: it is _much_ faster to update all columns simultaneously rather than update each column separately
        $sql = "UPDATE occurrence_processed op SET ";
        $sql .= "_species = " . $this->GetSetSpeciesSQL() . ",";
        $sql .= "_genus = CASE WHEN o.\"genus\" = '' THEN NULL ELSE initcap(o.\"genus\") END, ";
        $sql .= "_family = CASE WHEN o.\"family\" = '' THEN NULL ELSE initcap(o.\"family\") END, ";
        $sql .= "_order = CASE WHEN o.\"order\" = '' THEN NULL ELSE initcap(o.\"order\") END, ";
        $sql .= "_class = CASE WHEN o.\"class\" = '' THEN NULL ELSE initcap(o.\"class\") END, ";
        $sql .= "_phylum = CASE WHEN o.\"phylum\" = '' THEN NULL ELSE initcap(o.\"phylum\") END, ";
        $sql .= "_kingdom = CASE WHEN o.\"kingdom\" = '' THEN NULL ELSE initcap(o.\"kingdom\") END ";
        $sql .= "FROM occurrence o WHERE op._id = o._id ";

        $this->log("PopulateHigherTaxonomy - about to fill occurrence processed taxonomy with " . $sql);
        $res = pg_query_params($sql, array());
        $this->log("PopulateHigherTaxonomy - filled occurrence processed taxonomy");


        //genus: special case - extract from scientificName OR acceptedNameUsage if there was no explicit genus set
        //$this->log("PopulateHigherTaxonomy - fixing occurrence processed genus");

        //$res = pg_query_params("UPDATE occurrence_processed op SET _genus = initcap(substr(o.scientificname, 0, strpos(o.scientificname,' '))) FROM occurrence o WHERE (strpos(o.scientificname,' ')>0 AND (o.genus IS NULL OR o.genus='') AND op._id = o._id)", array());
        //if ($res === false) $overallres = 0;
        //$res = pg_query_params("UPDATE occurrence_processed op SET _genus = initcap(substr(o.acceptednameusage, 0, strpos(o.acceptednameusage,' '))) FROM occurrence o WHERE (strpos(o.acceptednameusage,' ')>0 AND (o.genus IS NULL OR o.genus='') AND op._id = o._id)", array());
        //if ($res === false) $overallres = 0;


        //add output indexes again to assist with table join to taxon table
        $this->log("PopulateHigherTaxonomy - adding occurrence processed taxonomy indexes");

        for ($tax = 0; $tax < count($taxon_hierarchy); $tax++) {
            $this->log("Adding index - " . $taxon_hierarchy[$tax]);
            $sql = "CREATE INDEX IF NOT EXISTS idx_occurrence_processed__" . $taxon_hierarchy[$tax] . " ON occurrence_processed (_" . $taxon_hierarchy[$tax] . ")";
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
        // to distinguish between y/b family option
        //   x     ?      z
        /*
        $this->log("PopulateHigherTaxonomy - processing remaining occurrence processed taxonomic hierarchy");
        for ($tax = 2; $tax < count($taxon_hierarchy); $tax++) {
            $sql = "UPDATE occurrence_processed op SET _" . $taxon_hierarchy[$tax] . " = t.\"" . $taxon_hierarchy[$tax] . "\" FROM taxon t WHERE (op._" . $taxon_hierarchy[$tax - 1] . " = t.\"" . $taxon_hierarchy[$tax - 1] . "\" AND t.taxonrank  = '" .  strtoupper($taxon_hierarchy[$tax - 1])  . "' AND (op._" . $taxon_hierarchy[$tax] . " IS NULL) AND t._datasetid = '" . pg_escape_string(TAXONOMIC_BACKBONE) . "'";
            if ($datasetid != "" && $datasetid != "gbif_data") {
                $sql .= " AND op._datasetid = '" . pg_escape_string($datasetid) . "')";
            } else {
                $sql .= ")";
            }
            $this->log($tax - 1 . " of " . count($taxon_hierarchy) - 1 . " (" . $taxon_hierarchy[$tax - 1] . "): " . $sql );

            $res = pg_query_params($sql, array());
            if ($res === false) $overallres = 0;
        }
        */

        //remove temporary indexes
        //$this->log("PopulateHigherTaxonomy - dropping temporary indexes on occurrence table");

        //for ($tax = 1; $tax < count($taxon_hierarchy); $tax++) {
        //    $sql = "DROP INDEX IF EXISTS idx_occurrence_" . $taxon_hierarchy[$tax];
        //    $res = pg_query_params($sql, array());
        //}
        //re-enable autovacuum
        pg_query_params("ALTER TABLE occurrence_processed SET ( autovacuum_enabled = TRUE, toast.autovacuum_enabled = TRUE )", array());
        $this->log("PopulateHigherTaxonomy - Leave");

        return $overallres;
    }

    private function PopulateDatasetName() {
        $this->log("Entering PopulateDatasetName");
        $sql = "UPDATE occurrence o SET datasetname = d.title FROM dataset d WHERE o.datasetkey = d.datasetkey AND (o.datasetname IS NULL or o.datasetname = '')";
        $res = pg_query_params($sql, array());
        if ($res === false) {
            $this->log("Error populating dataset names in occurrence table");
            return false;
        } else {
            $this->log("Leaving PopulateDatasetName");
            return true;
        }
    }

    //migrate valid content from limbo dataset into main db and do post-processing
//return -1 on success, 0 on any failures
    function ImportDwCData($filename, $data_type, $skip_occurrence_higher_taxonomy)
    {
        global $siteconfig;
        $this->log("ImportDwCData - Enter [" . $filename . "]");

        $idx_fields = array("_id", "scientificname", "taxonrank", "taxonkey", "acceptedtaxonkey", "datasetkey" /*, "countrycode",
            "institutioncode", "collectioncode", "basisofrecord", "recordedby", "year", "month", "stateprovince" */);
        $overallres = -1;
        $datasetid = $this->GetFileWithoutExtFromPath($filename);

        $this->log("Importing DWCdata from " . $datasetid);
        //scan limbo schema for any tables prefixed with the datasetid


        if ($data_type == "occurrence") {
            $source_id_field = $this->GetPrimaryKeyFirstField('limbo.gbif_data_occurrence'); // "" if none
            $this->log("GBIF data: truncating entire occurrence table");
            pg_query_params("TRUNCATE occurrence", array());

            $this->log("Temporarily removing occurrence table indexes");
            foreach ($idx_fields as $idx_field) {
                $sql = "DROP INDEX IF EXISTS idx_occurrence_" . $idx_field;
                $res = pg_query_params($sql, array());
            }
            $sql = "INSERT INTO occurrence (";
            $fieldnamearray = $this->GetValidFieldNameArrayWithoutTypeMatch('limbo.gbif_data_occurrence', 'occurrence');
            foreach ($fieldnamearray as $field) {
                $sql .= "\"" . strtolower($field) . "\", "; //my simpledwc table has lowercase field names to simplify use in postgreSQL
            }
            $sql = substr($sql,  0,strlen($sql)-2); //remove trailing comma
            if ($source_id_field != "") $sql .= ", _sourcerecordid";
            $sql .= ") SELECT ";
            foreach ($fieldnamearray as $field) {
                $sql .= "\"" . $field . "\", "; //source table might have mixed case field names
            }
            $sql = substr($sql,  0,strlen($sql)-2);
            if ($source_id_field != "") $sql .= ", \"" . $source_id_field . "\" as _sourcerecordid";
            $sql .= " FROM " . 'limbo.gbif_data_occurrence';
            $this->log("Copying across to main database using: " . $sql);
            $res = pg_query_params($sql, array()); //copy valid fields from dataset across
            $this->log("Recreating occurrence table indexes");
            foreach ($idx_fields as $idx_field) {
                $sql = "CREATE INDEX IF NOT EXISTS idx_occurrence_" . $idx_field . " ON occurrence (" . $idx_field . ")";
                $res = pg_query_params($sql, array());
            }
            if ($res === false) {
                //problem inserting into main occurrence table
                $this->log("SQL copy to main database failed!");
                $overallres = 0;
            }
            $res = $this->PopulateDatasetName();
            if (!$res) {
                $this->log("Populate dataset name in occurrences failed!");
            }
            // now create occurrence_processed records
            $res = $this->CreateOccurrenceProcessed();
            if (!$res) {
                $this->log("Process occurrences failed!");
                $overallres = 0;
            } else {
                //now process lat/long fields
                $res = $this->PopulateCoordinates();
                if (!$res) {
                    $this->log("Populate coordinates and spatial geometries failed!");
                    $overallres = 0;
                }
                if (!$skip_occurrence_higher_taxonomy) {
                    //now process taxon information
                    $res = $this->PopulateHigherTaxonomy();
                    if (!$res) {
                        $this->log("Populate higher taxonomy failed!");
                        $overallres = 0;
                    }
                }
            }
        }

        if ($data_type == "taxon") {

            pg_query_params("TRUNCATE limbo.gbif_taxon", array());
            $sql = "COPY limbo.gbif_taxon FROM '" . $siteconfig['path_basetasksfolder'] . "/gbif_taxon/gbif_taxon.csv' DELIMITER '\t' CSV HEADER";

            $res = pg_query_params($sql, array());
            if ($res === false) {
                $this->log("Error loading taxon CSV file into limbo schema");
                $this->log("ImportDwCData - Leave");
                return 0;
            }
            $this->log("GBIF data: truncating entire taxon table");
            pg_query_params("TRUNCATE taxon", array());
            $sql = "INSERT INTO taxon (";
            $fieldnamearray = $this->GetValidFieldNameArrayWithoutTypeMatch('limbo.gbif_taxon', 'taxon');
            foreach ($fieldnamearray as $field) {
                $sql .= "\"" . strtolower($field) . "\", "; //my table has lowercase field names to simplify use in postgreSQL
            }
            $regions = "'{true,true,true}'";
            $sql .= " _regions";
            if ($source_id_field != "") $sql .= ", _sourcerecordid";
            $sql .= ") SELECT ";
            foreach ($fieldnamearray as $field) {
                $sql .= "\"" . $field . "\", "; //source table might have mixed case field names
            }
            $sql .= $regions;
            if ($source_id_field != "") $sql .= ", \"" . $source_id_field . "\" as _sourcerecordid";
            $sql .= " FROM " . 'limbo.gbif_taxon';
            $res = pg_query_params($sql, array()); //copy valid fields from dataset across
            if ($res === false) {
                $this->log("Error copying gbif_taxon to main schema");
                $this->log("ImportDwCData - Leave");
                return 0;
            }

            //now add _species_with_synof data
            pg_query_params("UPDATE taxon SET _species_with_synof = species WHERE taxonomicstatus='ACCEPTED' AND taxonrank IN ('SPECIES','SUBSPECIES','VARIETY','FORM')", array());
            pg_query_params("UPDATE taxon SET _species_with_synof = concat(species,' = ',acceptedscientificname) WHERE taxonomicstatus!='ACCEPTED' AND taxonrank IN ('SPECIES','SUBSPECIES','VARIETY','FORM')", array());
            //set taxon regional affiliation.  This needs to be updated when a dataset is updated.

            //pg_query_params("UPDATE taxon t SET _regions = $1", array($regions)); //TODO: rework this. Taxa do not belong to specific 'master' datasets now, with only one GBIF master dataset (and might be found in many of the contained datasets)
            //now rebuild occurrence data taxonomic details
            $this->PopulateHigherTaxonomy();
        }

        $this->log("ImportDwCData - Leave");
        return $overallres;
    }

    //populates dataset-level metadata fields from the datasetmetadata dump table
//returns -1 on success, 0 on faiulre
    function PopulateDatasetCleanMetadata()
    {
        //_has_occurrence, _has_taxon - these are set when importing the data
        //following functions sets the follwing in the main dataset table from the datasetmetadata table
        //_creator
        //_creator_org
        //_contact
        //_contact_org
        //_keywords
        //_citation
        $this->log("PopulateDatasetCleanMetadata - Enter");

        $res = pg_query_params("SELECT UpdateDataset_CleanMetadata()", array());
        $this->log("PopulateDatasetCleanMetadata - Leave");
        return -1; //assume success
    }

    function ProcessDwCMetadata($metadatafile)
    {
        //$this->log("ProcessDwCMetadata - Enter - [" . $metadatafile . ", " . ($datasetid == ""? "(dataset from file)" : $datasetid) . "]");

        $metadata = simplexml_load_file($metadatafile);
        if (!$metadata) return 0; //invalid xml or some other error
        $meta_array = json_decode(json_encode($metadata), TRUE);

        $meta_flat = FlattenArray($meta_array);
        //Create simple one-dimensional array of key=>values,
        //where key is composed of fully contextualised XML elements, nested elements being concatenated with '.'
        //attributes of XML elements are indicated with prefix '@'
        //empty elements are not included in the array
        //ready to be inserted into DB with each array element recorded as <dataset_id><attribute><value>

        $datasetid = $meta_flat['dataset.title'];
        $datasetkey = $this->GetFileWithoutExtFromPath($metadatafile);

        //clear any existing metadata for the dataset
        pg_query_params("DELETE FROM datasetmetadata WHERE datasetid = $1", array($datasetid));

        //check dataset exists in table: some are not listed in rights.txt for some reason
        $ds = pg_query_params("SELECT datasetid FROM dataset WHERE datasetid = $1", array($datasetid));
        if (pg_num_rows($ds) < 1) {
            $color = '#' . substr('00000' . dechex(mt_rand(0, 0xffffff)), -6);
            $timestamp = time();
            $regions = "{true,true,true}";
            $sql = "INSERT INTO dataset (title, licence, addedtoportal, date_timestamp, datasetid, color, toremove, _has_occurrence, _has_taxon, _regions) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)";
            $vals = array($datasetid, "Unknown", "true", $timestamp, $datasetid, $color, "false", "true", "false", $regions);
            $res = pg_query_params($sql, $vals);
            if ($res === false) {
                $this->log("Error adding dataset with: " . implode("; ", $vals));
            }
        }
        $sql = "UPDATE dataset SET datasetkey = $1 WHERE datasetid = $2";
        $vals = array($datasetkey, $datasetid);
        $res = pg_query_params($sql, $vals);
        if ($res === false) {
            $this->log("Error adding dataset key with: " . implode("; ", $vals));
        }

        //var_dump($meta_flat);
        foreach ($meta_flat as $key => $value) {
            $res = pg_query_params("INSERT INTO datasetmetadata (datasetid, strattribute, strvalue, strfile) VALUES ($1, $2, $3, $4)", array($datasetid, $key, substr($value,0,2000), "eml"));
        }
        //$this->log("ProcessDwCMetadata - Leave");

        return -1;
    }

    function SplitFile($sqlfile) {
        $numparts = 0;
        $handle_read = fopen($sqlfile, "r");
        $file_out = $sqlfile . "_" . $numparts;
        $handle_write = fopen($file_out, "w");
        //fwrite($handle_write, "SET CLIENT_ENCODING TO 'utf8';" . PHP_EOL);
        //$set_client_length = strlen("SET CLIENT_ENCODING");
        $lineno = 0;
        $insertno = 0;
        while(!feof($handle_read)) {
            $line = trim(fgets($handle_read));
            $lineno++;
            if (substr($line,0,6) == "INSERT") {
                if ($insertno >= 1000) {
                    //= 1000 * 100 = 100k records
                    fclose($handle_write);
                    $numparts++;
                    $file_out = $sqlfile . "_" . $numparts;
                    $handle_write = fopen($file_out, "w");
                    //fwrite($handle_write, "SET CLIENT_ENCODING TO 'utf8';" . PHP_EOL);
                    $insertno = 0;
                } else {
                    $insertno++;
                }

            }
            fwrite($handle_write, $line . PHP_EOL);
            /*
            if (($lineno - 2) % $insert_bulk_rows == $insert_bulk_rows-1) {
                if ($insertno >= 1000) {
                    //=2000 * 100 = 200k records
                    fclose($handle_write);
                    $numparts++;
                    $file_out = $sqlfile . "_" . $numparts;
                    $handle_write = fopen($file_out, "w");
                    $insertno = 0;
                } else {
                    $insertno++;
                }
            } */
        }
        fclose($handle_write);
        fclose($handle_read);

        return $numparts;
    }

    function ProcessDwCRecords($skip_sql_creation = false)
    {
        $this->log("ProcessDwCRecords - Enter");

        global $siteconfig;
        $insert_bulk_rows = 100;

        $sqlfile = "gbif_data.sql";
        $output = array();

        if (!$skip_sql_creation) {
            $dwca2sql = "\"" . $siteconfig['path_java_exe'] . "\"  -jar \"" . $siteconfig['path_basefolder'] . "/lib/dwca_import/dwca2sql.jar\" -ci -s " . "gbif_data" . " -o " . "gbif_data.sql" . " -p " . "gbif_data" . " -f true --max-row-per-insert " . $insert_bulk_rows;
            $this->log("Processing DWCA to SQL with: " . $dwca2sql);

            exec($dwca2sql, $output);

            if (isset($output[1])) {
                if (!in_array("Successfully generated:", $output)) {
                    $errormsg = implode(" ", $output);
                    $this->log("Error: " . $errormsg);
                    return 0;
                }
            } else {
                $errormsg = implode(" ", $output);
                $this->log("Error: " . $errormsg);
                return 0;
            }
        }
        //import SQL into 'limbo' schema as a single transaction
        //NOTE: user for this command only has rights to limbo schema
        $output2 = array();
        $this->log("Loading data to limbo schema with: " . "\"" . $siteconfig['path_psql_exe'] . "\" -U " . $siteconfig['limbo_user'] . " -d " . $siteconfig['dwc_db'] . " -1 -f \"" . $sqlfile . "\" -p " . $siteconfig['dwc_port'] );

        //debugging
        //$numparts = $this->SplitFile($sqlfile);

        exec("\"" . $siteconfig['path_psql_exe'] . "\" -U " . $siteconfig['limbo_user'] . " -d " . $siteconfig['dwc_db'] . " -1 -f \"" . $sqlfile . "\" -p " . $siteconfig['dwc_port'], $output2);
        if (isset($output2[0])) {
            if (stripos(implode(" ", $output2), "Error") !== false) {
                $errormsg = implode(" ", $output2);
                $this->log("Error: " . $errormsg);
                return 0;
            }
        } else {
            $errormsg = implode(" ", $output2);
            $this->log("Error: " . $errormsg);
            return 0;
        }

        $this->log("ProcessDwCRecords - Leave");

        return -1; //success
    }

    //rebuild the summary occurrence_grid coverage
//Aug 2014: now populates all summary grid tables
    function PopulateCoordinateGrid()
    {
        $this->log("PopulateCoordinateGrid - Enter");

        $overallres = -1;
        //reason for subtracting 0.5 is because grid is otherwise shifted 'right' and 'up' due to rounding.
        $res = pg_query_params("SELECT UpdateOccurrence_Grid_Albertine()", array());
        if ($res === false) $overallres = 0; //problem updating occurrence_grid
        $res = pg_query_params("SELECT UpdateOccurrence_Grid_Mountains()", array());
        if ($res === false) $overallres = 0; //problem updating occurrence_grid
        $res = pg_query_params("SELECT UpdateOccurrence_Grid_Lakes()", array());
        if ($res === false) $overallres = 0; //problem updating occurrence_grid
        $this->log("PopulateCoordinateGrid - Leave");

        return $overallres;
    }

    function UpdateSummaryViewTables()
    {
        $this->log("UpdateSummaryViewTables - Enter");

        //The functions below do the following:
        //drop indexes
        //truncate tables
        //reinsert into tables
        //recreate indexes

        $this->log("UpdateSummaryViewTables - species summary");
        $res_spp = pg_query_params("SELECT updatesummary_spp()", array());
        $this->log("UpdateSummaryViewTables - occurrence list");
        $res_occ_list = pg_query_params("SELECT updatesummary_occlist()", array());
        $this->log("UpdateSummaryViewTables - occurrence summary");
        $res_occ_sum = pg_query_params("SELECT updatesummary_occsum()", array());
        $this->log("UpdateSummaryViewTables - Leave");

        return -1;
    }

    // update the db with the metadata and load the DwC records into limbo schema
//returns -1 on success, 0 on failure
    function ProcessDwC($datasetid, $dwcpath, $skip_sql_creation = false) {
        $this->log("ProcessDwC - Enter - [" . $datasetid . "]");
        pg_query_params("TRUNCATE dataset CASCADE", array());
        if (file_exists($dwcpath . "/rights.txt")) {
            $file = fopen($dwcpath . "/rights.txt","r");
            while(! feof($file))
            {
                $line = trim(fgets($file));
                if (substr($line,0, strlen("Dataset:")) == "Dataset:") { //next line has licence
                    $dataset = substr($line, strlen("Dataset:")+1);
                    $licence = "";
                    if (!feof($file)) {
                        $line2 = trim(fgets($file));
                        if (substr($line2,0, strlen("Rights as supplied:")) == "Rights as supplied:") {
                            $licence = substr($line2, strlen("Rights as supplied:")+1);
                        } else { //try to get everything but descriptor
                            $line2_arr = explode(":", $line2);
                            if (sizeof($line2_arr) > 1) $line2_arr = array_shift($line2_arr);
                            $licence = implode(":", $line2_arr);
                        }
                    }
                    $color =  '#' . substr('00000' . dechex(mt_rand(0, 0xffffff)), -6);
                    $timestamp = time();
                    $regions = "{true,true,true}";
                    $sql = "INSERT INTO dataset (title, licence, addedtoportal, date_timestamp, datasetid, color, toremove, _has_occurrence, _has_taxon, _regions) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)";
                    $vals = array($dataset, $licence, "true", $timestamp, $dataset, $color, "false", "true", "false", $regions);
                    $res = pg_query_params($sql, $vals);
                    if ($res === false) {
                        $this->log("Error adding dataset with: " . implode("; ", $vals));
                    }
                }
            }

            fclose($file);

        }

        $res2 = 0;

        $res1 = 0;
        if (file_exists($dwcpath . '/dataset/')) {
            $this->log("Reading individual dataset metadata files");
            $files = glob($dwcpath . '/dataset/*.{xml}', GLOB_BRACE);
            foreach ($files as $file) {
                $this->log("Processing metadata in " . $file);
                $res1 = $this->ProcessDwCMetadata($file); //determine dataset from file contents
            }
        }

        if ($res1 + $res2 == 0) {
            $this->log("No metadata files found in " . $dwcpath);
        }

        //TODO: use meta.xml to check we have all the fields in limbo.gbif_data_occurrence

        //could now check to make sure we have all fields and amend schema if needs be, which would be slow
        //maybe better to simply load from the text file in that case?

        $res3 = $this->ProcessDwCRecords($skip_sql_creation);
        if (!$res3) $this->log("Error processing DWCA");
        $this->log("ProcessDwC - Leave");

        return (($res1 || $res2) && $res3); //need at least one metadata file and main dataset to pass

    }


    //can skip process DWC when testing (after already loading DWC into limbo table once)
    public function LoadGBIF_data($skip_occurrence_higher_taxonomy = false, $skip_processDwC = false, $skip_sql_creation = false) {
        $this->log("LoadGBIF_data - Enter");

        $already_running = $this->IsSemaphorSet();
        if ($already_running) {
            $this->log("GBIF load already running (or failed unexpectedly). Restarting...");
            //TODO: need better way of deciding whether to abort or continue
            //return 0;
        }
        $this->SetSemaphor();
        $this->log("Starting GBIF load: gbif_data");
        $this->log_summary("Loading GBIF occurrence data from latest download file");
        $dwcpath = 'gbif_data';
        $datasetid = 'gbif_data';
        $res = true;
        if (!$skip_processDwC) {
            $res = $this->ProcessDwC($datasetid, $dwcpath, $skip_sql_creation); //unpack file, put metadata into datasetmetadata table, put dwc table(s) into limbo schema
        }
        if (!$res) {
            $this->log("Process DWC error - aborting remaining process, database will be left unchanged");
        } else {
            $this->log_summary("Occurrence data loaded to temporary database");
            $this->PopulateDatasetCleanMetadata();
            $res = $this->ImportDwCData($dwcpath, "occurrence", $skip_occurrence_higher_taxonomy); //now parse across into main DB and fix up geometry
            if (!$res) {
                $this->log("Failed to import data");
            } else {
                $this->log("GBIF records transferred to main database, now updating summaries");
                $this->log_summary("GBIF records transferred to main database, now updating summaries");
                $this->PopulateCoordinateGrid();
                if (!$skip_occurrence_higher_taxonomy) {
                    $this->UpdateSummaryViewTables();
                }
            }

        }

        $this->UnsetSemaphor();
        $this->log("LoadGBIF_data - Leave");
        $res = pg_query_params("SELECT count(*) as totalrecs from occurrence", array());
        $totalrecs = 'error';
        if ($res) {
            $row = pg_fetch_array($res);
            $totalrecs = $row['totalrecs'];
        }
        $res2 = pg_query_params("SELECT count(*) as totalrecs from dataset", array());
        $totaldatasets = 'error';
        if ($res2) {
            $row = pg_fetch_array($res2);
            $totaldatasets = $row['totalrecs'];
        }
        $this->log("Finished loading GBIF occurrence data from latest download file: " . $totalrecs . " occurrence records from " . $totaldatasets . " datasets");
        $this->log_summary("Finished loading GBIF occurrence data from latest download file:");
        $this->log_summary(" = " . $totalrecs . " occurrence records");
        $this->log_summary(" = " . $totaldatasets . " datasets");
        $this->log_summary("");
        return 0;
    }

    public function LoadGBIF_taxon() {
        $this->log("LoadGBIF_taxon - Enter");

        $already_running = $this->IsSemaphorSet();
        if ($already_running) {
            $this->log("GBIF load already running (or failed unexpectedly). Restarting...");
        }
        $this->SetSemaphor();
        $this->log("Starting GBIF load: gbif_taxon");
        $this->log_summary("Loading GBIF taxon data from latest download file");
        $dwcpath = 'gbif_taxon';
        $datasetid = 'gbif_taxon';
        $res = true;
        //$res = $this->ProcessDwCRecords($dwcpath, $datasetid, $skip_sql_creation = false);
        if (!$res) {
            $this->log("Process DWC error - aborting remaining process, database will be left unchanged");
        } else {
            $res = $this->ImportDwCData($dwcpath, "taxon", false); //now parse across into main DB and fix up geometry
            if (!$res) {
                $this->log("Failed to import data");
                $this->log_summary("Failed to import GBIF taxon data - please notify technical support");
            }
        }
        $this->UpdateSummaryViewTables();
        $this->UnsetSemaphor();
        $this->log("LoadGBIF_taxon - Leave");
        $res = pg_query_params("SELECT count(*) as totalrecs from taxon", array());
        $totalrecs = 'error';
        if ($res) {
            $row = pg_fetch_array($res);
            $totalrecs = $row['totalrecs'];
        }
        $this->log("Finished loading GBIF taxon data from latest download file: total taxon records = " . $totalrecs);
        $this->log_summary("Finished loading GBIF taxon data from latest download file:");
        $this->log_summary(" = " . $totalrecs . " taxon records");
        $this->log_summary("");
        return 0;
    }
}
$load_type = "";
if (isset($argc) && $argc > 1) {
    $load_type = $argv[1];
}

if ($load_type != "gbif_data" && $load_type != "gbif_taxon" && $load_type != 'both') {
    echo "Pass parameter 'gbif_data' (occurrence data) or 'gbif_taxon' (taxonomic backbone) or 'both'";
    exit;
}
$gbif = new GBIF_load();
if ($load_type == 'gbif_data' || $load_type == 'both') {
    $gbif->LoadGBIF_data($load_type == 'both'); //, false, true);
}
if ($load_type == 'gbif_taxon' || $load_type == 'both') {
    $gbif->LoadGBIF_taxon();
}
$gbif->log_summary("");

//$gbif->ProcessDwC('gbif_data', 'gbif_data');
//$gbif->ImportDwCData("gbif_data");
//$this->PopulateCoordinateGrid();
//$this->UpdateSummaryViewTables();
//echo $gbif->SplitFile('gbif_data_subset.sql', 100);
?>
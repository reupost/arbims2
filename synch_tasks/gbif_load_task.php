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
    function CreateOccurrenceProcessed($datasetid)
    {
        $this->log("CreateOccurrenceProcessed - Enter [" . $datasetid . "]");

        $this->log("GBIF data: truncating occurrence_processed");
        pg_query_params("TRUNCATE occurrence_processed", array());

        $this->log("Creating occurrence record stubs in occurrence_processed");
        $res = pg_query_params("INSERT INTO occurrence_processed (_id, _datasetid) SELECT _id, _datasetid FROM occurrence o", array());
        $this->log("CreateOccurrenceProcessed - Leave");

        if ($res === false) return 0; //problem
        return -1;
    }

    //populate metadata coordinate info for a dataset, or all occurrence records
//returns -1 on success, 0 on failure
    function PopulateCoordinates($datasetid = "")
    {
        $this->log("PopulateCoordinates - Enter [" . $datasetid . "]");

        $overallres = -1;
        $this->log("Temporarily dropping spatial indexes from occurrence_processed");
        $spatial_idxs = array("_deci_latitude", "_deci_longitude", "_decimallatitude", "_decimallongitude");
        foreach ($spatial_idxs as $spatial_idx) {
            //$spatial_idxs_joined = implode(", ", $spatial_idxs); TODO - can drop all at once
            $sql = "DROP INDEX IF EXISTS idx_occurrence_processed_" . $spatial_idx;
            $res = pg_query_params($sql, array());
        }
        /* if ($datasetid != "" && $datasetid != "gbif_data") {
            $res = pg_query_params("SELECT UpdateOccurrence_Coordinates($1)", array($datasetid));
        } else { */
            $res = pg_query_params("SELECT UpdateOccurrence_Coordinates()", array());
        //}
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
        $sql .= "	CASE WHEN coalesce(scientificname,'') != '' AND lower(taxonrank) IN ('species','subspecies','subsp.','variety','var.') THEN ";
        $sql .= "		SUBSTRING(scientificname FROM POSITION(' ' IN scientificname)+1) ";
        $sql .= "	ELSE ";
        $sql .= "       CASE WHEN coalesce(acceptednameusage,'') != '' THEN ";
        $sql .= "		    SUBSTRING(acceptednameusage FROM POSITION(' ' IN acceptednameusage)+1) ";
        $sql .= "	    ELSE ";
        $sql .= "           NULL ";
        $sql .= "       END ";
        $sql .= "	END ";
        $sql .= "ELSE ";
        $sql .= "	CASE WHEN coalesce(infraspecificepithet,'') = '' THEN ";
        $sql .= "		specificepithet ";
        $sql .= "	ELSE ";
        $sql .= "		concat(specificepithet, ' ', taxonrank, ' ', infraspecificepithet) ";
        $sql .= "	END ";
        $sql .= "END ";
        return $sql;
    }

    //populate metadata taxonomic info for a dataset, or all occurrence records
//returns -1 on success, 0 on failure
    function PopulateHigherTaxonomy($datasetid = "")
    {
        $this->log("PopulateHigherTaxonomy - Enter [" . $datasetid . "]");

        $overallres = -1;

        //work up the scale from genus->family->order->class->phylum->kingdom, using whatever got on previous level to populate next level
        $taxon_hierarchy = array('species', 'genus', 'family', 'order', 'class', 'phylum', 'kingdom');

        //first disable autovacuum for the duration of the process
        pg_query_params("ALTER TABLE occurrence_processed SET ( autovacuum_enabled = FALSE, toast.autovacuum_enabled = FALSE )", array());

        //add temporary indexes to source fields (note: ignore 'species' since this is a synthetic field not in DwC)

        $this->log("PopulateHigherTaxonomy - Adding temporary taxon indexes to occurrence");

        for ($tax = 1; $tax < count($taxon_hierarchy); $tax++) {
            $sql = "CREATE INDEX IF NOT EXISTS idx_occurrence_" . $taxon_hierarchy[$tax] . " ON occurrence (\"" . $taxon_hierarchy[$tax] . "\")";
            $res = pg_query_params($sql, array());
        }

        //first blank any existing taxonomic metadata
        $this->log("PopulateHigherTaxonomy - Removing taxon indexes on occurrence processed");

        if ($datasetid != "" && $datasetid != "gbif_data") {
            //remove indexes on fields which will be updated, these indexes will be added again at the end
            for ($tax = 0; $tax < count($taxon_hierarchy); $tax++) {
                $sql = "DROP INDEX IF EXISTS idx_occurrence_processed__" . $taxon_hierarchy[$tax];
                $res = pg_query_params($sql, array());
            }
            $res = pg_query_params("UPDATE occurrence_processed SET _species = NULL, _genus = NULL, _family = NULL, _order = NULL, _class = NULL, _phylum = NULL, _kingdom = NULL WHERE (_datasetid = $1)", array($datasetid));

            $this->log("PopulateHigherTaxonomy - Removed taxon indexes and cleared taxon fields");

        } else {
            $this->log("Dropping higher taxonomy fields and recreating them (quicker than deleting indexes)");
            //quickest to drop and recreate fields - this automatically drops the indexes as well
            for ($tax = 0; $tax < count($taxon_hierarchy); $tax++) {
                $sql = "ALTER TABLE occurrence_processed DROP IF EXISTS _" . $taxon_hierarchy[$tax];
                $res = pg_query_params($sql, array());
                $sql = "ALTER TABLE occurrence_processed ADD COLUMN _" . $taxon_hierarchy[$tax] . " text";
                $res = pg_query_params($sql, array());
            }
            $this->log("PopulateHigherTaxonomy - Dropped and re-added taxon fields");
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
        if ($datasetid != "" && $datasetid != "gbif_data") {
            $sql .= "AND o._datasetid = '" . pg_escape_string($datasetid) . "'";
        }
        $this->log("PopulateHigherTaxonomy - about to fill occurrence processed taxonomy with " . $sql);

        $res = pg_query_params($sql, array());
        $this->log("PopulateHigherTaxonomy - filled occurrence processed taxonomy");


        //genus: special case - extract from scientificName OR acceptedNameUsage if there was no explicit genus set
        $this->log("PopulateHigherTaxonomy - fixing occurrence processed genus");

        if ($datasetid != "" && $datasetid != "gbif_data") {
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
        $this->log("PopulateHigherTaxonomy - adding occurrence processed taxonomy indexes");

        for ($tax = 0; $tax < count($taxon_hierarchy); $tax++) {
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
        // to distringuish between y/b family option
        //   x     ?      z
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

        //remove temporary indexes
        $this->log("PopulateHigherTaxonomy - dropping temporary indexes on occurrence table");

        for ($tax = 1; $tax < count($taxon_hierarchy); $tax++) {
            $sql = "DROP INDEX IF EXISTS idx_occurrence_" . $taxon_hierarchy[$tax];
            $res = pg_query_params($sql, array());
        }
        //re-enable autovacuum
        pg_query_params("ALTER TABLE occurrence_processed SET ( autovacuum_enabled = TRUE, toast.autovacuum_enabled = TRUE )", array());
        $this->log("PopulateHigherTaxonomy - Leave");

        return $overallres;
    }


    //migrate valid content from limbo dataset into main db and do post-processing
//return -1 on success, 0 on any failures
    function ImportDwCData($filename)
    {
        $this->log("ImportDwCData - Enter [" . $filename . "]");
        global $siteconfig;

        $idx_fields = array("_datasetid","_id", "scientificname");
        $overallres = -1;
        $datasetid = $this->GetFileWithoutExtFromPath($filename);

        $this->log("Importing DWCdata from " . $datasetid);
        //scan limbo schema for any tables prefixed with the datasetid
        $cleandatasetid = strtolower(str_replace("-", "_", $datasetid));
        $result = pg_query_params("SELECT table_name FROM information_schema.tables WHERE (table_schema=$1 AND table_name LIKE $2 || '_%')", array($siteconfig['schema_limbo'], $cleandatasetid));
        if (!$result) {
            $this->log("SQL error in ImportDwCData");
            return 0;
        } //error
        while ($table = pg_fetch_array($result)) {
            $source_id_field = $this->GetPrimaryKeyFirstField($table['table_name']); // "" if none
            //make sure limbo table fields are in the main db table, and [current not checking] fieldtypes are compatible.
            //occurrence tables must be compared to the main schema occurrence table
            if ($table['table_name'] == $cleandatasetid . "_occurrence") {
                pg_query_params("UPDATE dataset SET _has_occurrence = true WHERE (datasetid = $1)", array($datasetid)); //TODO: fix with new dataset model

                $this->log("Deleting old occurrence records where _datasetid = " . $datasetid);
                if ($datasetid == "gbif_data") {
                    $this->log("GBIF data: truncating entire occurrence table");
                    pg_query_params("TRUNCATE occurrence", array());
                } else {
                    pg_query_params("DELETE FROM occurrence WHERE (_datasetid = $1)", array($datasetid));
                }
                $this->log("Temporarily removing occurrence table indexes");
                foreach($idx_fields as $idx_field) {
                    $sql = "DROP INDEX IF EXISTS idx_occurrence_" . $idx_field;
                    $res = pg_query_params($sql, array());
                }
                $sql = "INSERT INTO occurrence (";
                $fieldnamearray = $this->GetValidFieldNameArrayWithoutTypeMatch($table['table_name'], 'occurrence');
                foreach ($fieldnamearray as $field) {
                    $sql .= "\"" . strtolower($field) . "\", "; //my simpledwc table has lowercase field names to simplify use in postgreSQL
                }
                $sql .= " _datasetid";
                if ($source_id_field != "") $sql .= ", _sourcerecordid";
                $sql .= ") SELECT ";
                foreach ($fieldnamearray as $field) {
                    $sql .= "\"" . $field . "\", "; //source table might have mixed case field names
                }
                //$sql .= "'" . pg_escape_string($datasetid) . "' as _datasetid";
                $sql .= "\"" . "datasetName" . "\" as _datasetid";
                if ($source_id_field != "") $sql .= ", \"" . $source_id_field . "\" as _sourcerecordid";
                $sql .= " FROM " . $table['table_name'];
                $this->log("Copying across to main database using: " . $sql);
                $res = pg_query_params($sql, array()); //copy valid fields from dataset across
                $this->log("Recreating occurrence table indexes");
                foreach($idx_fields as $idx_field) {
                    $sql = "CREATE INDEX IF NOT EXISTS idx_occurrence_" . $idx_field . " ON occurrence (" . $idx_field . ")";
                    $res = pg_query_params($sql, array());
                }
                if ($res === false) {
                    //problem inserting into main occurrence table
                    $this->log("SQL copy to main database failed!");
                    $overallres = 0;
                }
                // now create occurrence_processed records
                $res = $this->CreateOccurrenceProcessed($datasetid);
                if (!$res) {
                    $this->log("Process occurrences failed!");
                    $overallres = 0;
                } else {
                    //now process lat/long fields
                    $res = $this->PopulateCoordinates($datasetid);
                    if (!$res) {
                        $this->log("Populate coordinates and spatial geometries failed!");
                        $overallres = 0;
                    }
                    //now process taxon information
                    $res = $this->PopulateHigherTaxonomy($datasetid);
                    if (!$res) {
                        $this->log("Populate higher taxonomy failed!");
                        $overallres = 0;
                    }
                }
            }
            //TODO: need to look at this ***
            if ($table['table_name'] == $cleandatasetid . "_taxon") {
                pg_query_params("UPDATE dataset SET _has_taxon = true WHERE (datasetid = $1)", array($datasetid));
                pg_query_params("DELETE FROM taxon WHERE (_datasetid = $1)", array($datasetid));
                $sql = "INSERT INTO taxon (";
                $fieldnamearray = $this->GetValidFieldNameArrayWithoutTypeMatch($table['table_name'], 'taxon');
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
                $res = pg_query_params($sql, array()); //copy valid fields from dataset across
                if ($res === false) $overallres = 0; //problem inserting into main taxon table

                //now fix capitalisation
                pg_query_params("UPDATE taxon SET kingdom = initcap(coalesce(kingdom,'')), phylum = initcap(coalesce(phylum,'')), \"class\" = initcap(coalesce(\"class\",'')), \"order\" = initcap(coalesce(\"order\",'')), family = initcap(coalesce(family,'')), genus = initcap(coalesce(genus,'')), species = " . GetSetSpeciesSQL() . " WHERE _datasetid = $1", array($datasetid));
                //populate scientificname where possible
                pg_query_params("UPDATE taxon SET scientificname = trim(both from (concat(genus,' ',specificepithet,' ',(trim(both from (concat(infraspecificepithet,' ',scientificnameauthorship))))))) WHERE (_datasetid = $1 AND coalesce(scientificname,'') = '' AND genus > '' AND specificepithet > '')", array($datasetid));
                //now add _species_wth_synof data
                pg_query_params("UPDATE taxon SET _species_with_synof = concat(species,' = ',tax_syns.currentname) FROM (SELECT t1._id, t2.scientificname as currentname from taxon t1 JOIN taxon t2 ON t1.acceptednameusageid = t2.taxonid AND t1._datasetid = t2._datasetid) tax_syns WHERE tax_syns._id = taxon._id AND _datasetid = $1", array($datasetid));
                //set taxon regional affiliation.  This needs to be updated when a dataset is updated.
                pg_query_params("UPDATE taxon t SET _regions = d._regions FROM dataset d WHERE t._datasetid = d.datasetid AND t._datasetid = $1", array($datasetid));
                //now rebuild occurrence data taxonomic details if the dataset is the master backbone dataset
                if ($datasetid == TAXONOMIC_BACKBONE) $this->PopulateHigherTaxonomy();
            }
        }
        $this->log("ImportDwCData - Leave");
        return $overallres;
    }

    //populates dataset-level metadata fields from the datasetmetadata dump table
//returns -1 on success, 0 on faiulre
    function PopulateDatasetCleanMetadata($datasetid = "")
    {
        //_has_occurrence, _has_taxon - these are set when importing the data
        //following functions sets the follwing in the main dataset table from the datasetmetadata table
        //_creator
        //_creator_org
        //_contact
        //_contact_org
        //_keywords
        //_citation
        $this->log("PopulateDatasetCleanMetadata - Enter [" . $datasetid . "]");

        $res = pg_query_params("SELECT UpdateDataset_CleanMetadata()", array());
        $this->log("PopulateDatasetCleanMetadata - Leave");
        return -1; //assume success
    }

    function ProcessDwCMetadata($metadatafile, $datasetid = "")
    {
        $this->log("ProcessDwCMetadata - Enter - [" . $metadatafile . ", " . ($datasetid == ""? "(dataset from file)" : $datasetid) . "]");

        $metadata = simplexml_load_file($metadatafile);
        if (!$metadata) return 0; //invalid xml or some other error
        $meta_array = json_decode(json_encode($metadata), TRUE);

        $meta_flat = FlattenArray($meta_array);
        //Create simple one-dimensional array of key=>values,
        //where key is composed of fully contextualised XML elements, nested elements being concatenated with '.'
        //attributes of XML elements are indicated with prefix '@'
        //empty elements are not included in the array
        //ready to be inserted into DB with each array element recorded as <dataset_id><attribute><value>

        if ($datasetid == "") $datasetid = $meta_flat['dataset.title'];

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

        //var_dump($meta_flat);
        foreach ($meta_flat as $key => $value) {
            $res = pg_query_params("INSERT INTO datasetmetadata (datasetid, strattribute, strvalue, strfile) VALUES ($1, $2, $3, $4)", array($datasetid, $key, substr($value,0,2000), "eml"));
        }
        $this->log("ProcessDwCMetadata - Leave");

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

    function ProcessDwCRecords($archive, $datasetid, $skip_sql_creation = false)
    {
        $this->log("ProcessDwCRecords - Enter - [" . $datasetid . "]");

        global $siteconfig;
        $insert_bulk_rows = 100;

        $sqlfile = $archive . ".sql";
        $output = array();
        $cleandatasetid = str_replace("-", "_", $datasetid); //otherwise the SQL table name is invalid
        if (!$skip_sql_creation) {
            $dwca2sql = "\"" . $siteconfig['path_java_exe'] . "\"  -jar \"" . $siteconfig['path_basefolder'] . "/lib/dwca_import/dwca2sql.jar\" -ci -s " . $archive . " -o " . $sqlfile . " -p " . $cleandatasetid . " -f true --max-row-per-insert " . $insert_bulk_rows;
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

        //if ($cleandatasetid == 'gbif_data') { //TODO: when loading taxonomic backbone need to not do this
        //    pg_query_params("TRUNCATE " . $cleandatasetid . "_occurrence", array());
        //}
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
        $res_spp = pg_query_params("SELECT UpdateSummary_Spp()", array());
        $this->log("UpdateSummaryViewTables - occurrence list");
        $res_occ_list = pg_query_params("SELECT UpdateSummary_OccList()", array());
        $this->log("UpdateSummaryViewTables - occurrence summary");
        $res_occ_sum = pg_query_params("SELECT UpdateSummary_OccSum()", array());
        $this->log("UpdateSummaryViewTables - Leave");

        return -1;
    }

    // update the db with the metadata and load the DwC records into limbo schema
//returns -1 on success, 0 on failure
    function ProcessDwC($datasetid, $dwcpath, $skip_sql_creation = false)
    {
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
                $res1 = $this->ProcessDwCMetadata($file, ""); //determine dataset from file contents
            }
        }

        if ($res1 + $res2 == 0) {
            $this->log("No metadata files found in " . $dwcpath);
        }

        //TODO: use meta.xml to check we have all the fields in limbo.gbif_data_occurrence

        //could now check to make sure we have all fields and amend schema if needs be, which would be slow
        //maybe better to simply load from the text file in that case?

        $res3 = $this->ProcessDwCRecords($dwcpath, $datasetid, $skip_sql_creation);
        if (!$res3) $this->log("Error processing DWCA");
        $this->log("ProcessDwC - Leave");

        return (($res1 || $res2) && $res3); //need at least one metadata file and main dataset to pass

    }


    //can skip process DWC when testing (after already loading DWC into limbo table once)
    public function LoadGBIF($skip_processDwC = false, $skip_sql_creation = false)
    {
        $this->log("LoadGBIF - Enter");

        global $siteconfig;

        $already_running = $this->IsSemaphorSet();
        if ($already_running) {
            $this->log("GBIF load already running (or failed unexpectedly). Restarting...");
            //TODO: need better way of deciding whether to abort or continue
            //return 0;
        }
        $this->SetSemaphor();
        $this->log("Starting GBIF load");
        $dwcpath = "gbif_data"; //TODO
        $datasetid = "gbif_data"; //TODO
        $res = true;
        if (!$skip_processDwC) {
            $res = $this->ProcessDwC($datasetid, $dwcpath, $skip_sql_creation); //unpack file, put metadata into datasetmetadata table, put dwc table(s) into limbo schema
        }
        if (!$res) {
            $this->log("Process DWC error - aborting remaining process, database will be left unchanged");
        } else {
            $this->PopulateDatasetCleanMetadata($datasetid);
            $res = $this->ImportDwCData($dwcpath); //now parse across into main DB and fix up geometry
            if (!$res) {
                $this->log("Failed to import data");
            } else {
                $this->log("GBIF records transferred to main database, now updating summaries");
                $this->PopulateCoordinateGrid();
                $this->UpdateSummaryViewTables();
            }

        }

        $this->UnsetSemaphor();
        $this->log("LoadGBIF - Leave");
        return 0;
    }
}
$gbif = new GBIF_load();
$gbif->LoadGBIF(false, true);
//$gbif->ProcessDwC('gbif_data', 'gbif_data');
//$gbif->ImportDwCData("gbif_data");
//$this->PopulateCoordinateGrid();
//$this->UpdateSummaryViewTables();
//echo $gbif->SplitFile('gbif_data_subset.sql', 100);

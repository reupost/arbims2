<?php

/*
 * Manage the database interface for the occurrence collection
 */

require_once("includes/config.php");
require_once("includes/tools.php");
require_once("models/table_base.php");
require_once("includes/inc.language.php");

class TableOccurrence extends Table_Base {

    var $sql_listing = "select * from vw_occ_list1";
    var $sql_listing_occlist = "SELECT vol.* FROM vw_occ_list1 vol JOIN session_searchdata sd ON sd.occurrenceid = vol._id";
    var $use_occlist = false;
    
    var $fieldmap_orderby = array(
        "dataset" => "dataset_title, _genus, _species, catalognumber",
        "scientificname" => "_genus, _species, scientificname, catalognumber",
        "fulltaxonomy" => "_kingdom, _phylum, _class, _order, _family, _genus, _species, catalognumber",
        "institution" => "institutioncode, collectioncode, dataset_title, datasetname, catalognumber",
        "date" => "year, month, day, _genus, _species, catalognumber",
        "place" => "country, stateprovince, localitystart, _genus, _species, catalognumber",
        "basisofrecord" => "basisofrecord, _genus, _species, catalognumber"
    );
    var $fieldmap_filterby = array(
        "datasetid" => "_datasetid",
        "latitude" => "_decimallatitude",
        "longitude" => "_decimallongitude",
        "region:albertine" => "_regions[1]",
        "region:mountains" => "_regions[2]",
        "region:lakes" => "_regions[3]",
        "taxon" => "", //special case    
        "occlist" => "", //special case
        "filtercontent" => "to_tsvector('english', lower(coalesce(_kingdom,'') || ' ' || coalesce(_phylum,'') || ' ' || coalesce(_class,'') || ' ' || coalesce(_order,'') || ' ' || coalesce(_family,'') || ' ' || coalesce(_genus,'') || ' ' || coalesce(_species,'') || ' ' || coalesce(scientificname,'') || ' ' || coalesce(institutioncode,'') || ' ' || coalesce(collectioncode,'') || ' ' || coalesce(recordedby,'') || ' ' || coalesce(country,'') || ' ' || coalesce(stateprovince,'') || ' ' || coalesce(localitystart,'') || ' ' || coalesce(dataset_title,''))) @@ plainto_tsquery('***')",
        //advanced search options are passed directly as fields
    );
    
    var $AdvancedSearchFields = array(
        'dataset_title',
        '_family',
        '_genus',
        'institutioncode',
        'collectioncode',
        'basisofrecord',
        'recordedby',
        'year',
        'month',
        'country',
        'stateprovince'
    );
    
    var $sql_listing_download = "SELECT o.* FROM occurrence o JOIN (***) v ON o._id = v._id";
    
    var $DwCFieldOrdering = array(                
        'type',
        'modified',
        'language',
        'rights',
        'rightsholder',
        'accessrights',
        'bibliographiccitation',
        'references',
        'institutionid',
        'collectionid',  
        'datasetid',
        'institutioncode',
        'collectioncode',
        'datasetname',
        'ownerinstitutioncode',
        'basisofrecord',
        'informationwithheld',
        'datageneralizations',
        'dynamicproperties',
        'occurrenceid',
        'catalognumber',
        'occurrenceremarks',
        'recordnumber',
        'recordedby',
        'individualid',
        'individualcount',
        'sex',
        'lifestage',
        'reproductivecondition',
        'behavior',
        'establishmentmeans',
        'occurrencestatus',
        'preparations',
        'disposition',
        'othercatalognumbers',
        'previousidentifications',
        'associatedmedia',
        'associatedreferences',
        'associatedoccurrences',
        'associatedsequences',
        'associatedtaxa',
        'materialsampleid',
        'eventid',
        'samplingprotocol',
        'samplingeffort',
        'eventdate',
        'eventtime',
        'startdayofyear',
        'enddayofyear',
        'year',
        'month',
        'day',
        'verbatimeventdate',
        'habitat',
        'fieldnumber',
        'fieldnotes',
        'eventremarks',
        'locationid',
        'highergeographyid',
        'highergeography',
        'continent',
        'waterbody',
        'islandgroup',
        'island',
        'country',
        'countrycode',
        'stateprovince',
        'county',
        'municipality',
        'locality',
        'verbatimlocality',
        'verbatimelevation',
        'minimumelevationinmeters',
        'maximumelevationinmeters',
        'verbatimdepth',
        'minimumdepthinmeters',
        'maximumdepthinmeters',
        'minimumdistanceabovesurfaceinmeters',
        'maximumdistanceabovesurfaceinmeters',
        'locationaccordingto',
        'locationremarks',
        'verbatimcoordinatesystem',
        'verbatimlatitude',
        'verbatimlongitude',
        'verbatimcoordinates',
        'verbatimsrs',
        'decimallatitude',
        'decimallongitude',
        'geodeticdatum',
        'coordinateuncertaintyinmeters',
        'coordinateprecision',
        'pointradiusspatialfit',
        'footprintwkt',
        'footprintsrs',
        'footprintspatialfit',
        'georeferencedby',
        'georeferenceddate',
        'georeferenceprotocol',
        'georeferencesources',
        'georeferenceverificationstatus',
        'georeferenceremarks',
        'geologicalcontextid',
        'earliesteonorlowesteonothem',
        'latesteonorhighesteonothem',
        'earliesteraorlowesterathem',
        'latesteraorhighesterathem',
        'earliestperiodorlowestsystem',
        'latestperiodorhighestsystem',
        'earliestepochorlowestseries',
        'latestepochorhighestseries',
        'earliestageorloweststage',
        'latestageorhigheststage',
        'lowestbiostratigraphiczone',
        'highestbiostratigraphiczone',
        'lithostratigraphicterms',
        'group',
        'formation',
        'member',
        'bed',
        'identificationid',
        'identifiedby',
        'dateidentified',
        'identificationreferences',
        'identificationverificationstatus',
        'identificationremarks',
        'identificationqualifier',
        'typestatus',
        'taxonid',
        'scientificnameid',
        'acceptednameusageid',
        'parentnameusageid',
        'originalnameusageid',
        'nameaccordingtoid',
        'namepublishedinid',
        'taxonconceptid',
        'scientificname',
        'acceptednameusage',
        'parentnameusage',
        'originalnameusage',
        'nameaccordingto',
        'namepublishedin',
        'namepublishedinyear',
        'higherclassification',
        'kingdom',
        'phylum',
        'class',
        'order',
        'family',
        'genus',
        'subgenus',
        'specificepithet',
        'infraspecificepithet',
        'taxonomicstatus',
        'verbatimtaxonrank',
        'scientificnameauthorship',
        'vernacularname',
        'nomenclaturalcode',
        'taxonremarks',
        'nomenclaturalstatus',
        'taxonrank',
        /* '_kingdom', //additional (calculated) fields
        '_phylum',
        '_class',
        '_order',
        '_family',
        '_genus',
        '_species', */
        '_datasetid'
    );
    
    //override default because of choice between occlist / normal filtering
    protected function GetSQLlisting($orderby = '', $start = 0, $num = 0) {
        if ($this->use_occlist) {
            $sql = $this->sql_listing_occlist;
        } else {
            $sql = $this->sql_listing;
        }
		
		$sql .= $this->GetWhereClause();		
		if (!array_key_exists($orderby, $this->fieldmap_orderby)) { 
            $sql .= " ORDER BY " . reset($this->fieldmap_orderby); 
        } else {
            $sql .= " ORDER BY " . $this->fieldmap_orderby[$orderby];
        }
		        
		if ($start != 0 || $num != 0) $sql .= " LIMIT " . $num . " OFFSET " . $start;
				
        //to do: map and download must respect occlist filter        
        //to do: display occlist filter message on list occ
        //echo $sql;
        return $sql;
    }
    
    
    //HACK: THIS should be in a prepared statement

    public function AddWhere($fieldalias, $evaluate, $value) {
        //NOTE: can use $_GET directly since using pg_escape_string to prevent SQL injection
        if (in_array($fieldalias, $this->AdvancedSearchFields)) {
            $this->whereclause[] = "lower(" . $fieldalias . ") " . $evaluate . " lower('" . pg_escape_string($value) . "')";            
            return -1;
        }
        if (array_key_exists($fieldalias, $this->fieldmap_filterby)) {
            $field = $this->fieldmap_filterby[$fieldalias];
            if ($fieldalias == "filtercontent") { //special case
                //echo $value;
                $value = html_entity_decode($value, ENT_QUOTES);
                $value = str_replace("'","",$value); //HACK                
                $value = strtolower(str_replace(' ', ' & ', trim($value))); //words must be separated by &   
                $value = pg_escape_string($value);
                if ($value > '') {
                    $this->whereclause[] = str_replace("***", $value, $field);
                }
            } elseif ($fieldalias == "taxon") {
                $taxonranks = array('*root*', 'kingdom', 'phylum', 'class', 'order', 'family', 'genus', 'species');
                $taxon_epithet = ucfirst(strtolower(pg_escape_string($value[0])));
                $parent_epithet = ucfirst(strtolower(pg_escape_string($value[2])));
                $rank = strtolower(pg_escape_string($value[1]));
                $rankpos = array_search($rank, $taxonranks);
                if (!$rankpos)
                    return; //invalid OR *root*
                if ($rank == 'species')
                    $taxon_epithet = strtolower($taxon_epithet);
                if ($rank == 'kingdom') { //no higher rank to search
                    $this->whereclause[] = "_" . $rank . " = '" . $taxon_epithet . "'";
                } else {
                    $this->whereclause[] = "_" . $rank . " = '" . $taxon_epithet . "' AND _" . $taxonranks[$rankpos - 1] . " = '" . $parent_epithet . "'";
                }
            } elseif ($fieldalias == "occlist") {
                $this->whereclause[] = "sd.sessionid = '" . $value . "'";
                $this->use_occlist = true;
            } else {
                //table contextualised fields, and array fields, need special treatment
                if ((strpos($field, ".") > 0) || (strpos($field, "[") > 0)) {                    
                    $quotedfield = "\"" . str_replace(".", "\".\"", $field);
                    if (strpos($field, "[") > 0) {
                        $quotedfield = str_replace("[", "\"[", $quotedfield);
                    } else {
                        $quotedfield = $quotedfield . "\"";
                    }
                } else {
                    $quotedfield = "\"" . $field . "\"";
                }
                if (is_numeric($value) || is_bool($value)) {
                    if (is_bool($value)) {
                        $this->whereclause[] = "" . $quotedfield . " " . $evaluate . " " . ($value === true? "true" : "false") . "";
                    } else { //genuine number
                        $this->whereclause[] = "" . $quotedfield . " " . $evaluate . " " . $value . "";
                    }
                } else {
                    //make case-insensitive
                    $this->whereclause[] = "lower(" . $quotedfield . ") " . $evaluate . " lower('" . pg_escape_string($value) . "')";
                }               
            }
        }
    }
    
    //either returns the KML text for the occurrences, or writes the KML to a named file.
    //TODO: (maybe) add ability to create generalised KML if too many occurrence records are included
    public function GetOccurrencesKML($to_file = '') {
        $dom = new DOMDocument('1.0', 'UTF-8');

        // Creates the root KML element and appends it to the root document.
        $node = $dom->createElementNS('http://earth.google.com/kml/2.1', 'kml');
        $parNode = $dom->appendChild($node);

        // Creates a KML Document element and append it to the KML element.
        $dnode = $dom->createElement('Document');
        $docNode = $parNode->appendChild($dnode);

        $res = $this->GetRecords(); //may include occs. with no coordinates
        foreach ($res as $row) {        
            if (empty($row['_decimallongitude']) || empty($row['_decimallatitude'])) {
                continue; //don't include in set if has no coordinates
            }
            // Creates a Placemark and append it to the Document.
            $node = $dom->createElement('Placemark');
            $placeNode = $docNode->appendChild($node);

            // Creates an id attribute and assign it the value of id column.
            $placeNode->setAttribute('id', 'occurrence_' . $row['_id']);

            // Create name, and description elements
            $nameNode = $dom->createElement('name', htmlspecialchars(getMLtext('occurrence_record') . ": <a href='out.occurrence.php?id=" . $row['_id'] . "' alt='" . getMLtext('occurrence_record') . "' title='" . getMLtext('occurrence_record') . "' target='_new'>" . $row['_id'] . "</a>"));
            $placeNode->appendChild($nameNode);
            
            $descr = '<b>';
            if ($row['_genus'] >"" && $row['_species'] > "") {
                $descr .= getMLtext('species');
            } else {
                $descr .= getMLtext('scientific_name');                
            } 
            $descr .= ":</b>" . $row['display_taxon'] . "<br/>";
            
            $descr .= '<b>' . getMLtext('dataset') . ':</b> ' . $row['dataset_title'] . '<br>';
            $descr .= '<b>' . getMLtext('date') . ':</b> ' . $row['year'] . '-' . $row['month'] . '-' . $row['day'];
            
            $descrNode = $dom->createElement('description', htmlspecialchars($descr));
            $placeNode->appendChild($descrNode);

            // Creates a Point element.
            $pointNode = $dom->createElement('Point');
            $placeNode->appendChild($pointNode);

            // Creates a coordinates element and gives it the value of the computed lat/long columns from the results.
            $coorStr = $row['_decimallongitude'] . ',' . $row['_decimallatitude'];
            $coorNode = $dom->createElement('coordinates', $coorStr);
            $pointNode->appendChild($coorNode);
        }

        $kmlOutput = $dom->saveXML();
        //header('Content-type: application/vnd.google-earth.kml+xml');
        if ($to_file > '') {
            $written_ok = file_put_contents_atomic($to_file, $kmlOutput); //atomic
            //$written_ok = file_put_contents($to_file, $kmlOutput); //note: not atomic.  shouldn't be a problem since only the current user could read the file, but may need to implement 
            if ($written_ok === false) return 0; //error
            return $to_file;
        } else {
            return $kmlOutput;
        }
    }

    //get a sorted list of possible (non-null) values in a field, with total number of records having that value)
    //can specify region filter if desired
    public function GetDistinctFieldValues($field, $region='') {
        $ret = array();
        if ($region != '') {
            $sql = "SELECT * from vw_occ_sum1 WHERE (\"field\" = '" . $field . "' AND numrecs_" . $region . " > 0) ORDER BY \"value\" ASC";
        } else {
            $sql = "SELECT * from vw_occ_sum1 WHERE \"field\" = '" . $field . "' ORDER BY \"value\" ASC";
        }
        $res = pg_query_params($sql, array());
        $ret = array();
        while ($row = pg_fetch_array($res)) {
            $ret[] = $row;
        }
        return $ret;
    }
    
    //for advanced search
    public function GetSearchFields() {        
        return $this->AdvancedSearchFields;
    }
    
    public function Download() {
        global $siteconfig;
        
        //download is of original DwC data, not the summary view        
        $sql = str_replace("***", $this->GetSQLlisting(), $this->sql_listing_download);
        $res = pg_query_params($sql, array());
        if (!$res) return 0; //SQL error

        header('Content-Description: File Transfer');
        header('Content-Encoding: UTF-8');
        /* header('Content-Transfer-Encoding: binary'); */
        /* header('Content-type: text/csv; charset=UTF-8'); */
        header("Content-type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename=\"arbmis_occurrence.csv\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo "sep=\t\r\n"; //HACK: for Excel to recognise the tab delimiter
        //print column headers
        foreach($this->DwCFieldOrdering as $field) {
            echo getMLtext('occ_' . $field) . "\t";
        }
        echo "\r\n";
        //print data
        while ($row = pg_fetch_array($res)) {
            foreach($this->DwCFieldOrdering as $field) {
                if ($field != '_datasetid') {
                    echo $row[$field] . "\t";
                } else {
                    //URL to dataset
                    echo $siteconfig['path_ipt'] . "/resource.do?r=" . $row[$field] . "\t";
                }
            }
            echo "\r\n";
        }
        return -1;
    }
    
    public function IsDbOccList($session) {
        $res = pg_query_params("SELECT count(*) from session_searchdata WHERE sessionid = $1", array($session));
        if (!$res) return 0;
        while ($row = pg_fetch_array($res)) {
            if ($row[0]) return -1; //more than zero session data records
        }
        return 0;
    }
    
    //get 2 element array of layer name (which needs to be translated) and feature description
    public function GetOccListDescr($session) {
        $res = pg_query_params("SELECT *, COALESCE(\"description\",'') as descrnotnull from session_searchdata WHERE sessionid = $1 LIMIT 1", array($session));
        if (!$res) return 0;
        $fid = "";
        while ($row = pg_fetch_array($res)) {
            $fid = $row['descrnotnull'];                
        }
        $descr = array("layer" => "", "feature" => "");
        if ($fid == "") return $descr; //nothing in DB
        $res = pg_query_params("SELECT g.displayname, COALESCE(gf.description_text,'') AS descr_text, gf.attributes_concat FROM gislayer g JOIN gislayer_feature gf ON g.id = gf.gislayer_id WHERE gf.fid = $1", array($fid));
        if (!$res) return 0;        
        while ($row = pg_fetch_array($res)) {                     
            $descr['layer'] = $row['displayname'];
            if ($row['descr_text'] > '') {
                $descr['feature'] = $row['descr_text'];
            } else {
                $descr['feature'] = $row['attributes_concat'];
            }
        }        
        return $descr;
    }
}

?>
<?php
require_once("includes/inc.language.php");

class SingleOccurrence {

    var $id;
    //based on http://code.google.com/p/darwincore/downloads/detail?name=DwCTermsForTranslations_2013-10-22.csv.xls&can=2&q=
    var $arrayOrdering = array(        
        'link', //additional field: link to IPT resource
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
        '_kingdom', //additional (calculated) fields
        '_phylum',
        '_class',
        '_order',
        '_family',
        '_genus',
        '_species',
    );

    public function SingleOccurrence($id) {
        $this->id = $id;
    }

    //returns an array of field-label-value values for a single entry in the occurrence table, correctly ordered
    function GetAttributes($include_empty_fields = false) {
        $sql = "SELECT o.*, op.*, d.datasetid, d.link FROM ";
        $sql .= "occurrence o JOIN occurrence_processed op ON o._id = op._id ";
        $sql.= "JOIN dataset d ON op._datasetid = d.datasetid ";
        $sql .= "WHERE o._id = $1";
        
        $res = pg_query_params($sql, array($this->id));
        if (!$res) return 0; //error
        $row = pg_fetch_array($res, null, PGSQL_ASSOC);
        
        $ret = array();
        if (!$row) { //invalid id - no data
            $ret[] = array('no_record','',getMLtext('no_data'));
        } else {
            //var_dump($row);
            foreach ($this->arrayOrdering as $field) {
                if (isset($row[$field])) {
                    if ($include_empty_fields || $row[$field] > '')
                        $ret[] = array($field, getMLtext('occ_' . $field), $row[$field]);
                }
            }
        }
        return $ret;
    }

}

?>
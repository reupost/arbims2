<?php
require_once("../web/includes/config.php");
global $siteconfig;
require_once($siteconfig['path_basefolder'] . "/models/ipt.php");
require_once($siteconfig['path_basefolder'] . "/models/table_base.php");

define('TAXONOMIC_BACKBONE','taxonomicbackbone');

$OUTPUT = "";

$DEBUGGING = true;
$VERBOSE = true;

class GBIF {

    var $query_template = '
{
"creator": "reubenroberts",
"notificationAddresses": [
],
"sendNotification": false,
"format": "DWCA",
"predicate": {
"type": "and",
"predicates": [
    {
        "type": "within",
        "geometry": "***POLYGON***"
    },
    {
        "type": "equals",
        "key": "HAS_COORDINATE",
        "value": "true"
    },
    {
        "type": "equals",
        "key": "HAS_GEOSPATIAL_ISSUE",
        "value": "false"
    }
]
}
} ';

    private function AddGBIFDownloadToQueue($gbif_id, $gbif_metadata, $downloadtype) {
        $timestamp = date('Y-m-d H:i:s');
        $sql = "INSERT INTO log_gbif_requests (gbif_id, gbif_response, startdate, status, statusdate, downloadlink, downloadtype) VALUES ($1, $2, $3, $4, $5, $6, $7)";

        $metadata_json = json_decode($gbif_metadata, true);
        if (array_key_exists("status", $metadata_json) && array_key_exists("downloadLink", $metadata_json)) {
            $res = pg_query_params($sql, array($gbif_id, $gbif_metadata, $timestamp, $metadata_json['status'], $timestamp, $metadata_json['downloadLink'], $downloadtype));
            if (!$res) {
                $this->log("Error inserting download (" . $downloadtype . ") " . $gbif_id . " into queue");
            } else {
                $this->log("Download (" . $downloadtype . ") " . $gbif_id . " added to queue");
                return -1;
            }
        } else {
           $this->log("Error inserting download (" . $downloadtype . ") " . $gbif_id . " into queue - bad GBIF metadata");
        }
        return 0;
    }

    private function UpdateDownloadQueueStatus() {
        $sql = "SELECT * FROM log_gbif_requests WHERE status='RUNNING' OR status='PREPARING'";
        $res = pg_query_params($sql, array());
        $all_ok = true;
        if ($res === false) {
            $this->log("DB error retrieving active GBIF requests");
            $all_ok = false;
            return $all_ok;
        }
        $timestamp = date('Y-m-d H:i:s');
        $curl_handle=curl_init();
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'ARBIMS');

        while ($row = pg_fetch_array($res)) {
            curl_setopt($curl_handle, CURLOPT_URL,"http://api.gbif.org/v1/occurrence/download/" . $row['gbif_id']);
            $gbif_metadata = curl_exec($curl_handle);
            $metadata_json = json_decode($gbif_metadata, true);
            if (array_key_exists("status", $metadata_json) ) {
                $sql_update = "UPDATE log_gbif_requests SET status = $1, statusdate = $2 WHERE id = $3";
                $res_update = pg_query_params($sql_update, array($metadata_json['status'], $timestamp, $row['id']));
                if ($res_update === false) {
                    $this->log("Error updating status for download " . $row['gbif_id']);
                    $all_ok = false;
                }
            } else {
                $this->log("Failed to retrieve status for download " . $row['gbif_id']);
                $all_ok = false;
            }
        }
        curl_close($curl_handle);
        return $all_ok;
    }

    private function CountActiveDownloads() {
        $sql = "SELECT count(*) as active_downloads FROM log_gbif_requests WHERE status='RUNNING' OR status='PREPARING'";
        $res = pg_query_params($sql, array());
        $active_downloads = 0;
        while ($row = pg_fetch_array($res)) {
            $active_downloads = $row['active_downloads'];
        }
        return $active_downloads;
    }

    private function CountCompleteDownloads() {
        $sql = "SELECT count(*) as ready_downloads FROM log_gbif_requests WHERE status='SUCCEEDED' AND downloaddate IS NULL";
        $res = pg_query_params($sql, array());
        $ready_downloads = 0;
        while ($row = pg_fetch_array($res)) {
            $ready_downloads = $row['ready_downloads'];
        }
        return $ready_downloads;
    }

    private function CountSynchableDownloads() {
        $sql = "SELECT count(*) as ready_downloads FROM log_gbif_requests WHERE status='SUCCEEDED' AND downloaddate IS NOT NULL AND synchdate IS NULL";
        $res = pg_query_params($sql, array());
        $synchable_downloads = 0;
        while ($row = pg_fetch_array($res)) {
            $synchable_downloads = $row['ready_downloads'];
        }
        return $synchable_downloads;
    }

    var $loading = false;

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

    public function RetrieveCompleteDownloads($downloadtype) {
        global $siteconfig;

        if ($downloadtype == 'occurrences') {
            $filename = 'gbif_data';
        } elseif ($downloadtype == 'species list') {
            $filename = 'gbif_taxon';
        } else {
            $this->log("RetrieveCompleteDownloads - invalid downloadtype: " . $downloadtype);
            return 0;
        }
        $sql = "SELECT * FROM log_gbif_requests WHERE status='SUCCEEDED' AND downloaddate IS NULL AND downloadtype = $1 ORDER BY startdate DESC"; //get most recent first, if more than one
        $res = pg_query_params($sql, array($downloadtype));
        $download_id = 0;
        set_time_limit(0);
        $zip_file = $filename . ".zip";

        while (($row = pg_fetch_array($res)) && !$download_id) {

            $fp = fopen ($zip_file, 'w+');

            $ch = curl_init($row['downloadlink']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 400);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $curl_res = curl_exec($ch);
            $info = curl_getinfo($ch);
            if(curl_errno($ch)){
                throw new Exception(curl_error($ch));
            }
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if($statusCode != 200){
                throw new Exception("Download status code: " . $statusCode);
            }
            //TODO: catch timeout

            curl_close($ch);
            fclose($fp);

            $path = pathinfo(realpath($zip_file), PATHINFO_DIRNAME);

            $zip = new ZipArchive;
            $res = $zip->open($zip_file);
            if ($res === TRUE) {
                $zip->extractTo($path . '/' . $filename);
                $zip->close();

                if ($downloadtype == 'species list') {
                    $directory = $filename . '/';
                    @unlink($directory . $filename . ".csv"); //any old version, suppress warning
                    foreach (glob($directory . "*.csv") as $filenamenew) {
                        $file = realpath($filenamenew);
                        rename($file, $directory . $filename . '.csv');
                    }
                }

                $download_id = $row['id'];

                if ($download_id) {
                    //update this entry
                    $sql_update = "UPDATE log_gbif_requests SET downloaddate = $1 WHERE id = $2";
                    $timestamp = date("Y-m-d h:i:sa");
                    $res = pg_query_params($sql_update, array($timestamp, $row['id']));
                    //cancel any others that are ready in the queue since we've got the most up-to-date one
                    $sql_update = "UPDATE log_gbif_requests SET status = 'SUPERCEDED', statusdate = $1 WHERE status='SUCCEEDED' AND downloaddate IS NULL ANd downloadtype = $2";
                    $res = pg_query_params($sql_update, array($timestamp, $downloadtype));
                }
            } else {
                throw new Exception("Could not unzip file, it may be corrupt");
            }

        }
        return $download_id;
    }

    public function InitiateGBIFDownload() {
        global $siteconfig;

        $this->UpdateDownloadQueueStatus();
        $still_busy = false;
        $active_downloads = $this->CountActiveDownloads();
        if ($active_downloads > 0) $still_busy = true;

        //$still_busy = true;
        if (!$still_busy) {
            $this->log("Starting downloads from GBIF");
            $this->log_summary("Starting downloads from GBIF for spatial footprint: " . $siteconfig['gbif_query']);
            $this->loading = true;
            $query_json = str_replace("***POLYGON***", $siteconfig['gbif_query'], $this->query_template);
            $query_spplist_json = str_replace('"format": "DWCA",', '"format": "SPECIES_LIST",', $query_json);
            //$saved_query = file_put_contents('gbif/query.json', $query_json);
            //$saved_query_spplist = file_put_contents('gbif/query_spplist.json', $query_spplist_json);
            //if ($saved_query === false) {
            //    return "Error: could not save query json to gbif directory";
            //}

            $curl_handle=curl_init();
            curl_setopt($curl_handle, CURLOPT_URL,"http://api.gbif.org/v1/occurrence/download/request");
            curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_handle, CURLOPT_USERAGENT, 'ARBIMS');
            curl_setopt($curl_handle, CURLOPT_USERPWD, $siteconfig['gbif_username'] . ":" . $siteconfig['gbif_password']);
            curl_setopt($curl_handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "POST");

            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($query_json)));
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $query_json);
            $gbif_download_id = curl_exec($curl_handle);

            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($query_spplist_json)));
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $query_spplist_json);
            $gbif_download_spplist_id = curl_exec($curl_handle);

            curl_close($curl_handle);

            $curl_handle=curl_init();

            curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_handle, CURLOPT_USERAGENT, 'ARBIMS');
            curl_setopt($curl_handle, CURLOPT_URL,"http://api.gbif.org/v1/occurrence/download/" . $gbif_download_id);
            $gbif_metadata = curl_exec($curl_handle);
            curl_setopt($curl_handle, CURLOPT_URL,"http://api.gbif.org/v1/occurrence/download/" . $gbif_download_spplist_id);
            $gbif_spplist_metadata = curl_exec($curl_handle);
            //$this->log("GBIF species list metadata # " . $gbif_download_spplist_id . " : " . $gbif_spplist_metadata);
            curl_close($curl_handle);
            $db_res = $this->AddGBIFDownloadToQueue($gbif_download_id, $gbif_metadata, 'occurrences');
            $db_spplist_res = $this->AddGBIFDownloadToQueue($gbif_download_spplist_id, $gbif_spplist_metadata, 'species list');
            $this->log("Occurrence download added to queue: result " . $db_res);
            $this->log("Species list download added to queue: result " . $db_spplist_res);
            $this->log_summary("Add occurrence download to queue: " . ($db_res == -1? "ok" : "problem"));
            $this->log_summary("Add species list download to queue: " . ($db_spplist_res == -1? "ok" : "problem"));
        }
        $download_id = -1;
        $download_spplist_id = -1;
        try {
            //$this->UpdateDownloadQueueStatus();
            $download_id = $this->RetrieveCompleteDownloads('occurrences');
            $download_spplist_id = $this->RetrieveCompleteDownloads('species list');
        } catch (Exception $e) {
            $this->log('Error retrieving download: ', $e->getMessage(), "\n");
        }

        if ($download_id > 0) {
            $this->log('Download #' . $download_id . ' (' . 'occurrence' . ') successfully retrieved');
            $this->log_summary('Download #' . $download_id . ' (' . 'occurrence' . ') successfully retrieved');
        } elseif ($download_id == 0) {
            $this->log('No (' . 'occurrence' . ') to download right now');
            $this->log_summary('No occurrence downloads to retrieve right now');
        } else {
            $this->log('Active (' . 'occurrence' . ') downloads in the queue already');
            $this->log_summary('Active occurrence downloads still in the queue');
        }
        if ($download_spplist_id > 0) {
            $this->log('Download #' . $download_spplist_id . ' (' . 'species list' . ') successfully retrieved');
            $this->log_summary('Download #' . $download_spplist_id . ' (' . 'species list' . ') successfully retrieved');
        } elseif ($download_spplist_id == 0) {
            $this->log('No (' . 'species list' . ') to download right now');
            $this->log_summary('No species list downloads to retrieve right now');
        } else {
            $this->log('Active (' . 'species list' . ') downloads in the queue already');
            $this->log_summary('Active species list downloads still in the queue');
        }
        $this->log_summary("");
    }
}

$gbif = new GBIF();
$gbif->InitiateGBIFDownload();

?>
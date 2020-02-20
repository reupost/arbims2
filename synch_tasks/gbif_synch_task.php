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

    private function AddGBIFDownloadToQueue($gbif_id, $gbif_metadata) {
        $timestamp = date('Y-m-d H:i:s');
        $sql = "INSERT INTO log_gbif_requests (gbif_id, gbif_response, startdate, status, statusdate, downloadlink) VALUES ($1, $2, $3, $4, $5, $6)";
        //TODO: extract status and downloadlink
        $metadata_json = json_decode($gbif_metadata, true);
        if (array_key_exists("status", $metadata_json) && array_key_exists("downloadLink", $metadata_json)) {
            $res = pg_query_params($sql, array($gbif_id, $gbif_metadata, $timestamp, $metadata_json['status'], $timestamp, $metadata_json['downloadLink']));
            if (!$res) {
                $this->log("Error inserting download " . $gbif_id . " into queue");
            } else {
                $this->log("Download " . $gbif_id . " added to queue");
            }
        } else {
           $this->log("Error inserting download " . $gbif_id . " into queue - bad GBIF metadata");
        }
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

    public function InitiateGBIFDownload() {
        global $siteconfig;

        $this->UpdateDownloadQueueStatus();
        $still_busy = false;
        $active_downloads = $this->CountActiveDownloads();
        if ($active_downloads > 0) $still_busy = true;
        //$still_busy = true; //*** debugging

        if (!$still_busy) {
            $this->log("Starting downloads from GBIF");
            $this->loading = true;
            $query_json = str_replace("***POLYGON***", $siteconfig['gbif_query'], $this->query_template);
            /*$saved_query = file_put_contents('gbif/query.json', $query_json);
            if ($saved_query === false) {
                return "Error: could not save query json to gbif directory";
            } */

            $curl_handle=curl_init();
            curl_setopt($curl_handle, CURLOPT_URL,"http://api.gbif.org/v1/occurrence/download/request");
            curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_handle, CURLOPT_USERAGENT, 'ARBIMS');
            curl_setopt($curl_handle, CURLOPT_USERPWD, $siteconfig['gbif_username'] . ":" . $siteconfig['gbif_password']);
            curl_setopt($curl_handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($query_json)));
            curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $query_json);

            $gbif_download_id = curl_exec($curl_handle);
            //$info = curl_getinfo($curl_handle);
            curl_close($curl_handle);

            $curl_handle=curl_init();
            curl_setopt($curl_handle, CURLOPT_URL,"http://api.gbif.org/v1/occurrence/download/" . $gbif_download_id);
            curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_handle, CURLOPT_USERAGENT, 'ARBIMS');
            $gbif_metadata = curl_exec($curl_handle);
            curl_close($curl_handle);
            $db_res = $this->AddGBIFDownloadToQueue($gbif_download_id, $gbif_metadata);
            return $db_res;
        } else {
            $download_id = -1;
            try {
                $download_id = $this->RetrieveCompleteDownloads();
            } catch (Exception $e) {
                $this->log('Error retrieving download: ',  $e->getMessage(), "\n");
            }

            if ($download_id > 0) {
                $this->log('Download #' . $download_id . ' successfully retrieved');
            } elseif ($download_id == 0) {
                $this->log('Nothing to download right now');
            } else {
                return "Active downloads in the queue already";
            }
        }
    }

    public function RetrieveCompleteDownloads() {
        global $siteconfig;
        $this->UpdateDownloadQueueStatus();
        $sql = "SELECT * FROM log_gbif_requests WHERE status='SUCCEEDED' AND downloaddate IS NULL ORDER BY startdate DESC"; //get most recent first, if more than one
        $res = pg_query_params($sql, array());
        $download_id = 0;
        set_time_limit(0);
        $zip_file = "gbif_data.zip"; //"download.zip";
        while (($row = pg_fetch_array($res)) && !$download_id) {

            $fp = fopen (/* dirname(__FILE__) . '/' */ $zip_file, 'w+');
            echo $row['downloadlink'];
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
                $zip->extractTo($path . '/gbif_data');
                $zip->close();

                $download_id = $row['id'];

                if ($download_id) {
                    //update this entry
                    $sql_update = "UPDATE log_gbif_requests SET downloaddate = $1 WHERE id = $2";
                    $timestamp = date("Y-m-d h:i:sa");
                    $res = pg_query_params($sql_update, array($timestamp, $row['id']));
                    //cancel any others that are ready in the queue since we've got the most up-to-date one
                    $sql_update = "UPDATE log_gbif_requests SET status = 'SUPERCEDED', statusdate = $1 WHERE status='SUCCEEDED' AND downloaddate IS NULL";
                    $res = pg_query_params($sql_update, array($timestamp));
                }
            } else {
                throw new Exception("Could not unzip file, it may be corrupt");
            }

        }
        return $download_id;
    }

}

$gbif = new GBIF();
echo $gbif->InitiateGBIFDownload();
?>
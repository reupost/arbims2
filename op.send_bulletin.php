<?php
require_once("includes/config.php");
require_once("includes/inc.language.php");
require_once("lib/phpmailer/PHPMailerAutoload.php");

/* ---- */
//Run this script from the command-line using:
//php -f op.send_bulletin.php -- key=ArbMisbuLLetiN
/* ---- */

global $siteconfig;

$DOCS_TO_INCLUDE_AGE_MAX = 30; // in days
//check if it is really time to send the bulletin?

if (isset($argv)) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}
$key = (isset($_GET['key'])? $_GET['key'] : '');
if ($key != 'ArbMisbuLLetiN') exit;

mysql_connect($siteconfig['media_server'], $siteconfig['media_user'], $siteconfig['media_password']) OR DIE("<p><b>DATABASE ERROR: </b>Unable to connect to database server</p>");

//generates the HTML bulletin for the active MySQL library DB
//generates two copies, one in english, one in french;
//each is given as html and as plaintext
function GenerateLibraryBulletins($library) {
    global $siteconfig;    
    global $DOCS_TO_INCLUDE_AGE_MAX;
    
    $langs = array("en_GB","fr_FR");
    
    $bulletins = array();
    foreach ($langs as $lang) {
        
        $bulletin = "";        
        $bulletin .= "<h2>" . htmlspecialchars(getMLtext($library, null, null, $lang)) . "</h2>";
        $bulletin .= "<p>" . htmlspecialchars(getMLtext("bulletin_intro", null, null, $lang)) . "</p>";
        $bulletin .= "<table border='1px black' cellspacing='0px' cellpadding='5px'>";
        $bulletin .= "<tr><td>";
        $bulletin .= "<table border='0px' cellpadding='5px'>";
        $bulletin .= "<thead><th>" . htmlspecialchars(getMLtext("bulletin_doc_date", null, null, $lang)) . "</th>";
        $bulletin .= "<th>" . htmlspecialchars(getMLtext("bulletin_doc_title", null, null, $lang)) . "</th></thead>";

        $bulletin_text = "";
        $bulletin_text .= getMLtext($library, null, null, $lang) . "\r\n\n";
        $bulletin_text .= getMLtext("bulletin_intro", null, null, $lang) . "\r\n\n";
        $bulletin_text .= getMLtext("bulletin_doc_date", null, null, $lang) . " ";
        $bulletin_text .= getMLtext("bulletin_doc_title", null, null, $lang) . "\r\n";
    
        //get documents which are released (status = 2)
        $sql = "select d.`id`, d.`name`, DATE_FORMAT(FROM_UNIXTIME(d.`date`), '%e %b %Y') AS 'date_formatted', lsdoc.`status`, lsdoc.`version`,  datediff(now(), FROM_UNIXTIME(d.`date`)) daysold FROM tbldocuments d JOIN ";
        $sql .= "(select ds.documentid, ds.version, lateststatus.status FROM tbldocumentstatus ds JOIN ";
        $sql .= "(select `status`, statusid FROM tbldocumentstatuslog dsl JOIN ";
        $sql .= "(select max(statusLogID) as sid from tbldocumentstatuslog GROUP BY statusID) dslmax ";
        $sql .= "ON dsl.statusLogID = dslmax.sid) lateststatus ";
        $sql .= "ON ds.statusID = lateststatus.statusid) lsdoc ";
        $sql .= "ON d.id = lsdoc.documentid ";
        $sql .= "WHERE (lsdoc.`status` = 2 AND datediff(now(), FROM_UNIXTIME(d.`date`)) < " . $DOCS_TO_INCLUDE_AGE_MAX . ") ";
        $sql .= "ORDER BY d.`name` ASC;";
        $res = mysql_query($sql);
        if (!$res) return 0; //SQL error

        $doccount = 0;
        while ($doc = mysql_fetch_array($res)) {
            $doccount++;
            $bulletin .= "<tr>";
            $bulletin .= "<td>" . $doc['date_formatted'] . "</td>";
            $bulletin .= "<td>" . "<a href='" . $siteconfig['path_baseurl'] . "/" . str_replace("_","-",$library) . "/out/out.ViewDocument.php?documentid=" . $doc['id'] . "'>" . htmlspecialchars($doc['name']) . "</a></td>";
            $bulletin .= "</tr>";
            $bulletin_text .= $doc['date_formatted'] . " ";
            $bulletin_text .= $doc['name'] . " ";
            $bulletin_text .= $siteconfig['path_baseurl'] . "/" . str_replace("_","-",$library) . "/out/out.ViewDocument.php?documentid=" . $doc['id'] . "\r\n";                    
        }
        $bulletin .= "</table>";
        $bulletin .= "</td></tr></table>";
        $bulletin .= "<br/>";
        $bulletin .= "<br/>";
        $bulletin .= "<p style='font-size:smaller'>" . htmlspecialchars(getMLtext("bulletin_unsubscribe", null, null, $lang)) . " ";
        $bulletin .= "<a href='" . $siteconfig['path_baseurl'] . "/" . str_replace("_","-",$library) . "/out/out.MyAccount.php'>" . htmlspecialchars(getMLtext("my_account", null, null, $lang)) . "</a>";
        $bulletin .= "</p>";        
        
        $bulletin_text .= "\r\n\n";
        $bulletin_text .= getMLtext("bulletin_unsubscribe", null, null, $lang) . " ";
        $bulletin_text .= $siteconfig['path_baseurl'] . "/" . str_replace("_","-",$library) . "/out/out.MyAccount.php\r\n";
        $bulletins[] = array($bulletin, $bulletin_text);
    }
    if ($doccount == 0) return 0;    
    return $bulletins;
}

function SendLibraryBulletin($library, $bulletin_en, $bulletin_fr, $log = true) {     
    global $siteconfig;
    global $mail;
    
    $sql = "SELECT id, fullName, email, login, language FROM tblusers WHERE (disabled = 0 AND bulletin = 1 AND IFNULL(email,'') > '')";
    $res = mysql_query($sql);
    if (!$res) return 0; //SQL error
    
    $mailcount = 0;
    $failcount = 0;
    while ($usr = mysql_fetch_array($res)) {
        $mail->addAddress($usr['email'], $usr['fullName']);
        $mail->Subject = getMLtext('bulletin_title', null, null, $usr['language']);
        switch ($usr['language']) {
            case "en_GB"    : $mail->msgHTML(getMLtext("bulletin_dear", null, null, $usr['language']) . " " . $usr['fullName'] . "<br/><br/>" . $bulletin_en[0]); break;
            case "fr_FR"    : $mail->msgHTML(getMLtext("bulletin_dear", null, null, $usr['language']) . " " . $usr['fullName'] . "<br/><br/>" . $bulletin_fr[0]); break;
            default         : continue; /* no lang set */
        }  

        switch ($usr['language']) {
            case "en_GB"    : $mail->AltBody = $bulletin_en[1]; break;
            case "fr_FR"    : $mail->AltBody = $bulletin_fr[1]; break;
            default         : continue; /* no lang set */
        }   
        
        $mailcount++;        
        if ($usr['email'] == 'reupost@gmail.com') {
            //echo $message;
            $resultmail = $mail->send(); //true if successfully queued
            if (!$resultmail) $failcount++;
            if ($log) {
                $line = date('Y-m-d H:i:s') . "\t" . $library . "\t" . $usr['email'] . "\t" . $usr['login'] . "\t" . ($resultmail? "1" : "0") . "\r\n";
                file_put_contents($siteconfig['mail_log'], $line, FILE_APPEND);
            }
        }
        $mail->clearAddresses();
    }
    return array($library, $mailcount, $failcount);
}

$mail = new PHPMailer();
$mail->Host = "127.0.0.1"; // NOTE: on server it is 5.189.144.252
$mail->IsSMTP(); // telling the class to use SMTP
$mail->SMTPAuth = false;
$mail->Port = 25;
$mail->SMTPKeepAlive = true; // SMTP connection will not close after each email sent, reduces SMTP overhead

$mail->setFrom('no-reply@arbims.arcosnetwork.org', 'ARBIMS bulletin');
$mail->addReplyTo('bounce@arbims.arcosnetwork.org', 'Bounce');

foreach ($siteconfig['media_dbs'] as $theme => $dbname) {            
    @mysql_select_db($dbname) or die( "<p><b>DATABASE ERROR: </b>Unable to open database</p>");
    $bulletins = GenerateLibraryBulletins($dbname);
    if (is_array($bulletins)) { //are documents in bulletin        
        $bulletinresults = SendLibraryBulletin($dbname, $bulletins[0], $bulletins[1]);        
        echo $bulletinresults[0] . ": " . $bulletinresults[1] . " mails sent, " . $bulletinresults[2] . " failed (see log for details)</br>\r\n";                
    }
}
?>
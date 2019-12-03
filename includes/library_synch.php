<?php
require_once("includes/config.php");

global $siteconfig;

// Create connection
$conn = new mysqli($siteconfig['media_server'], $siteconfig['media_user'], $siteconfig['media_password']);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


//mysql_connect($siteconfig['media_server'], $siteconfig['media_user'], $siteconfig['media_password']) OR DIE("<p><b>DATABASE ERROR: </b>Unable to connect to database server</p>");
    
function Library_SynchUserToLibrary($username, $email, $password) {
    //from library inc.classDMS
    //there are defaults as follows: 
    //$isHidden=0, $isDisabled=1, $pwdexpiration=''  (set disabled = 0 when account activated)
    //$theme = 'bootstrap'
    //$role = 2 (guest)
    
    global $USER_SESSION;
    global $siteconfig;
    global $conn;

    $allok = true;
    foreach ($siteconfig['media_dbs'] as $theme => $dbname) {
        @mysqli_select_db($conn, $dbname) or die( "<p><b>DATABASE ERROR: </b>Unable to open database</p>");
        //first check if alreay there
        $sql = "SELECT * from tblUsers WHERE login = '" . mysql_real_escape_string($username) . "'";
        $res = mysqli_query($conn,$sql);
        if (mysqli_fetch_row($res)) continue; //already in library user list
    
        $theme = 'bootstrap';
        $role = 2;
        $disabled = 1;
        $comment = 'Added by ARBIMS';
        $language = $USER_SESSION['language'];
        $sql = "INSERT INTO tblusers (login, pwd, fullName, email, `language`, theme, comment, role, disabled, linkedaccount) ";
        $sql .= "VALUES (";
        $sql .= "'" . mysqli_real_escape_string($conn,$username) . "','" . md5($password) . "','" . mysql_real_escape_string($username) . "',";
        $sql .= "'" . mysqli_real_escape_string($conn,$email) . "','" . $language . "','" . $theme . "','" . $comment . "',";
        $sql .= $role . "," . $disabled . ", 1)";    
        $allok = mysqli_query($conn,$sql) && $allok;
    }
    if (!$allok) return 0; //SQL error
    return -1;
}

function Library_DeleteUser($username) {   
    global $siteconfig;
    global $conn;
    foreach ($siteconfig['media_dbs'] as $theme => $dbname) {
        @mysqli_select_db($conn,$dbname) or die( "<p><b>DATABASE ERROR: </b>Unable to open database</p>");
        $sql = "DELETE FROM tblusers WHERE login = '" . mysql_real_escape_string($username) . "'";
        mysqli_query($conn,$sql);
    }
    return -1;
}

function Library_ActivateUser($username) {
    global $siteconfig;
    global $conn;
    foreach ($siteconfig['media_dbs'] as $theme => $dbname) {
        @mysqli_select_db($conn,$dbname) or die( "<p><b>DATABASE ERROR: </b>Unable to open database</p>");
        $sql = "UPDATE tblusers SET disabled = 0 WHERE login = '" . mysql_real_escape_string($username) . "'";
        mysqli_query($conn,$sql);
    }
    return -1;
}

function Library_UpdateUserPassword($username, $pwd) {
    global $siteconfig;
    global $conn;
    foreach ($siteconfig['media_dbs'] as $theme => $dbname) {
        @mysqli_select_db($conn,$dbname) or die( "<p><b>DATABASE ERROR: </b>Unable to open database</p>");
        $sql = "UPDATE tblusers SET pwd = '" . md5($pwd) . "' WHERE login = '" . mysqli_real_escape_string($conn,$username) . "'";
        mysqli_query($conn,$sql);
    }
    return -1;
}

function Library_AttemptLogin($user, $pwd) {
    global $siteconfig;
	global $conn;
    foreach ($siteconfig['media_dbs'] as $theme => $dbname) {
        @mysqli_select_db($conn, $dbname) or die( "<p><b>DATABASE ERROR: </b>Unable to open database $dbname</p>");
        // Try to find user with given login.
        $res = mysqli_query($conn, "SELECT * FROM tblusers WHERE login = '" . mysqli_real_escape_string($conn, $user) . "' AND pwd = '" . md5($pwd) . "'");
        if (!$res) continue; //SQL error
        $userdb = mysqli_fetch_array($res);
        if (!$userdb) continue; //user not found or pwd incorrect
        if ($userdb['disabled']) return 0; //disabled account
    
        $queryStr = "DELETE FROM tblSessions WHERE " . time() . " - lastAccess > 86400";
        $res = mysqli_query($conn, $queryStr);
        if (!$res) continue; //sql error
    
        $id = "" . rand() . time() . rand() . "";
        $id = md5($id);
        $lastaccess = time();
        $queryStr = "INSERT INTO tblSessions (id, userID, lastAccess, theme, language, su) ".
                "VALUES ('".$id."', ".$userdb['id'].", ".$lastaccess.", '".$userdb['theme']."', '".$userdb['language']."', 0)";
        $res = mysqli_query($conn, $queryStr);
        if (!$res) continue; //sql error
            
        $lifetime = 0;
        setcookie("mydms_session", $id, $lifetime, '/library' . $theme . '/', null, null, !false);

        /* add_log_line(); */
    }
    return -1;
}

?>
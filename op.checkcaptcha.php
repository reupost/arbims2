<?php
require_once("includes/config.php");

if (isset($_POST['g_recaptcha_response']) && !empty($_POST['g_recaptcha_response'])) {
    $secret = $siteconfig['recaptcha_private_key'];
    //get verify response data
    $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $_POST['g_recaptcha_response'];
    $verifyResponse = file_get_contents($url);
    $responseData = json_decode($verifyResponse);
    //echo($responseData); exit;
    if ($responseData->success) {
        //captcha validated successfully.
        echo "ok";
    } else {
        echo "failed";
    }
} else {
    echo 'invalid captcha';
}

?>
<?php

require_once("includes/inc.language.php");
require_once("includes/library_synch.php");

class SingleUser {
    
    public function GetUser($id) {
        $ret = array();
        $res = pg_query_params("SELECT * FROM \"user\" WHERE id = $1", array($id));
        if ($res) {
            $row = pg_fetch_array($res, null, PGSQL_ASSOC);
			if ($row) {
                $ret = $row;
            }
        }
        return $ret;
    }
    
    public function GetUserFromPwd($pwd) {
        $ret = array();
        $res = pg_query_params("SELECT * FROM \"user\" WHERE password = $1", array($pwd));
        if ($res) {
            $row = pg_fetch_array($res, null, PGSQL_ASSOC);
			if ($row) {
                $ret = $row;
            }
        }
        return $ret;
    }
    
    private function GetPasswordHash($password) {
        // A higher "cost" is more secure but consumes more processing power
        //$cost = 10;

        // Create a random salt
        //$salt = strtr(base64_encode(phpseclib_mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');

        // Prefix information about the hash so PHP knows how to verify it later.
        // "$2a$" Means we're using the Blowfish algorithm. The following two digits are the cost parameter.
        //$salt = sprintf("$2a$%02d$", $cost) . $salt;

        // Value:
        // $2a$10$eImiTXuWVxfM37uY4JANjQ==

        // Hash the password with the salt
        //$hash = crypt($password, $salt);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        return $hash;
    }
    
    public function CreateUser($username, $email, $password, & $save_msg) {
        global $siteconfig;
        global $USER_SESSION;
        
        $res = pg_query_params("SELECT * FROM \"user\" WHERE upper(username) = $1", array(strtoupper($username)));
        if ($res) {
            $row = $row = pg_fetch_array($res);
			if ($row) { 
                $save_msg = getMLtext('register_username_already_used');
                return 0;
            }
        }
        $res = pg_query_params("SELECT * FROM \"user\" WHERE upper(email) = $1", array(strtoupper($email)));
        if ($res) {
            $row = $row = pg_fetch_array($res);
			if ($row) { 
                $save_msg = getMLtext('register_email_already_used');
                return 0;
            }
        }
        $hash = $this->GetPasswordHash($password);
        $language = $USER_SESSION['language'];
        
        pg_query_params("INSERT INTO \"user\" (username, email, language, password) VALUES ($1, $2, $3, $4)", array($username, $email, $language, $hash));
        
        $subject = getMLtext('register_email_subject');
        $message = GetMLtext('register_email_welcome') . " " . $username . "!\r\n\r\n" . getMLtext('register_email_instructions') . "\r\n\r\n";
        $message .= $siteconfig['path_baseurl'] . "/out.register_activate.php?k=" . $hash . "\r\n\r\n";
        $message .= getMLtext('thank_you') . ",\r\nARBMIS Admin";
        $from = "do-not-reply@arcosnetwork.org";
        $headers = "From:" . $from;
        
        $resultmail = mail($email,$subject,$message,$headers); //true if successfully queued
        if (!$resultmail) {
            $save_msg = getMLtext('email_error'); //maybe bad email address?
            pg_query_params("DELETE FROM \"user\" WHERE username = $1", array($username)); //roll back registration
            return 0;
        } else {
            $save_msg = getMLtext('register_success');
        }
        return -1;
    }
    
    
    //returns -1 if login success, 1 if login failed. 
    //Only 'activated' accounts can be logged into (where email address validated)
    public function VerifyLogin($username, $password, &$ret_user) {
		$result = pg_query_params("SELECT * from \"user\" WHERE upper(username)=$1 AND activated = $2", array(strtoupper($username), 1));
		if ($result) {
			$row = pg_fetch_array($result);
			if ($row) {
                // Hashing the password with its hash as the salt returns the same hash
                if ( crypt($password, $row['password']) === $row['password']) {
                    // Ok!
                    $ret_user['id'] = $row['id'];
                    $ret_user['username'] = $row['username'];
                    $ret_user['language'] = $row['language'];  
                    $ret_user['siterole'] = $row['siterole'];
                    $ret_user['email'] = $row['email'];
                    return -1;
                } else {
                    //wrong password
                }
			}			
		}
		return 1; //unusual return value used on login.tpl.php
	}
	
	public function RegisterLogin($userid) {		
		pg_query_params("UPDATE \"user\" SET numlogins = numlogins+1, lastlogindate = now() WHERE id = $1", array($userid));
		return -1;
	}
    
    public function ActivateAccount($hash) {
        $result = pg_query_params("SELECT * from \"user\" WHERE password=$1", array($hash));
		if ($result) {
			$row = pg_fetch_array($result);
			if ($row) {
                if ($row['activated'] == 'f') {
                    pg_query_params("UPDATE \"user\" SET activated = true WHERE password = $1", array($hash));
                    return -1;
                } else {
                    return -2; //already activated
                }
            }
        }
        return 0;
    }
	    
	public function PasswordReset($strlogin = '', $stremail = '') {
        global $siteconfig;
		$resultmail = 0; //=false, assume failure
		if ($strlogin != '') {			
            $result = pg_query_params("SELECT * FROM \"user\" WHERE username = $1", array($strlogin));
		} else {
            $result = pg_query_params("SELECT * FROM \"user\" WHERE email = $1", array($stremail));			
		}		
		if ($result) {
			$row = pg_fetch_array($result);
			if ($row) {
                //first remove any previous password reset tasks
                pg_query_params("DELETE FROM user_pwdreset WHERE user_id = $1", array($row['id']));
                //generate random key
                $reset_key = md5($row['username'] . mt_rand());
                //insert into DB
                pg_query_params("INSERT INTO user_pwdreset (user_id, reset_code) VALUES ($1, $2)", array($row['id'], $reset_key));
                //TODO ***: language
                $subject = "Reset your ARBIMS account password";
                $message = "Hello " . $row['username'] . "\r\n\r\nTo reset your password, click on the link below or paste it into your browser address bar.\r\n\r\n";
                $message .= $siteconfig['path_baseurl'] . "/out.passwordreset.php?k=" . $reset_key . "\r\n\r\n";
                $message .= "Thanks,\r\nARBMIS Admin";
                $from = "do-not-reply@arcosnetwork.org";
                $headers = "From:" . $from;
                $resultmail = mail($row['email'],$subject,$message,$headers); //true if successfully queued
			}			
		}
		return $resultmail;
	}
    
    //returns userid if key is in DB, otherwise 0
    public function GetPasswordResetUser($key) {
        $res = pg_query_params("SELECT * from user_pwdreset WHERE reset_code = $1", array($key));
        if ($res) {
            $row = pg_fetch_array($res);
            if ($row) {
                return $row['user_id'];
            }
        }
        return 0;
    }
    
    //return -1 if successful, 0 otherwise
    //note: NB to check that key has been requested, obviously
    public function PasswordResetAction($id, $key, $password) {
        $res = pg_query_params("SELECT * FROM user_pwdreset WHERE user_id = $1 AND reset_code = $2", array($id, $key));
        if (!$res) return 0;
        $row = pg_fetch_array($res);
        if (!$row) return 0;
        $this->SetPassword($id, $password);        
        pg_query_params("DELETE FROM user_pwdreset WHERE user_id = $1", array($id));
        return -1;
    }
    
    public function SetPassword($id, $password) {
        $hash = $this->GetPasswordHash($password);
        pg_query_params("UPDATE \"user\" SET password = $1 WHERE id = $2", array($hash, $id));
        return -1;
    }
    
    public function SetLanguage($id, $lang) {
        if ($lang == 'fr_FR' || $lang == 'en_GB') {
            pg_query_params("UPDATE \"user\" SET language = $1 WHERE id = $2", array($lang, $id));
            return -1;
        }
        return 0; //invalid lang setting
    }
    
    public function SaveUser($userdetails, & $save_msg) {
        $fields = array('id', 'language', 'email', 'siterole', 'activated');
           
        //some validation checks
        $res = pg_query_params("SELECT * FROM \"user\" WHERE upper(email) = $1", array(strtoupper($userdetails['email'])));
        if ($res) {
            $row = $row = pg_fetch_array($res);
			if ($row) { 
                $save_msg = getMLtext('register_email_already_used');
                return 0;
            }
        }
        //do we have validly constructed userdetails?
        $save_msg = getMLtext('invalid_form_data');
        foreach ($fields as $field) {            
            if (!isset($userdetails[$field])) return 0;
            if ($userdetails[$field] == '') return 0; 
        }
        if ($userdetails['id'] == 0) return 0;                
        if ($userdetails['activated'] != 't' && $userdetails['activated'] != 'f') return 0;
        if ($userdetails['language'] != 'fr_FR' && $userdetails['language'] != 'en_GB') return 0;
        
        if (!filter_var($userdetails['email'], FILTER_VALIDATE_EMAIL)) {
            $save_msg = getMLtext('invalid_email');
            return 0;
        }   
        
        //check: Id is of a valid user
        $save_msg = getMLtext('user_unknown');
        $res = pg_query_params("SELECT * FROM \"user\" WHERE id = $1", array($userdetails['id']));
        if (!$res) return 0;
        $row = pg_fetch_array($res);
        if (!$row) return 0;
        
        //check: making changes will not leave the website without an administrator
        if ($row['siterole'] == 'admin' && $userdetails['siterole'] != 'admin') {
            $save_msg = getMLtext('admin_missing');
            $res = pg_query_params("SELECT * FROM \"user\" WHERE siterole = $1 AND id != $2", array('admin', $userdetails['id']));
            if (!$res) return 0;
            $row = pg_fetch_array($res);
            if (!$row) return 0;
        }
        
        $save_msg = getMLtext('invalid_form_data');
        $res = pg_query_params("UPDATE \"user\" SET \"language\" = $1, \"siterole\" = $2, \"email\" = $3, \"activated\" = $4 WHERE id = $5", array($userdetails['language'], $userdetails['siterole'], $userdetails['email'], ($userdetails['activated'] == 't'? 1 : 0), $userdetails['id']));
        if (!$res) return 0;
        $save_msg = getMLtext('user_saved');
        return -1;
    }
    
    public function DeleteUser($id, & $save_msg) {
        //check: Id is of a valid user
        $save_msg = getMLtext('user_unknown');
        $res = pg_query_params("SELECT * FROM \"user\" WHERE id = $1", array($id));
        if (!$res) return 0;
        $row = pg_fetch_array($res);
        if (!$row) return 0;
        
        //check: making changes will not leave the website without an administrator        
        $save_msg = getMLtext('admin_missing');
        $res = pg_query_params("SELECT * FROM \"user\" WHERE siterole = $1 AND id != $2", array('admin', $id));
        if (!$res) return 0;
        $row = pg_fetch_array($res);
        if (!$row) return 0;
        
        $save_msg = getMLtext('invalid_form_data');
        $res = pg_query_params("DELETE FROM \"user\" WHERE id = $1", array($id));
        if (!$res) return 0;
        $save_msg = getMLtext('user_deleted');
        return -1;
    }
    
}

?>
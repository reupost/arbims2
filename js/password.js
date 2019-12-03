function ValidatePassword(allow_blank) {
    var p1 = document.getElementById('password').value;
    var p2 = document.getElementById('password2').value;
    if (p1 !== p2) return 0; //not the same
    if (allow_blank === undefined) {
        if (!p1.length) return 0; //empty
    } else if (!allow_blank) {
        if (!p1.length) return 0; //empty
    }
    return -1;
}

function SubmitMatchingPasswords(error_msg_pwd, error_msg_username, allow_blank) {
    if (ValidatePassword(allow_blank)) {
        if (!document.getElementById('username')) document.getElementById('theform').submit();
        
        if (IsUsernameValid(document.getElementById('username').value)) {
            document.getElementById('theform').submit();
            return;
        } else {
            window.alert(error_msg_username);
            return;
        }
    }
    window.alert(error_msg_pwd);
    return;
}

function IsUsernameValid(username) {
    var letters = /^[0-9a-zA-Z_]+$/;
    if (letters.test(username)) return -1;
    return 0;
}
    

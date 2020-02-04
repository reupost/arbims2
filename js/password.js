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

var captcha = "";
var verifyCallback = function(response) {
captcha = response;
};

var onloadCallback = function() {
    grecaptcha.render(document.getElementById('captcha'), {
            'sitekey' :  captcha_public_key,
            'callback' : verifyCallback,
            'theme' : 'light'
});
};


function SubmitMatchingPasswords(allow_blank) {
    $.ajax({
        type: "POST",
        url: "op.checkcaptcha.php",
        data: {
            g_recaptcha_response: captcha
        },
        success: function(response) {
            if (response != 'ok') {
                window.alert(response);
                window.alert(bad_captcha);
            } else {
                if (ValidatePassword(allow_blank)) {
                    if (!document.getElementById('username')) document.getElementById('theform').submit();

                    if (IsUsernameValid(document.getElementById('username').value)) {
                        document.getElementById('theform').submit();
                        return;
                    } else {
                        window.alert(username_not_valid);
                        return;
                    }
                }
                window.alert(passwords_do_not_match);
            }
        }
    });

}

function IsUsernameValid(username) {
    var letters = /^[0-9a-zA-Z_]+$/;
    if (letters.test(username)) return -1;
    return 0;
}
    

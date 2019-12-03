<legend><?php printMLtext('password_reset') ?></legend>
<div class="row-fluid">
    <div class="span12">
        <div class='help_prompt'><?php printMLtext('arbmis_passwordreset') ?></div>
        <?php
        if (isset($session_msg)) {
            if ($session_msg['state'] != 'success') {
                echo "<div class='message'>" . $session_msg['msg'] . "</div>";
            } elseif (!isset($user['username'])) {
                //no user with this key
                echo "<div class='message'>" . getMLtext('password_reset_key_wrong') . "</div>";
            } else {
                echo getMLtext('hello') . " " . $user['username'] . "<br/><br/>";
                echo getMLtext('set_a_new_password');
                ?>
                <form method="POST" action="op.passwordreset_save.php" enctype="multipart/form-data" id="theform" name="theform">
                    <input type="hidden" id="id" name="id" value="<?php echo $user['id'] ?>" />
                    <input type="hidden" id="key" name="key" value="<?php echo $params['key'] ?>" />
                    <table>
                        <tr>
                            <td>
        <?php printMLtext('password') ?> 
                            </td>
                            <td>
                                <input type="password" id="password" name="password" value="" maxlength="100" />
                            </td>
                        </tr>
                        <tr>
                            <td>
        <?php printMLtext('password_again') ?>&nbsp;
                            </td>
                            <td>
                                <input type="password" id="password2" name="password2" value="" maxlength="100" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                            </td>
                            <td>
                                <input type="button" value="<?php printMLtext('save') ?>" onclick='javascript:SubmitMatchingPasswords("<?php printMLtext('passwords_do_not_match') ?>")'/>
                            </td>
                        </tr>
                    </table>                    
                </form>
                <?php
            }
        }
        ?>
    </div>
</div>
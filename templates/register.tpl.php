<legend><?php printMLtext('register') ?></legend>
<div class="row-fluid">
    <div class="span12">
        <div class='help_prompt'><?php printMLtext('arbmis_register') ?></div>
        <?php if (isset($session_msg)) {
                    if ($session_msg['state'] != 'success')
                        echo "<div class='message'>" . $session_msg['msg'] . "</div>";
                }
        ?>
        <form method="POST" action="op.register_save.php" enctype="multipart/form-data" id="theform" name="theform">
            <table>
                <tr>
                    <td>
            <?php printMLtext('username') ?>&nbsp;
                        </td>
                <td>
                    <input type="text" id="username" name="username" maxlength="20" />
                </td>
                </tr>
                <tr>
                    <td>
            <?php printMLtext('email') ?>&nbsp;
                        </td>
                <td>
                    <input type="text" id="email" name="email" maxlength="40" />
                </td>
                </tr>
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
            <input type="button" value="<?php printMLtext('save') ?>" onclick='javascript:SubmitMatchingPasswords("<?php printMLtext('passwords_do_not_match') ?>","<?php printMLtext('username_not_valid') ?>")'/>
                    </td>
                </tr>
            </table>
        </form>
        <b></i><?php printMLtext('register_validate_email') ?></i></b>
    </div>
</div>
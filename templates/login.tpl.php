<legend><?php printMLtext('sign_in') ?></legend>
<div class="row-fluid">
    <div class="span12">
        <div class='help_prompt'><?php echo getMLtext('arbmis_login') . " <a href='out.register.php' alt=\"" . getMLtext('register') . "\" title=\"" . getMLtext('register') . "\" >" . getMLtext('register') . "</a>" ?></div>
        <?php if ($params['loginok'] == -1): ?>
            <b><?php printMLtext('sign_in_successful') ?></b><br/><br/>
            <?php printMLtext('view_profile') ?>
        <?php elseif ($params['loginok'] == 1): ?>
            <b><?php printMLtext('sign_in_unsuccessful') ?></b><br/><br/>
        <?php elseif ($params['loginok'] == -2): ?>
            <b><?php printMLtext('account_email_sent') ?></b>
        <?php elseif ($params['loginok'] == 2): ?>
            <b><?php printMLtext('account_not_found') ?></b><br/><br/>
        <?php endif ?>
        <?php if ($params['loginok'] >= 0): ?>	
            <form method="POST" action="out.login.php" enctype="multipart/form-data" id="login" name="login">
                <input type="hidden" id="reminder" name="reminder" value="0" />
                <input type="hidden" id="rememail" name="rememail" value="" />
                <table>
                    <tr>
                        <td>
                            <?php printMLtext('username') ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" id="username" name="username" value="" maxlength="50" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php printMLtext('password') ?>&nbsp;
                        </td>
                        <td>
                            <input type="password" id="password" name="password" value="" maxlength="100" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                        </td>
                        <td>
                            <input type="submit" value="Log in"/>
                        </td>
                    </tr>
                </table>
            </form>
            <br/>
            <b><?php printMLtext('password_reset') ?>:</b><br/>
            <?php printMLtext('enter_email_or_username') ?> <input type="text" id="rem_email" name="rem_email" maxlength="50" size="20" value=""/><br/>
            <?php printMLtext('and_then_click') ?> <a href="javascript:document.getElementById('reminder').value=1;document.getElementById('rememail').value=document.getElementById('rem_email').value;document.getElementById('login').submit();" alt="<?php printMLtext('password_reset') ?>" title="<?php printMLtext('password_reset') ?>"><?php printMLtext('reset_my_password') ?></a>.<br/>
            <?php printMLtext('password_reset_email') ?>
        <?php endif ?>
    </div>
</div>
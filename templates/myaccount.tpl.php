<legend><?php printMLText('my_account') ?></legend>
<div class="row-fluid">
    <div class="span12">
        <div class='help_prompt'><?php printMLtext('arbmis_myaccount') ?></div>
        <?php if (isset($session_msg)) {
                    if ($session_msg['state'] != 'success')
                        echo "<div class='message'>" . $session_msg['msg'] . "</div>";
                }
        ?>
        <form method="POST" action="op.myaccount_save.php" enctype="multipart/form-data" id="theform" name="theform">
            <div class="well">
                <table>
                    <tr>
                        <td>
                <b><?php printMLText('username') ?></b>&nbsp;
                    </td>
                        <td>
                            <?php echo $userdetails['username'] ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                <b><?php printMLText('email') ?></b>&nbsp; 
                    </td>
                        <td>
                            <?php echo $userdetails['email'] ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                <b><?php printMLText('role') ?></b>&nbsp;
                    </td>
                        <td>
                            <?php printMLText($userdetails['siterole']) ?>
                        </td>
                    </tr>
                </table>
            </div>
            <table>
                <tr>
                    <td colspan="2">
                        <?php printMLtext('password_change') ?> 
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
                        <?php printMLText('preferred_language') ?>&nbsp;
                    </td>
                    <td>
                        <select name="language" id="language"> 
                            <option value="en_GB" <?php if ($userdetails['language'] == 'en_GB') echo "selected" ?>><?php printMLtext('en_GB') ?></option>
                            <option value="fr_FR" <?php if ($userdetails['language'] == 'fr_FR') echo "selected" ?>><?php printMLtext('fr_FR') ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                    </td>
                    <td>            
                        <input type="button" value="<?php printMLtext('save') ?>" onclick='javascript:SubmitMatchingPasswords("<?php printMLtext('passwords_do_not_match') ?>","",true)' />
                    </td>
                </tr>
            </table>
        </form>
    </div>                    
</div>
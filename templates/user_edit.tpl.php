<legend><?php printMLText('user_edit') ?></legend>
<div class="row-fluid">
    <div class="span12">
        <div class='help_prompt'><?php printMLtext('arbmis_user_edit') ?></div>
        <?php if (isset($session_msg)) {
                    if ($session_msg['state'] != 'success')
                        echo "<div class='message'>" . $session_msg['msg'] . "</div>";
                }
        ?>
        <form method="POST" action="op.user_save.php" enctype="multipart/form-data" id="theform" name="theform">
            <input type="hidden" id="id" name="id" value="<?php echo $userdetails['id'] ?>"/>
            <input type="hidden" id="delete" name="delete" value="0"/>
            <div class='well'>
                <?php printMLText('user') ?>: <b><?php echo $userdetails['username'] ?></b> 
            </div>
            <table>
                <tr>
                    <td></td>                    
                    <td style='text-align:right'>
                        <input type="button" value="<?php printMLtext('user_delete') ?>" onclick='javascript:ConfirmDelete("<?php printMLtext('user_delete_confirm')?>")'/>     
                    </td>
                </tr>
                <tr>
                    <td colspan='3'>&nbsp;</td>
                </tr>
                <tr>
                    <td>
                        <?php printMLText('email') ?>
                    </td>
                    <td>
                        <input type="text" id="email" name="email" value="<?php echo $userdetails['email'] ?>"/>
                    </td>                    
                </tr>
                <tr>
                    <td>
                        <?php printMLText('role') ?>
                    </td>
                    <td>
                        <select name="siterole" id="siterole"> 
                        <option value="admin" <?php if ($userdetails['siterole'] == 'admin') echo "selected" ?>><?php printMLtext('admin') ?></option>
                        <option value="user" <?php if ($userdetails['siterole'] == 'user') echo "selected" ?>><?php printMLtext('user') ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
            <?php printMLText('preferred_language') ?>
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
                        <?php printMLtext('user_activated') ?>
                    </td>
                    <td>
                        <select name="activated" id="activated"> 
                            <option value="f" <?php if ($userdetails['activated'] == 'f') echo "selected" ?>><?php printMLtext('no') ?></option>
                            <option value="t" <?php if ($userdetails['activated'] == 't') echo "selected" ?>><?php printMLtext('yes') ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <input type="submit" value="<?php printMLtext('save') ?>" />
                    </td>
                </tr>
            </table>
        </form>
    </div>  
</div>
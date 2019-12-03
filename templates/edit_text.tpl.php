<legend><?php printMLtext('user_text_' . $params['key']) ?></legend>
<div class="row-fluid">
    <div class="span12">
        <div class='help_prompt'><?php printMLtext('arbmis_edit_text') ?></div>
        <?php if (isset($session_msg)) {
                    if ($session_msg['state'] != 'success')
                        echo "<div class='message'>" . $session_msg['msg'] . "</div>";
                }
        ?>
        <form method="POST" action="op.edit_text_save.php" enctype="multipart/form-data" id="theform" name="theform">
            <input type="hidden" id="key" name="key" value="<?php echo $params['key'] ?>"/>
            <input type="hidden" id="lang" name="lang" value="<?php echo $params['lang'] ?>"/>
            <textarea id="html" name="html">
                <?php echo $user_text ?>
            </textarea>
            <br/>
            <input type="submit" value="<?php printMLtext('save') ?>" />
        </form>
    </div>  
</div>
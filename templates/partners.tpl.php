<legend><?php printMLtext('partners') ?></legend>
<div class="row-fluid">
    <div class="span12">   
        <div id="intro">
            <?php if ($user['siterole'] == 'admin'): ?>
                <div style='float:right'>
                    <form action="out.edit_text.php">
                        <input type='hidden' name='key' id='key' value='partners' />
                        <input type='submit' value="<?php printMLtext('edit') ?>" />
                    </form>
                </div>
            <?php endif; ?>
            <div><?php echo $partner_text ?></div>
        </div>
    </div>
</div>
<br/>
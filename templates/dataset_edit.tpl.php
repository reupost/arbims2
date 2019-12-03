<legend><?php echo getMLtext('dataset_edit') . ": " . htmlspecialchars($dsdata['title']) ?></legend>
<div class="row-fluid">
    <div class="span12">        
        <div class='help_prompt'><?php printMLtext('arbmis_dataset_edit') ?></div>
        <div id="datasetfieldlist">
            <?php if (isset($session_msg)) {
                    if ($session_msg['state'] != 'success')
                        echo "<div class='message'>" . $session_msg['msg'] . "</div>";
                }
            ?>            
            <br/>
            <form action='op.dataset_save.php' method='POST'>
                <input type='hidden' id='datasetid' name='datasetid' value='<?php echo $dsdata['datasetid'] ?>' />
                <input type='hidden' id='region' name='region' value='<?php echo $region ?>' />
                <table id="dataset">                                        
                    <tbody>
                        <tr>
                            <td></td>
                            <td><input type='submit' style='float:right' value="<?php printMLtext('save') ?>" /></td>
                        </tr>
                        <tr>
                            <td style='vertical-align:top'><?php echo getMLtext('dataset_color') ?></td>
                            <td> 
                                <?php
                                if ($dsdata['_has_occurrence'] == 't') {
                                    echo "<input name='color' id='color' class='color {hash:true}' style='width:6em' maxlength=7 value='" . $dsdata['color'] . "'>";                                    
                                    echo "<br/>(" . getMLtext('click_to_edit') . ")";
                                } else {
                                    printMLtext('color_only_for_occurrence_datasets');
                                }
                                ?>
                            </td>
                        </tr>   
                        <tr><td colspan='2'>&nbsp;</td></tr>
                        <tr>
                            <td colspan='2'><b><?php printMLtext('regional_specificity') ?>:</b></td>
                        </tr>                            
                        <tr>
                            <td><?php printMLtext('region_albertine') ?>?</td>
                            <td>
                                <input type='radio' name='region_albertine' value='t' <?php if ($dsdata['region_albertine'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                                <input type='radio' name='region_albertine' value='f' <?php if ($dsdata['region_albertine'] == 'f') echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php printMLtext('region_mountains') ?>?</td>
                            <td>
                                <input type='radio' name='region_mountains' value='t' <?php if ($dsdata['region_mountains'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                                <input type='radio' name='region_mountains' value='f' <?php if ($dsdata['region_mountains'] == 'f') echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php printMLtext('region_lakes') ?>?</td>
                            <td>
                                <input type='radio' name='region_lakes' value='t' <?php if ($dsdata['region_lakes'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                                <input type='radio' name='region_lakes' value='f' <?php if ($dsdata['region_lakes'] == 'f') echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
        <br/>
    </div>                    
</div>
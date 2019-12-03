<legend><?php printMLtext('map_layer_edit') ?></legend>
<div class="row-fluid">
    <div class="span12">    
        <div class='help_prompt'><?php printMLtext('arbmis_gislayer_edit') ?></div>
        <div id="gislayerfieldlist">
            <?php if (isset($session_msg)) {
                    if ($session_msg['state'] != 'success')
                        echo "<div class='message'>" . $session_msg['msg'] . "</div>";
                }
            ?>            
            <br/>
            <form action='op.gislayer_save.php' method='POST'>
                <input type='hidden' id='id' name='id' value='<?php echo $layerdata['gislayer']['id'] ?>' />
                <table id="gislayer_edit">
                    <tr>
                        <td><h5><?php 
                        if ($layerdata['gislayer']['displayname'] != '') {
                            printMLtext($layerdata['gislayer']['displayname']);
                        } else { //no display name set
                            echo "[" . $layerdata['gislayer']['geoserver_name'] . "]";
                        }
                        ?></h5></td>                        
                        <td colspan='2' style='text-align:right'>
                            <input type='submit' value="<?php printMLtext('save') ?>" />
                        </td>
                    </tr>
                    <tr>
                        <td rowspan="11"><?php echo $layerdata['preview_img'] ?></td>
                        <td><?php echo getMLtext('layer_name') . "<br/>(" . getMLtext('dictionary_key') . ")" ?></td>
                        <td>
                            <select name='displayname' style='width:440px'>
                                <?php foreach ($layernamekeys as $key => $value) 
                                    echo "<option value=\"" . $key . "\" " . ($layerdata['gislayer']['displayname'] == $key? "selected='selected'" : "") . ">" . htmlentities($value) . " &nbsp; [" . htmlentities($key) . "]" . "</option>";
                                ?>                            
                            </select>
                            <div style='max-width:440px;font-style:italic'>
                                <?php printMLtext('map_layer_pick_help') ?>
                            </div>
                        </td>                        
                    </tr>                    
                    <tr>
                        <td><?php printMLtext('when_added') ?></td>
                        <td> <?php echo $layerdata['gislayer']['dateadded'] ?></td>
                    </tr>
                    <tr>
                        <td><?php printMLtext('layer_order') ?></td>
                        <td>
                            <select name='layer_order' style='width:110px'>
                                <?php for ($i = 1; $i < 100; $i++) { 
                                    echo "<option value='" . $i . "' " . ($i == $layerdata['gislayer']['layer_order']? "selected='selected'" : '') . ">" . $i . "</option>";
                                } ?>
                            </select>
                        </td>
                    </tr>                    
                    <tr>
                        <td><?php printMLtext('display_albertine') ?>?</td>
                        <td>
                            <input type='radio' name='allow_display_albertine' value='t' <?php if ($layerdata['gislayer']['allow_display_albertine'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                            <input type='radio' name='allow_display_albertine' value='f' <?php if ($layerdata['gislayer']['allow_display_albertine'] == 'f') echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                        </td>
                        
                    </tr>
                    <tr>
                        <td><?php printMLtext('display_mountains') ?>?</td>
                        <td>
                            <input type='radio' name='allow_display_mountains' value='t' <?php if ($layerdata['gislayer']['allow_display_mountains'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                            <input type='radio' name='allow_display_mountains' value='f' <?php if ($layerdata['gislayer']['allow_display_mountains'] == 'f') echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                        </td>
                        
                    </tr>
                    <tr>
                        <td><?php printMLtext('display_lakes') ?>?</td>
                        <td>
                            <input type='radio' name='allow_display_lakes' value='t' <?php if ($layerdata['gislayer']['allow_display_lakes'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                            <input type='radio' name='allow_display_lakes' value='f' <?php if ($layerdata['gislayer']['allow_display_lakes'] == 'f') echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                        </td>
                        
                    </tr>
                    <tr>
                        <td><?php printMLtext('can_be_queried') ?>?</td>
                        <td>
                            <input type='radio' name='allow_identify' value='t' <?php if ($layerdata['gislayer']['allow_identify'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                            <input type='radio' name='allow_identify' value='f' <?php if ($layerdata['gislayer']['allow_identify'] == 'f') echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                        </td>                        
                    </tr>
                    <tr>
                        <td><?php echo getMLtext('can_download') ?>?</td>
                        <td>
                            <input type='radio' name='allow_download' value='t' <?php if ($layerdata['gislayer']['allow_download'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                            <input type='radio' name='allow_download' value='f' <?php if ($layerdata['gislayer']['allow_download'] == 'f') echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                        </td>                          
                    </tr>
                    <tr>
                        <td><?php echo getMLtext('is_disabled') ?>?</td>
                        <td>
                            <input type='radio' name='disabled' value='t' <?php if ($layerdata['gislayer']['disabled'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                            <input type='radio' name='disabled' value='f' <?php if ($layerdata['gislayer']['disabled'] == 'f') echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                        </td>                          
                    </tr>
                    <tr>
                        <td><?php echo getMLtext('projection') ?></td>
                        <td><?php echo $layerdata['gislayer']['projection'] ?></td>
                    </tr>
                    <tr>
                        <td colspan='2' style='vertical-align:top'>
                            <?php echo "<b>" . getMLtext('legend') . ":</b><br/>" . $layerdata['legend_img'] ?>
                        </td>
                    </tr>            
                </table>
            </form>
        </div>
        <br/>
    </div>                    
</div>

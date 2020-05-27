<legend><?php printMLtext('map_layer_edit') ?></legend>
<div class="row-fluid">
    <div class="span12">    
        <div class='help_prompt'><?php printMLtext('arbmis_gislayer_edit') ?> <a href="/geoserver">Geoserver</a></div>
        <div id="gislayerfieldlist">
            <?php if (isset($session_msg)) {
                    if ($session_msg['state'] != 'success')
                        echo "<div class='message'>" . $session_msg['msg'] . "</div>";
                }
            ?>            
            <br/>
            <form action='op.gislayer_save.php' method='POST' enctype='multipart/form-data'>
                <input type='hidden' id='id' name='id' value='<?php echo $layerdata['gislayer']['id'] ?>' />
                <input type='hidden' id='geoserver_name' name='geoserver_name' value='<?php echo $layerdata['gislayer']['geoserver_name'] ?>' />
                <table id="gislayer">
                    <thead>
                    <th class='td_left'></th>
                    <th class='td_middle'></th>
                    <th class='td_right'></th>
                    </thead>
                    <tr>
                        <td><h5><?php
                        if ($layerdata['gislayer']['displayname'] != '') {
                            printMLtext($layerdata['gislayer']['displayname']);
                        } elseif ($layerdata['gislayer']['geoserver_name'] != '') { //no display name set
                            echo "[" . $layerdata['gislayer']['geoserver_name'] . "]";
                        }
                        if (!isset($layerdata['gislayer']['geoserver_name']) || $layerdata['gislayer']['geoserver_name'] == NULL || $layerdata['gislayer']['geoserver_name'] == '') {
                            echo "Click 'Choose file' to load a zipped shapefile or GeoTIFF:<br/>";
                        } else {
                            echo "<br/><br/>Click 'Choose file' to update the layer from a zipped shapefile or GeoTIFF:<br/>";
                        }
                        ?><input type="file" id="layer-load-shp" style="margin-top:5px" name="layer-load-shp" accept="application/zip"></h5></td>
                        <td colspan='2' style='text-align:right'>
                            <input type='submit' value="<?php printMLtext('save') ?>" style="width:8em !important" />
                        </td>
                    </tr>
                    <tr>
                        <td rowspan="5"><?php echo $layerdata['preview_img'] ?></td>
                        <td style="vertical-align:top"><?php echo getMLtext('layer_name') . "<br/>(" . getMLtext('dictionary_key') . ")" ?></td>
                        <td>
                            <select name='displayname' style="width:100% !important">
                                <?php foreach ($layernamekeys as $key => $value) 
                                    echo "<option value=\"" . $key . "\" " . ($layerdata['gislayer']['displayname'] == $key? "selected='selected'" : "") . ">" . htmlentities($value) . " &nbsp; [" . htmlentities($key) . "]" . "</option>";
                                ?>                            
                            </select>
                            <div style='font-style:italic'>
                                <?php printMLtext('map_layer_pick_help') ?>
                            </div>
                        </td>                        
                    </tr>
                    <tr>
                        <td style="vertical-align:top"><?php printMLtext('layer_meta_description') ?></td>
                        <td><textarea name="meta_description" id="meta_description"><?php echo $layerdata['gislayer']['meta_description'] ?></textarea></td>
                    </tr>
                    <tr>
                        <td colspan='2'><h5><?php printMLtext('layer_meta_source_title') ?>:</h5></td>
                    </tr>
                    <tr>
                        <td><?php printMLtext('layer_meta_source') ?></td>
                        <td><input type="text" name='meta_source' id="meta_source" value="<?php echo $layerdata['gislayer']['meta_source'] ?>"/></td>
                    </tr>
                    <tr>
                        <td><?php printMLtext('layer_meta_sourcelink') ?></td>
                        <td><input type="text" name='meta_sourcelink' id="meta_sourcelink" value="<?php echo $layerdata['gislayer']['meta_sourcelink'] ?>"/></td>
                    </tr>
                    <tr>
                        <td rowspan="18" style='vertical-align:top'>
                            <?php echo "<b>" . getMLtext('legend') . ":</b><br/>" . $layerdata['legend_img'] ?>
                        </td>
                        <td><?php printMLtext('layer_meta_sourcedate') ?></td>
                        <td><input type="text" name='meta_sourcedate' id="meta_sourcedate" value="<?php echo $layerdata['gislayer']['meta_sourcedate'] ?>"/></td>
                    </tr>
                    <tr>
                        <td><?php printMLtext('layer_meta_citation') ?></td>
                        <td><input type="text" name='meta_citation' id="meta_citation" value="<?php echo $layerdata['gislayer']['meta_citation'] ?>"/></td>
                    </tr>
                    <tr>
                        <td><?php printMLtext('layer_meta_licence') ?></td>
                        <td><input type="text" name='meta_licence' id="meta_licence" value="<?php echo $layerdata['gislayer']['meta_licence'] ?>"/></td>
                    </tr>

                    <tr>
                        <td><?php printMLtext('when_added') ?></td>
                        <td><?php echo $layerdata['gislayer']['dateadded'] ?></td>
                    </tr>
                    <tr>
                        <td><?php printMLtext('layer_type') ?></td>
                        <td><?php echo $layerdata['gislayer']['layer_type'] ?></td>
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
                        <td><?php printMLtext('layer_style') ?></td>
                        <td>
                            <select name='layer_style' style='width:330px'>
                                <?php for ($i = 1; $i < sizeof($styles); $i++) {
                                    echo "<option value='" . $styles[$i]['name'] . "' " . ($styles[$i]['name'] == $layerdata['style']? "selected='selected'" : '') . ">" . $styles[$i]['name'] . "</option>";
                                } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?php printMLtext('layer_meta_classification_1') ?></td>
                        <td><input type="text" name='meta_classification_1' id="meta_classification_1" value="<?php echo $layerdata['gislayer']['meta_classification_1'] ?>"/></td>
                    </tr>
                    <tr>
                        <td><?php printMLtext('layer_meta_classification_2') ?></td>
                        <td><input type="text" name='meta_classification_2' id="meta_classification_2" value="<?php echo $layerdata['gislayer']['meta_classification_2'] ?>"/></td>
                    </tr>
                    <?php if ($layerdata['gislayer']['layer_type'] == 'raster'): ?>
                    <tr>
                        <td><?php printMLtext('datafile_path') ?></td>
                        <td>
                            <input type="text" name='datafile_path' id="datafile_path" value="<?php echo $layerdata['gislayer']['datafile_path'] ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><?php printMLtext('datafile_path_warning') ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><?php printMLtext('display_albertine') ?>?</td>
                        <td>
                            <input type='radio' name='allow_display_albertine' value='t' <?php if ($layerdata['gislayer']['allow_display_albertine'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                            <input type='radio' name='allow_display_albertine' value='f' <?php if ($layerdata['gislayer']['allow_display_albertine'] == 'f' || $layerdata['gislayer']['id'] == 0) echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                        </td>
                        
                    </tr>
                    <tr>
                        <td><?php printMLtext('display_mountains') ?>?</td>
                        <td>
                            <input type='radio' name='allow_display_mountains' value='t' <?php if ($layerdata['gislayer']['allow_display_mountains'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                            <input type='radio' name='allow_display_mountains' value='f' <?php if ($layerdata['gislayer']['allow_display_mountains'] == 'f' || $layerdata['gislayer']['id'] == 0) echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                        </td>
                        
                    </tr>
                    <tr>
                        <td><?php printMLtext('display_lakes') ?>?</td>
                        <td>
                            <input type='radio' name='allow_display_lakes' value='t' <?php if ($layerdata['gislayer']['allow_display_lakes'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                            <input type='radio' name='allow_display_lakes' value='f' <?php if ($layerdata['gislayer']['allow_display_lakes'] == 'f' || $layerdata['gislayer']['id'] == 0) echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                        </td>
                        
                    </tr>
                    <tr>
                        <td><?php printMLtext('can_be_queried') ?>?</td>
                        <td>
                            <input type='radio' name='allow_identify' value='t' <?php if ($layerdata['gislayer']['allow_identify'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                            <input type='radio' name='allow_identify' value='f' <?php if ($layerdata['gislayer']['allow_identify'] == 'f' || $layerdata['gislayer']['id'] == 0) echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                        </td>                        
                    </tr>
                    <tr>
                        <td><?php echo getMLtext('can_download') ?>?</td>
                        <td>
                            <input type='radio' name='allow_download' value='t' <?php  if ($layerdata['gislayer']['layer_type'] != 'vector') echo ' disabled=true ' ?>  <?php if ($layerdata['gislayer']['allow_download'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                            <input type='radio' name='allow_download' value='f' <?php if ($layerdata['gislayer']['allow_download'] == 'f' || $layerdata['gislayer']['id'] == 0) echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                        </td>                          
                    </tr>
                    <tr>
                        <td><?php echo getMLtext('is_disabled') ?>?</td>
                        <td>
                            <input type='radio' name='disabled' value='t' <?php if ($layerdata['gislayer']['disabled'] == 't') echo "checked='checked'" ?>> <?php printMLtext('yes') ?>
                            <input type='radio' name='disabled' value='f' <?php if ($layerdata['gislayer']['disabled'] == 'f' || $layerdata['gislayer']['id'] == 0) echo "checked='checked'" ?>> <?php printMLtext('no') ?>
                        </td>                          
                    </tr>
                    <?php if ($layerdata['gislayer']['id'] != 0): ?>
                    <tr>
                        <td><?php echo getMLtext('projection') ?></td>
                        <td><?php echo $layerdata['gislayer']['projection'] ?></td>
                    </tr>
                    <?php endif ?>

                </table>
            </form>
        </div>
        <br/>
    </div>                    
</div>

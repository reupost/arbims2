<legend><?php printMLtext('map_layer') ?></legend>
<div class="row-fluid">
    <div class="span12">        
        <a href='out.listgislayers.php' alt="<?php printMLtext('map_layers') ?>" title="<?php printMLtext('map_layers') ?>"><img src='images/arrow-left-green.png' height='16px' width='16px' border='0px' style='padding-right:4px'><?php printMLtext('map_layers') ?></a>
        <div class='help_prompt'><?php printMLtext('arbmis_gislayer') ?></div>
        <div id="gislayerfieldlist">
            <table id="gislayer">
                <tr>
                    <td><h5><?php 
                        if ($layerdata['gislayer']['displayname'] != '') {
                            printMLtext($layerdata['gislayer']['displayname']);
                        } else { //no display name set
                            echo "[" . $layerdata['gislayer']['geoserver_name'] . "]";
                        }
                        ?></h5></td>
                    <td colspan="2" style='text-align:right'>
                        <?php if ($user['siterole'] == 'admin'): ?>
                            <input type='button' value="<?php printMLtext('edit') ?>" onclick="javascript:window.location='out.gislayer_edit.php?id=<?php echo $layerdata['gislayer']['id'] ?>'"/>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td rowspan="11"><?php echo $layerdata['preview_img'] ?></td>
                    <td><?php echo "<b>" . getMLtext('when_added') . "</b> " ?></td>
                    <td><?php echo $layerdata['gislayer']['dateadded'] ?></td>
                </tr>
                <tr>
                    <td><?php echo "<b>" . getMLtext('layer_order') . "</b>" ?></td>
                    <td><?php echo $layerdata['gislayer']['layer_order'] ?></td>
                </tr>                
                <tr>
                    <td><?php echo "<b>" . getMLtext('display_albertine') . "</b> " ?></td>
                    <td><?php echo ($layerdata['gislayer']['allow_display_albertine'] == 't'? getMLtext('yes') : getMLtext('no') ) ?></td>
                </tr>
                <tr>
                    <td><?php echo "<b>" . getMLtext('display_mountains') . "</b> " ?></td>
                    <td><?php echo ($layerdata['gislayer']['allow_display_mountains'] == 't'? getMLtext('yes') : getMLtext('no') ) ?></td>
                </tr>
                <tr>
                    <td><?php echo "<b>" . getMLtext('display_lakes') . "</b> " ?></td>
                    <td><?php echo ($layerdata['gislayer']['allow_display_lakes'] == 't'? getMLtext('yes') : getMLtext('no') ) ?></td>
                </tr>
                <tr>
                    <td><?php echo "<b>" . getMLtext('can_be_queried') . "</b> " ?></td>
                    <td><?php echo ($layerdata['gislayer']['allow_identify'] == 't'? getMLtext('yes') : getMLtext('no') ) ?></td>
                </tr>
                <tr>
                    <td><?php echo "<b>" . getMLtext('is_disabled') . "</b> " ?></td>
                    <td><?php echo ($layerdata['gislayer']['disabled'] == 't'? getMLtext('yes') : getMLtext('no') ) ?></td>
                </tr>
                <tr>
                    <td><?php echo "<b>" . getMLtext('download') . ":</b> " ?></td>
                    <td><?php
                        if ($layerdata['gislayer']['allow_download'] == 't') {
                            if ($user['id'] != 0) { //logged in
                                echo $layerdata['download_link'] . "<img src=\"images/download_icon.jpg\" border=\"0\" width=\"16\" height=\"16\" alt=\"" . getMLtext('download') . "\">" . getMLtext('download_shapefile') . "</a>";
                            } else {
                                printMLtext('logged_in_users_only');
                            }
                        } else {
                            echo getMLtext('not_available');
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><?php echo "<b>" . getMLtext('projection') . "</b> " ?></td>
                    <td><?php echo $layerdata['gislayer']['projection'] ?></td>
                </tr>
                <tr>
                    <td colspan="3">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="3" style='vertical-align:top'><?php echo "<b><u>" . getMLtext('legend') . "</u>:</b><br/>" . $layerdata['legend_img'] ?></td>
                </tr>            
            </table>
        </div>
        <br/>
    </div>                    
</div>

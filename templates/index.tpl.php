<legend><?php printMLtext('arcos_biodiversity_management_information_system') ?></legend>
<div class="row-fluid">
    <div class="span12">        
        <div id="arbmis_intro_box">
            <div id="arbmis_intro">
                <?php if ($user['siterole'] == 'admin'): ?>
                    <div style='float:right'>
                        <form action="out.edit_text.php">
                            <input type='hidden' name='key' id='key' value='intro' />
                            <input type='submit' value="<?php printMLtext('edit') ?>" />
                        </form>
                    </div>
                <?php endif; ?>
                <div><?php echo $user_text ?></div>
            </div>
            <div id="geo_themes">
                <a href='out.biodiversitydata.albertine.php'><h4><?php printMLtext('arbmis_geo_albertine_rift') ?></h4></a>
                <img src='images/ar-ecosystems.jpg' width='400px' height='200px' alt='pic' title='pic' />
                <a href='out.biodiversitydata.lakes.php'><h4><?php printMLtext('arbmis_geo_great_lakes') ?></h4></a>
                <img src='images/dsc_0978.jpg' width='400px' height='200px' alt='pic' title='pic' />                   
                <a href='out.biodiversitydata.mountains.php'><h4><?php printMLtext('arbmis_geo_african_mountains') ?></h4></a>
                <img src='images/kilimanjaro.jpg' width='400px' height='200px' alt='pic' title='pic' />                
            </div>
        </div>        
        <div id="arbmis_links">
        <table style='border-spacing: 10px; border-collapse: separate;'>
            <colgroup>
                <col width='33%'/>
                <col width='33%'/>
                <col width='33%'/>
            </colgroup> 
            <tr>                
                <td class='welltd'>
                    <table border='0'>
                        <tr>
                            <td colspan='2'><a href='out.biodiversitydata.php' alt="<?php printMLtext('biodiversity_data') ?>" title="<?php printMLtext('biodiversity_data') ?>"><h4><?php printMLtext('biodiversity_data') ?></h4></a></td>
                        </tr>
                        <tr>
                            <td><img src='images/red_colobus_monkey.jpg' height='100px' width='140px' border='0' alt='pic'/></td>
                            <td>
                                <p><?php printMLtext('arbmis_intro_biodiversitydata') ?></p>
                                <ul>
                                    <li><a href='out.biodiversitydata.albertine.php' alt="<?php printMLtext('biodiversity_data') ?>" title="<?php printMLtext('biodiversity_data') ?>"><?php printMLtext('region_albertine') ?></a></li>
                                    <li><a href='out.biodiversitydata.mountains.php' alt="<?php printMLtext('biodiversity_data') ?>" title="<?php printMLtext('biodiversity_data') ?>"><?php printMLtext('region_mountains') ?></a></li>
                                    <li><a href='out.biodiversitydata.lakes.php' alt="<?php printMLtext('biodiversity_data') ?>" title="<?php printMLtext('biodiversity_data') ?>"><?php printMLtext('region_lakes') ?></a></li>
                                </ul>                                
                            </td>
                        </tr>
                    </table>
                </td>
                <td class='welltd'>
                    <table border='0'>
                        <tr>
                            <td colspan='2'><h4><?php printMLtext('maps') ?></h4></td>
                        </tr>
                        <tr>
                            <td><img src='images/shrike.jpg' height='100px' width='140px' border='0' alt='pic'/></td>
                            <td>
                                <p><?php printMLtext('arbmis_intro_map') ?></p>
                                <ul>
                                    <li><a href='out.map.albertine.php' alt="<?php printMLtext('map') ?>" title="<?php printMLtext('map') ?>"><?php printMLtext('region_albertine') ?></a></li>
                                    <li><a href='out.map.mountains.php' alt="<?php printMLtext('map') ?>" title="<?php printMLtext('map') ?>"><?php printMLtext('region_mountains') ?></a></li>
                                    <li><a href='out.map.lakes.php' alt="<?php printMLtext('map') ?>" title="<?php printMLtext('map') ?>"><?php printMLtext('region_lakes') ?></a></li>
                                </ul>
                            </td>
                        </tr>
                    </table>
                </td>                
                <td class='welltd'>
                    <table border='0'>
                        <tr>
                            <td colspan='2'><a href='out.libraries.php' alt="<?php printMLtext('libraries') ?>" title="<?php printMLtext('libraries') ?>"><h4><?php printMLtext('libraries') ?></h4></a></td>
                        </tr>
                        <tr>
                            <td><img src='images/turlini_0006.jpg' height='100px' width='140px' border='0' alt='pic'/></td>
                            <td>
                                <p><?php printMLtext('arbmis_intro_library') ?></p>
                                <a href='out.libraries.php' alt="<?php printMLtext('libraries') ?>" title="<?php printMLtext('libraries') ?>"><?php printMLtext('libraries') ?></a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        </div>
    </div>                    
</div>

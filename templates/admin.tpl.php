<legend><?php printMLtext('admin_tools') ?></legend>
<div class="row-fluid">
    <div class="span12">
        <?php printMLtext('arbmis_admin') ?>
        <table style='border-spacing: 10px; border-collapse: separate;'>
            <colgroup>
                <col width='50%'/>
                <col width='50%'/>
            </colgroup>            
            <tr>                
                <td class='well'>
                    
                        <table border='0'>
                            <tr>
                                <td colspan='2'><h4><?php printMLtext('users') ?></h4></td>
                            </tr>
                            <tr>
                                <td style='padding-right:5px'><img src='images/users-128x128.png' height='128px' width='128px' border='0' alt='pic'/></td>
                                <td>
                                    <p><?php printMLtext('arbmis_admin_users') ?></p>
                                    <a href='out.listusers.php' alt="<?php printMLtext('user_list') ?>" title="<?php printMLtext('user_list') ?>"><?php printMLtext('user_list') ?></a>
                                </td>
                            </tr>
                        </table>
                    
                </td>
                <td class='well'>
                    
                        <table border='0'>
                            <tr>
                                <td colspan='2'><h4><?php printMLtext('ipt') ?></h4></td>
                            </tr>
                            <tr>
                                <td style='padding-right:5px'><img src='images/ipt.jpg' height='128px' width='159px' border='0' alt='pic'/></td>
                                <td>
                                    <p><?php printMLtext('arbmis_admin_ipt') ?></p>
                                    <a href='ipt/' alt="<?php printMLtext('ipt_link') ?>" title="<?php printMLtext('ipt_link') ?>"><?php printMLtext('ipt_link') ?></a><br/><br/>
                                    <a href='out.ipt_synch.php' alt="<?php printMLtext('synchronise_with_ipt') ?>" title="<?php printMLtext('synchronise_with_ipt') ?>"><?php printMLtext('synchronise_with_ipt') ?></a>
                                </td>
                            </tr>
                        </table>
                    
                </td>
            </tr>
            <tr>                
                <td class='well'>
                    
                        <table border='0'>
                            <tr>
                                <td colspan='2'><h4><?php printMLtext('mailing_list') ?></h4></td>
                            </tr>
                            <tr>
                                <td style='padding-right:5px'><img src='images/mail_grey_128.png' height='128px' width='128px' border='0' alt='pic'/></td>
                                <td>
                                    <p><?php printMLtext('arbmis_admin_mail') ?></p>
                                    <a href='op.send_bulletin.php?key=ArbMisbuLLetiN' alt="<?php printMLtext('mailing_list_send') ?>" title="<?php printMLtext('mailing_list_send') ?>"><?php printMLtext('mailing_list_send') ?></a>
                                </td>
                            </tr>
                        </table>
                    
                </td>                
                <td class='well'>
                    
                        <table border='0'>
                            <tr>
                                <td colspan='2'><h4><?php printMLtext('geoserver') ?></h4></td>
                            </tr>
                            <tr>
                                <td style='padding-right:5px'><img src='images/geoserver-128.png' height='128px' width='128px' border='0' alt='pic'/></td>
                                <td>
                                    <p><?php printMLtext('arbmis_admin_geoserver') ?></p>
                                    <a href='geoserver/' alt="<?php printMLtext('geoserver') ?>" title="<?php printMLtext('geoserver') ?>"><?php printMLtext('geoserver') ?></a><br/><br/>
                                    <a href='out.listgislayers.php' alt="<?php printMLtext('synchronise_with_geoserver') ?>" title="<?php printMLtext('synchronise_with_geoserver') ?>"><?php printMLtext('synchronise_with_geoserver') ?></a>
                                </td>
                            </tr>
                        </table>
                   
                </td>
            </tr>

        </table>
    </div>                    
</div>
<legend><?php echo getMLtext('species_explorer') . ': ' . getMLtext('region_' . $region) ?></legend>
<div class="row-fluid">
    <div class="span12">
        <div class='help_prompt'><?php printMLtext('arbmis_species') ?></div>
        <?php echo $pageopts ?>
        <a name="specieslistanchor"></a>
        <?php echo $pageform ?>
        <div id="spp_acc">
            <ul id="species_accordion" class="accordion">
                <?php echo $txt ?>
            </ul>
        </div>
        <br/>
    </div>                    
</div>
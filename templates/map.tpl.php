<legend><?php echo getMLtext('map') . ": " . getMLtext('region_' . $region) ?></legend>
<?php if (isset($params['dataset_title']) || isset($params['taxon_title']) || $params['bounding_box'] > '') echo "<h4>" . getMLtext('search_results') . ":</h4>" ?>
<?php if (isset($params['dataset_title'])) echo "<h5>" . $params['dataset_title'] . "</h5>" ?>
<?php if (isset($params['taxon_title'])) echo "<h5>" . $params['taxon_title'] . "</h5>" ?>
<?php if ($params['bounding_box'] > '') echo "<h5>" . $params['bounding_box'] . "</h5>" ?>
<?php if ($adv_criteria > '') echo "<h5>" . getMLtext('advanced_search_criteria') . ":</h5><div class='well'>" . $adv_criteria . "</div>"  ?>
<div class="row-fluid">
    <div class="span12">
        <div class='help_prompt'><?php printMLtext('arbmis_map') ?></div>
        <div>       
            <div id="map-controlwrapper" class="map_controls map_controls_normalsize">                
                <b><?php printMLtext('map_controls') ?>:</b><br/>  
                <input type="radio" name="control" value="navigation" id="noneToggle" onclick="toggleControl(this);" CHECKED>
                <label for="noneToggle" style="display: inline"><?php printMLtext('navigate') ?></label>
                &nbsp;&nbsp;
                <input type="radio" name="control" value="box_occ" id="boxToggle" onclick="toggleControl(this);">
                <label for="boxToggle" style="display: inline"><?php printMLtext('select_occurrence_records') ?></label>                
                &nbsp;
                <div id="map-size-notice"><?php printMLtext('map_size_notice') ?></div>
            </div>
            <div id="map" class="map_pane map_normalsize">        
                <input id="btn-print" style="z-index:9999;" title="print map" class="btn-print-map" type="image" src="images/printmap.png" onclick="javascript:printMap()"/>
                <input id="btn-full-screen" style="z-index:9999;" title="<?php printMLtext('map_size_fullscreen')?>" class="btn-full-screen" type="image" src="images/maximise.png" onclick="javascript:maximiseMap()"/>
                <input id="btn-partial-screen" style="z-index:9999;display:none" title="<?php printMLtext('map_size_normal')?>" class="btn-partial-screen" type="image" src="images/minimise.png" onclick="javascript:normalMap()"/>
            </div>
            <div id="map-selectwrapper">
                <div id='layerlink'>
                    <?php printMLtext('map_layer_details') ?>:&nbsp;
                    <a href='out.listgislayers.php' alt="<?php printMLtext('map_layers') ?>" title="<?php printMLtext('map_layers') ?>"><?php printMLtext('map_layers') ?></a>
                </div>
                <div id="dataset_key">
                    <h5><?php printMLtext('dataset_occurrence_key') ?>:</h5>
                    <?php echo $occ_legend; ?>
                </div>
                <div id="map-boxSelect">
                    <h5><?php printMLtext('selected_occurrence_records') ?>:</h5>
                </div>
            </div>
        </div>
        <div id="map-wrapper">
            <div id="map-location"><?php printMLtext('location') ?></div>
            <div id="map-scale"></div>
        </div>
        <div id="map-info">
            <p><?php printMLtext('click_map_for_info') ?></p>
            <div id="map-responseText"></div>
            
        </div>
        <div id="debugging"></div>
    </div>                    
</div>
<div id="layerdialog" title="<?php printMLtext("map_info_feature") ?>" style='display:none'>
  <p><?php printMLtext("map_info_feature_explanation") ?></p>
  <div id="layertabs">
    <ul id="layertabs_ul">
        <li><a href="#tabs-1">Tab 1</a></li>        
    </ul>
    <div id="tabs-1">
        <p>Feature info</p>
    </div>    
  </div>
</div>
<script defer="defer" type="text/javascript">    
    $("#layertabs").tabs(); //set up the tabs
    var map;
    <?php echo $params['js_layer_objs'] ?>
        
    var layer_occurrence, layer_occurrence_overview, layer_identify, layer_occ_select;
<?php if (isset($params['occ_kml'])): ?>
        var layer_occ_kml;
<?php endif; ?>
    var infoControls;
    var selbox_lat_min, selbox_lat_max, selbox_lon_min, selbox_lon_max;
    var selbox_active = false;
    var selectOccurrence;

    // OpenLayers.ProxyHost = "/cgi-bin/proxy.cgi?url=";
    // pink tile avoidance
    OpenLayers.IMAGE_RELOAD_ATTEMPTS = 3;
    OpenLayers.Util.onImageLoadErrorColor = "transparent";
    // make OL compute scale according to WMS spec
    OpenLayers.DOTS_PER_INCH = 25.4 / 0.28;

    function init() {
        format = 'image/png';
        var geogProj = new OpenLayers.Projection('EPSG:4326'); //display coords etc as this
        var mapProj = new OpenLayers.Projection('EPSG:3857'); //map projection

        var options = {
            controls: [],
            projection: 'EPSG:3857',
            units: 'm', //degrees
            maxResolution: 156543.0339,
            maxExtent: new OpenLayers.Bounds(-20037508.34, -20037508.34,
                    20037508.34, 20037508.34),
<?php
    switch ($region) {
        case "albertine":   echo "center: new OpenLayers.LonLat(30.4,-2.3)"; break;
        case "mountains":   echo "center: new OpenLayers.LonLat(23,5.4)"; break;
        case "lakes"    :   echo "center: new OpenLayers.LonLat(29.6,-3.4)"; break;
        default         :   echo "center: new OpenLayers.LonLat(0,0)"; break;
    }
?>            
                    // Google.v3 uses web mercator as projection, so we have to transform our coordinates
                    .transform('EPSG:4326', 'EPSG:3857')
                    //zoom: 11
        };

        map = new OpenLayers.Map('map', options);

        <?php echo $params['js_layer_init'] ?>
                
        
        var occStyleMap = new OpenLayers.StyleMap({
            "temporary": new OpenLayers.Style({
                fillColor: "red"
            }),
            "select": new OpenLayers.Style({
                fillColor: "red"
            })
        });
        layer_occurrence = new OpenLayers.Layer.WMS(
                "<?php printMLtext('occurrence_records') ?>", "<?php echo $params['geoserver_wms'] ?>",
                {
                    LAYERS: 
                    <?php switch($region) {
                        case 'albertine':
                        case 'mountains':
                        case 'lakes'    : echo "'cite:occurrence_" . $region . "'"; break;
                        default         : echo "'cite:occurrence'"; break;
                    }
                    ?>
                        ,
                    styles: '',
                    format: format,
                    transparent: true, /* means parts outside of shape are left clear */
                    tiled: true,
                    tilesOrigin: map.maxExtent.left + ',' + map.maxExtent.bottom
                },
                {                    
                    buffer: 0,
                    displayOutsideMaxExtent: true,
                    isBaseLayer: false,
                    yx: {'EPSG:4326': true},
                    styleMap: occStyleMap
                }
        );
        layer_occurrence_overview = new OpenLayers.Layer.WMS(
                "<?php printMLtext('occurrence_overview') ?>", "<?php echo $params['geoserver_wms'] ?>",
                {
                    LAYERS: <?php echo "'cite:occurrence_overview_" . $region . "'"; ?>,
                    STYLES: '',                    
                    displayInLayerSwitcher: false,
                    format: format,
                    transparent: true, /* means parts outside of shape are left clear */
                    tiled: true,
                    tilesOrigin: map.maxExtent.left + ',' + map.maxExtent.bottom
                },
                {
                    opacity: 0.4,
                    buffer: 0,
                    displayOutsideMaxExtent: true,
                    isBaseLayer: false,
                    yx: {'EPSG:4326': true}
                }
        );
<?php if (isset($params['occ_kml'])): ?>
        var kmlStyleMap = new OpenLayers.StyleMap({
            "default": new OpenLayers.Style({
                externalGraphic: "<?php echo $params['occ_kml_icon'] ?>",
                graphicWidth: 32,
                graphicHeight: 32,
                graphicYOffset: -16
            }),
            "temporary": new OpenLayers.Style({
                externalGraphic: "<?php echo $params['occ_kml_icon_sel'] ?>"
            }),
            "select": new OpenLayers.Style({
                externalGraphic: "<?php echo $params['occ_kml_icon_sel'] ?>"
            })
        });
        layer_occ_kml = new OpenLayers.Layer.Vector(
                "<?php printMLtext('occurrences_mapped') ?>", 
                {
                    projection: geogProj,
                    strategies: [new OpenLayers.Strategy.Fixed()],
                    protocol: new OpenLayers.Protocol.HTTP({
                        url: "<?php echo $params['occ_kml'] ?>",
                        format: new OpenLayers.Format.KML({
                            extractStyles: true,
                            extractAttributes: true,
                            maxDepth: 2
                        })
                    }),
                    styleMap: kmlStyleMap
                });
<?php endif; ?>
        var gterrain = new OpenLayers.Layer.Google(
                "<?php printMLtext('map_physical') ?>",
                {type: google.maps.MapTypeId.TERRAIN, numZoomLevels: 20, sphericalMercator: true}
        );
        var gstreet = new OpenLayers.Layer.Google(
                "<?php printMLtext('map_street') ?>", 
                {sphericalMercator: true}
        );
        var ghybrid = new OpenLayers.Layer.Google(
                "<?php printMLtext('map_hybrid') ?>",
                {type: google.maps.MapTypeId.HYBRID, sphericalMercator: true}
        );
        var gsatellite = new OpenLayers.Layer.Google(
                "<?php printMLtext('map_satellite') ?>",
                {type: google.maps.MapTypeId.SATELLITE, sphericalMercator: true}
        );

        //general feature identify highlight shape
        layer_identify = new OpenLayers.Layer.Vector(
                "Highlighted Features",
                {
                    displayInLayerSwitcher: false,
                    isBaseLayer: false,
                    preFeatureInsert: function(feature) {
                        feature.geometry.transform(geogProj, mapProj);
                    }
                },
                {
                    yx: {'EPSG:3857': true},
                    projection: mapProj
                }
        );
        //selected occurrence records
        layer_occ_select = new OpenLayers.Layer.Vector(
                "Selected Occurrences",
                {
                    displayInLayerSwitcher: false, 
                    isBaseLayer: false,
                    styleMap: new OpenLayers.Style(OpenLayers.Feature.Vector.style["select"])
                },
                {
                    yx: {'EPSG:3857': true},
                    projection: mapProj
                }
        );

        // start with occurrence overview but not detailed occurrence data on map because of initial zoom level
        map.addLayers([gterrain, gstreet, ghybrid, gsatellite, 
        <?php echo $params['js_layer_list'] . (strlen($params['js_layer_list'])>0? ", " : "") ?>                        
                        layer_occurrence_overview,
<?php if (isset($params['occ_kml'])): ?>
                        layer_occ_kml,
<?php endif; ?>
                        layer_identify, layer_occ_select]);

        // build up all controls
        map.addControl(new OpenLayers.Control.LayerSwitcher());
        map.addControl(new OpenLayers.Control.PanZoomBar({position: new OpenLayers.Pixel(2, 15)}));
        map.addControl(new OpenLayers.Control.Scale(OpenLayers.Util.getElement('scale')));
        map.addControl(new OpenLayers.Control.MousePosition({
            element: OpenLayers.Util.getElement('map-location'),
            displayProjection: geogProj
        }));
        selectOccurrence = new OpenLayers.Control.SelectFeature(layer_occ_select);
        map.addControl(selectOccurrence);

        // support GetFeatureInfo    
        <?php if (strlen($params['js_layer_list'])>0): ?>
        infoControls = {
            click: new OpenLayers.Control.WMSGetFeatureInfo({
                url: "<?php echo $params['geoserver_wms'] ?>",
                title: 'Identify features by clicking',
                layers: [<?php echo $params['js_layer_list_identify'] ?>],
                queryVisible: true,
                infoFormat: "application/vnd.ogc.gml"
            })
        };
        infoControls.click.events.register("getfeatureinfo", this, showInfo);        
        map.addControl(infoControls.click);
        infoControls.click.activate();
        <?php endif; ?>
        map.events.register("zoomend", map, zoomChanged);
        togglecontrols = {
            navigation: new OpenLayers.Control.Navigation(),
            box_occ: new OpenLayers.Control.GetFeature({
                protocol: new OpenLayers.Protocol.WFS({
                    version: "1.1.0",
                    url: "<?php echo $params['geoserver_wfs'] ?>", 
                    featureType: "occurrence",
                    srsName: "EPSG:3857" 
                }),
                box: true,
                projection: mapProj
            }), 
        };
        togglecontrols.box_occ.events.register("featureselected", this, function(e) {
            addFeatureToList(e.feature);
        });
        togglecontrols.box_occ.events.register("featureunselected", this, function(e) {
            selbox_active = false;
            layer_occ_select.removeFeatures([e.feature]);
            document.getElementById("map-boxSelect").innerHTML = "<h5><?php printMLtext('selected_occurrence_records') ?>:</h5>";
        });
        togglecontrols.box_occ.events.register("beforefeaturesselected", this, function(e) {
            //about to select features
            if (e.features.length) {
                layer_occ_select.removeAllFeatures();
                document.getElementById("map-boxSelect").innerHTML = "";
            }
        });	
        togglecontrols.box_occ.events.register("featuresselected", this, function(e) {
            //finished selecting features
            if (e.features.length) {
                finaliseFeatureList(false);
            }
        });
        for (var key in togglecontrols) {
            map.addControl(togglecontrols[key]);
        }
        
<?php if (isset($params['occ_kml'])): ?>
        hoverselect_occ_kml = new OpenLayers.Control.SelectFeature(layer_occ_kml, {hover: true, highlightOnly: true});
        select_occ_kml = new OpenLayers.Control.SelectFeature(layer_occ_kml);

        layer_occ_kml.events.on({
            "featureselected": onFeatureSelect,
            "featureunselected": onFeatureUnselect
        });
        map.addControl(hoverselect_occ_kml);
        map.addControl(select_occ_kml);
        hoverselect_occ_kml.activate();
        select_occ_kml.activate();
        layer_occ_kml.events.register('loadend', layer_occ_kml, function() {
            map.zoomToExtent(this.getDataExtent()); //zoom to mapped records
        });
<?php endif; ?>

<?php switch($region) {
    case "albertine":   echo "map.zoomToScale(5000000);"; break;
    case "mountains":   echo "map.zoomToScale(40000000);"; break;
    case "lakes"    :   echo "map.zoomToScale(10000000);"; break;
    default         :   echo "map.zoomToScale(50000000);"; break;
}
?>        

    }

    function GetLayerNameFromFID(fid) {
        arr = fid.split(".");        
        layer_id = 'cite:' + arr[0]; //.toLowerCase();
        for	(index = 0; index < user_layers.length; ++index) {
            if (user_layers[index].geoserver_name == layer_id) {
                return user_layers[index].ml_name;
            }
        }
        return '(unknown layer)';        
    }
    
    function showInfo(evt) {                               
        if (evt.features && evt.features.length) {
            //reset the popup div
            $("#layertabs").tabs("destroy");
            $('#layertabs').empty();   
            var listTabs = $("<ul id='layertabs_ul'>");
            $('#layertabs').append(listTabs); 
        
            layer_identify.destroyFeatures();

            for (i = 0; i < evt.features.length; i++) { //all identified shapes
                //geoserver 2.5.0 returns incorrect geometry so don't use this version                
                var curFeat = evt.features[i];                                
                
                //alert(JSON.stringify(curFeat, null, 4));                
                var newTab = $('<li><a href="#tabs-' + i.toString() + '">' + GetLayerNameFromFID(curFeat.fid) + '</a></li>');
                $('#layertabs_ul').append(newTab);
                
                var arrAttribs = [];
                
                var only_show_descr = false;
                for (var prop in curFeat.attributes) {
                    if (prop.toLowerCase() == 'descriptio') only_show_descr = true;
                }
                if (!only_show_descr) {
                    for (var prop in curFeat.attributes) {                        
                        arrAttribs[arrAttribs.length] = prop.toString();
                    }
                } else {                    
                    arrAttribs[arrAttribs.length] = "<?php printMLtext("map_info_description") ?>";
                }                
                                                
                var arrAttribsVals = [];
                for (var att in curFeat.attributes) {
                    if (att.toLowerCase() == 'descriptio' || !only_show_descr) {                        
                        arrAttribsVals[arrAttribsVals.length] = curFeat.attributes[att] + "";
                    }
                }
                
                
                var newTab = $('<div id="tabs-' + i.toString() + '"></div>');
                $('#layertabs').append(newTab);                
                var newTabContent = '<table>';
                for (ix = 0; ix < arrAttribs.length; ix++) {
                    newTabContent += '<tr><td style="padding-right:10px">' + arrAttribs[ix] + '</td>';                    
                    newTabContent += '<td>';
                    newTabContent += arrAttribsVals[ix];
                    newTabContent += '</td>';                    
                }
                newTabContent += '</table>';
                var newTabContentJQ = $(newTabContent);
                $('#tabs-' + i.toString()).append(newTabContentJQ);
                                
                var tabLinks = "<br/><p><b><?php printMLtext('view_occurrences') ?>:</b><div>";
                var arrLayers = map.getLayersByName("<?php printMLtext('occurrence_records') ?>");
                if (arrLayers.length) { //only if occ data layer is there                    
                    tabLinks += "<a href=\"javascript:listOccsFor('" + curFeat.fid + "')\">" + "<?php printMLtext('view_occurrences') ?>" + "</a>";
                } else {
                    tabLinks += "<?php printMLtext('view_occurrences_zoom_in') ?>";
                }   
                tabLinks += "</div></p>";
                tabLinks += "<p><b><?php printMLtext('map_linked_docs') ?>:</b> ";
                tabLinks += "<div id='docs_" + curFeat.fid.toString() + "'></div>";
                var newTabContentLinks = $(tabLinks);
                $('#tabs-' + i.toString()).append(newTabContentLinks);
            }            
            layer_identify.addFeatures(evt.features);
            layer_identify.redraw();
            
            //display tabs
            $("#layertabs" ).tabs();
            $("#layerdialog").dialog({ width: 600 });
        } else {
            //clicked somewhere else: do nothing            
        }
               
        //now handle linked documents: the DOM has the elements we need to write into now
        if (evt.features && evt.features.length) {
            for (i = 0; i < evt.features.length; i++) { //all identified shapes                            
                var curFeat = evt.features[i]; 
                var fidName = curFeat.fid.toString();                
                writeLinkedDocs_makecall(fidName);
            }
        }                
    }
    
    function writeLinkedDocs_makecall(fidName) {
        $.ajax({
            type: 'GET',
            url: 'data.library_map.php',
            /* data: { region: "<?php  echo $region ?>", fid : curFeat.fid.toString()}, */
            data: { fid : fidName},                
            dataType: 'json',                    
            cache: false,
            success: function(result) { writeLinkedDocs_finalise(result, fidName); }                                           
        });
    }
                
    function writeLinkedDocs_finalise(result, fidName) {
        if (typeof result[0] !== 'undefined') {            
            var writeid = 'docs_' + fidName;            
            var writeEl = document.getElementById(writeid);                                                        
            $.each(result, function(index, element) {                                                
                writeEl.innerHTML += "<a href='" + element.url + "' target='_new'>" + element.name + "</a> (" + element.type + ")<br/>";
            });   
            $("#layertabs" ).tabs("refresh");
        }
    }
    
    function addFeatureToList(feature) {
        var arrLayers = map.getLayersByName("<?php printMLtext('occurrence_records') ?>");
        if (arrLayers.length) { //only if occ data layer is there
            if (arrLayers[0].visibility) { //only if occ data layer is showing
                layer_occ_select.addFeatures([feature]);
                var attrs = feature.attributes;
                if (!selbox_active) { //initialise bounding box coordinates
                    selbox_lat_min = parseFloat(attrs['_decimallatitude']);
                    selbox_lat_max = parseFloat(attrs['_decimallatitude']);
                    selbox_lon_min = parseFloat(attrs['_decimallongitude']);
                    selbox_lon_max = parseFloat(attrs['_decimallongitude']);
                    selbox_active = true;
                } else { //update min-max coords for bounding box
                    if (parseFloat(attrs['_decimallatitude']) < selbox_lat_min) selbox_lat_min = parseFloat(attrs['_decimallatitude']);
                    if (parseFloat(attrs['_decimallatitude']) > selbox_lat_max) selbox_lat_max = parseFloat(attrs['_decimallatitude']);
                    if (parseFloat(attrs['_decimallongitude']) < selbox_lon_min) selbox_lon_min = parseFloat(attrs['_decimallongitude']);
                    if (parseFloat(attrs['_decimallongitude']) > selbox_lon_max) selbox_lon_max = parseFloat(attrs['_decimallongitude']);
                }
                textarea = document.getElementById("map-boxSelect");
                if (typeof textarea !== 'undefined') 
                        textarea.innerHTML += 
                        "<a href='out.occurrence.php?id=" + attrs['_id'] + "' " +
                        "alt=\"<?php echo htmlspecialchars(getMLtext('occurrence_record')) ?>\" " +
                        "title=\"<?php echo htmlspecialchars(getMLtext('occurrence_record')) ?>\" " + 
                        "target='_new'>" + attrs['_id'] + "</a>: " + attrs['display_taxon'] + 
                        " (" + attrs['dataset_title'] + ")" + "<br>";
            }
        }
    }
    
    function finaliseFeatureList(basedOnFID) {        
        textarea = document.getElementById("map-boxSelect");
        if (textarea) {
            var showlink =             
                "<h5><?php printMLtext('selected_occurrence_records') ?>:</h5>" +
                "<b><a href='out.listoccurrence.<?php echo $region ?>.php?";
            if (!basedOnFID) {
                showlink +=
                    "x1=" + selbox_lon_min.toString() + "&x2=" + selbox_lon_max.toString() +
                    "&y1=" + selbox_lat_min.toString() + "&y2=" + selbox_lat_max.toString();
            } else {
                showlink += "occlist=1";
            }
            showlink += "' " + "alt=\"<?php printMLtext('view_occurrences') ?>\" title=\"<?php printMLtext('view_occurrences') ?>\" target='_new'><?php printMLtext('view_all') ?></a></b><br/><br/>";
            textarea.innerHTML = showlink + textarea.innerHTML; 
        }
    }
    
    function showDialog() {
        $(function() {
            $( "#layertabs" ).tabs();
            $( "#layerdialog" ).dialog({ width: 600 });
        });
    }
    
    function showFilteredOccs(request) {        
        try {
            var gmlParser = new OpenLayers.Format.GML.v3();
            gmlParser.extractAttributes = true;            
            var features = gmlParser.read(request.responseText);

            if (features) {                
                var projFrom = new OpenLayers.Projection("EPSG:4326");
                var projTo = new OpenLayers.Projection("EPSG:3857");
                for (i = 0; i < features.length; i++) {
                    yy = features[i].geometry.x;
                    features[i].geometry.x = features[i].geometry.y;
                    features[i].geometry.y = yy;
                    features[i].geometry.transform(projFrom,projTo);
                }                
                layer_occ_select.addFeatures(features);                
                selectOccurrence.activate();
                textarea = document.getElementById("map-boxSelect");
                if (textarea) textarea.innerHTML = "";
                for (i = 0; i < features.length; i++) {                    
                    selectOccurrence.select(layer_occ_select.features[i]);
                    addFeatureToList(features[i])
                }
                finaliseFeatureList(true); //true = basedOnFID
            }
        } catch(e) {
            alert("Error: " + e);
        }                
    }
    
    function listOccsFor(fid) {
        layer_occ_select.removeAllFeatures();
        arr = fid.split(".");
        layer_id = 'cite:' + arr[0];        
        var oXml = OpenLayers.Request.GET({
            url: "data.fid_occs.php?fid=" + fid.toString(),
            callback: showFilteredOccs
        });
    }

<?php if (isset($params['occ_kml'])): ?>
        function onPopupClose(evt) {
            select_occ_kml.unselectAll();
        }
        function onFeatureSelect(event) {
            var feature = event.feature;
            // Since KML is user-generated, do naive protection against
            // Javascript.
            var content = "<h2>" + feature.attributes.name + "</h2>" + feature.attributes.description;            
            popup = new OpenLayers.Popup.FramedCloud("occurrence_info",
                    feature.geometry.getBounds().getCenterLonLat(),
                    new OpenLayers.Size(100, 100),
                    content,
                    null, true, onPopupClose);
            popup.maxSize = new OpenLayers.Size(300, 200);
            feature.popup = popup;
            map.addPopup(popup);
        }
        function onFeatureUnselect(event) {
            var feature = event.feature;
            if (feature.popup) {
                map.removePopup(feature.popup);
                feature.popup.destroy();
                delete feature.popup;
            }
        }
<?php endif; ?>

    function zoomChanged() {
        //window.alert('zooming');
        zoom = map.getZoom();
        scale = map.getScale();
        //can't print map if too far zoomed out
        if (scale > 35000000) {
            document.getElementById("btn-print").disabled = true;
        } else {
            document.getElementById("btn-print").disabled = false;
        }
        if (zoom < 11) {
            arrLayers = map.getLayersByName("<?php printMLtext('occurrence_overview') ?>");
            if (!arrLayers.length) {
                map.addLayer(layer_occurrence_overview);
                layer_occurrence_overview.redraw();
            }
            arrLayers = map.getLayersByName("<?php printMLtext('occurrence_records') ?>");
            if (arrLayers.length) {
                map.removeLayer(layer_occurrence);
            }
            //cannot select when zoomed out
            document.getElementById("noneToggle").checked = true;
            toggleControl(document.getElementById("noneToggle"));
            document.getElementById("boxToggle").disabled = true; 
            
        } else {
            arrLayers = map.getLayersByName("<?php printMLtext('occurrence_records') ?>");
            if (!arrLayers.length) {
                map.addLayer(layer_occurrence);
                layer_occurrence.redraw();
            }
            arrLayers = map.getLayersByName("<?php printMLtext('occurrence_overview') ?>");
            if (arrLayers.length) {
                map.removeLayer(layer_occurrence_overview);
            }
            document.getElementById("boxToggle").disabled = false; //can select occ. recs. using drag-box
        }
        map.setLayerIndex(layer_identify, 99); //keep on top of other layers
        map.setLayerIndex(layer_occ_select, 98);
        
    }

    function toggleControl(element) {
        for (var key in togglecontrols) {
            var control = togglecontrols[key];
            if (element.value == key && element.checked) {
                control.activate();
            } else {
                control.deactivate();
            }
        }
    }
    
    function maximiseMap() {
        /*var mapdiv = document.getElementById("map");
        mapdiv.setAttribute("class","map_fullscreen");*/
        $("#map").removeClass("map_normalsize");
        $("#map").addClass("map_fullscreen");
        $("#map-controlwrapper").removeClass("map_controls_normalsize");
        $("#map-controlwrapper").addClass("map_controls_fullscreen");
        map.updateSize();
        document.getElementById("btn-full-screen").style.display="none";
        document.getElementById("btn-partial-screen").style.display="block";
        document.getElementById("map-size-notice").style.display="block";
    }
    
    function normalMap() {
        /* var mapdiv = document.getElementById("map");
        mapdiv.setAttribute("class","map_normalsize"); */
        $("#map").removeClass("map_fullscreen");
        $("#map").addClass("map_normalsize");
        $("#map-controlwrapper").removeClass("map_controls_fullscreen");
        $("#map-controlwrapper").addClass("map_controls_normalsize");
        map.updateSize();        
        document.getElementById("btn-full-screen").style.display="block";
        document.getElementById("btn-partial-screen").style.display="none";
        document.getElementById("map-size-notice").style.display="none";
    }

    function printMap() {
        var layers = "";
        for (var i = 0; i < map.layers.length; i++) {
            if(map.layers[i].visibility == true){
                //get a string of visible layers
                vislayer = map.layers[i];                
                if (vislayer.isBaseLayer) continue; //cannot print any google layers                      
                console.log(vislayer);                
                if ('params' in vislayer) {
                    if ('LAYERS' in vislayer.params) {
                        layers = layers + '"' + vislayer.params['LAYERS'] + '",'; //name + '",'
                    }
                }
            }
        }
        //remove the trailing ','
        layers = layers.slice(0, -1);
        
        mapcenter = map.getCenter().transform('EPSG:3857', 'EPSG:4326'); //map is in EPSG:3857 remember
        mapcentertext = '"' + mapcenter.lon.toString() + '","' + mapcenter.lat.toString() + '"';
        mapscale = map.getScale();
        //rescale to allowable scales
        if (mapscale > 16000000) mapscale = 32000000;
        if (mapscale > 8000000 && mapscale < 16000000) mapscale = 16000000;
        if (mapscale > 4000000 && mapscale < 8000000) mapscale = 8000000;
        if (mapscale > 2000000 && mapscale < 4000000) mapscale = 4000000;
        if (mapscale > 1000000 && mapscale < 2000000) mapscale = 2000000;
        if (mapscale > 500000 && mapscale < 1000000) mapscale = 1000000;
        if (mapscale > 200000 && mapscale < 500000) mapscale = 500000;
        if (mapscale > 100000 && mapscale < 200000) mapscale = 200000;
        if (mapscale > 50000 && mapscale < 100000) mapscale = 100000;
        if (mapscale > 25000 && mapscale < 50000) mapscale = 50000;
        if (mapscale < 25000) mapscale = 25000;
        
        layout = 'A4 portrait';
        if (document.getElementById("btn-full-screen").style.display == "none") layout = 'A4 landscape';
        
        var pdfurl = "<?php echo $params['geoserver'] ?>/pdf/print.pdf?spec={" +
"\"units\":\"degrees\"," +
"\"srs\":\"EPSG:4326\"," +
"\"layout\":\"" + layout + "\"," +
"\"dpi\":\"300\"," +
"\"mapTitle\":\"ARBIMS <?php printMLtext('map') ?>\"," +
"\"comment\":\"<?php printMLtext('map_printing_google') ?>\"," +
"\"resourcesUrl\":\"<?php echo $params['base_url'] ?>/img\"," +
"\"layers\":[" +
"{" +
"\"baseURL\":\"<?php echo $params['geoserver'] ?>/wms\"," +
"\"opacity\":\"1\"," +
"\"singleTile\":true," +
"\"type\":\"WMS\"," +
"\"layers\":[" + layers.toString() + "]," +
"\"format\":\"image/jpeg\"," +
"\"styles\":[\"\"]" +
"}]," +
"\"pages\":[" +
"{" +
"\"center\":[" + mapcentertext + "]," +
"\"scale\":\"" + mapscale.toString() + "\"," +
"\"rotation\":\"0\"" +
"}]" +
"}";        
        window.open(pdfurl);
    }
    
    window.onload = init();
</script>
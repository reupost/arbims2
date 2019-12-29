$("html").addClass("js");
$.fn.accordion.defaults.container = false;
var already_loaded = new Object();  // used to track which accordions have already been loaded

$(function() {
    $("#species_accordion").accordion({        
        initShow: "#current",
        uri: "relative", //NB
        retFunc: function(treeUrl) {
            var node_link = $(treeUrl.context).attr('href');
            if(! already_loaded[node_link] == 1) {
                console.log(node_link);
                var treeContext = treeUrl.context.innerHTML.split(" ");
                var tRank = treeContext[0].replace(":", ""); //TODO: this is horrible
                var tEpithet = treeContext[1].replace("(unnamed)", "");
                var curLink = '';
                if (node_link.indexOf("#") > 0) {
                    curLink = node_link.substring(node_link.indexOf("#") + 1);
                }
                var urlTree = "data.taxontree.php?region=albertine&taxon_epithet=" + tEpithet + "&rank=" + tRank + "&use_backbone=true&use_other=true&use_occ=false&cur_link=" + curLink;
                $.get(urlTree).success(function (data) {
                    treeUrl.next().after(data);
                    already_loaded[node_link] = 1; // keep track of the loaded accordions
                });
            }
        }
    });


    var hash = window.location.hash.substr(1);
    if (hash > '') {
        var hashComponents = hash.split("_");
        var node = '';
        hashComponents.shift();
        loadSubtree(node, hashComponents);
    }

    function loadSubtree(node, remainingNodes) {
        if (node == '') {
            node = 'link';
        }
        node = node + "_" + remainingNodes[0];
        var selector = "a[href$='#" + node + "']";
        var link = $(selector);
        if (link) {
            console.log(link[0].text);
            $(link[0]).addClass('open');
            var treeContextTxt = link[0].text.toString();
            var treeContext = treeContextTxt.split(" ");
            var tRank = treeContext[0].replace(":", ""); //TODO: this is horrible
            var tEpithet = treeContext[1].replace("(unnamed)", "");
            var curLink = node;
            var urlTree = "data.taxontree.php?region=albertine&taxon_epithet=" + tEpithet + "&rank=" + tRank + "&use_backbone=true&use_other=true&use_occ=false&cur_link=" + curLink;

            $.ajax({
                url: urlTree,
                in_link: link,
                in_node: node,
                in_remainingNodes: remainingNodes,
                success: function(data) {
                    console.log(this.in_link[0]);
                    $(this.in_link[0]).next().after(data);
                    already_loaded[this.in_link] = 1; // keep track of the loaded accordions
                    if (this.in_remainingNodes.length > 1) {
                        this.in_remainingNodes.shift();
                        loadSubtree(this.in_node, this.in_remainingNodes);
                    }
                }
            });
        }
    }

    $("html").removeClass("js");
});



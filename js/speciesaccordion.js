$("html").addClass("js");
$.fn.accordion.defaults.container = false;
$(function() {
    $("#species_accordion").accordion({        
        initShow: "#current",
        uri: "relative" //NB
    });

    $("html").removeClass("js");
});



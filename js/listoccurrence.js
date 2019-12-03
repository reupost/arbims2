var QueryString = function () {
  // This function is anonymous, is executed immediately and 
  // the return value is assigned to QueryString!
  var query_string = {};
  var query = window.location.search.substring(1);
  var vars = query.split("&");
  for (var i=0;i<vars.length;i++) {
    var pair = vars[i].split("=");
    	// If first entry with this name
    if (typeof query_string[pair[0]] === "undefined") {
      query_string[pair[0]] = pair[1];
    	// If second entry with this name
    } else if (typeof query_string[pair[0]] === "string") {
      var arr = [ query_string[pair[0]], pair[1] ];
      query_string[pair[0]] = arr;
    	// If third or later entry with this name
    } else {
      query_string[pair[0]].push(pair[1]);
    }
  } 
    return query_string;
} ();

function PassOccurrenceCriteriaToMap(region) {
    //note that possible criteria are hard-coded here
    //var query = window.location.search.substring(1);
    var q2 = '';
    /* if (QueryString.filterlistby > '') q2 = 'filterlistby=' + QueryString.filterlistby;
    if (QueryString.datasetid > '') q2 = (q2>''? q2 + '&' : '') + 'datasetid=' + QueryString.datasetid;
    if (QueryString.taxon > '') q2 = (q2>''? q2 + '&' : '') + 'taxon=' + QueryString.taxon;
    if (QueryString.rank > '') q2 = (q2>''? q2 + '&' : '') + 'rank=' + QueryString.rank;
    if (QueryString.taxonparent > '') q2 = (q2>''? q2 + '&' : '') + 'taxonparent=' + QueryString.taxonparent;
    if (QueryString.x1 > '') q2 = (q2>''? q2 + '&' : '') + 'x1=' + QueryString.x1;
    if (QueryString.x2 > '') q2 = (q2>''? q2 + '&' : '') + 'x2=' + QueryString.x2;
    if (QueryString.y1 > '') q2 = (q2>''? q2 + '&' : '') + 'y1=' + QueryString.y1;
    if (QueryString.y2 > '') q2 = (q2>''? q2 + '&' : '') + 'y2=' + QueryString.y2;
    */
    //try without assoc array
    q2 = window.location.search.substring(1);
    
    if (q2 == '') q2 = 'mapocc=1'; //no criteria, but still want to map occurrences as kml
    window.location = 'out.map.' + region + '.php?' + q2;
    //need to strip out irrelevant
    return 0;
}

function DownloadData() {
    download_switch = document.getElementById('download');
    if (download_switch) {
        download_switch.value = 1;
    }
    document.getElementById('frm_browse').submit();
}
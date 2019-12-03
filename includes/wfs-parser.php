<?php
class WFSParser {
    var $parser;
    
    var $layername = '';
    var $fulllayername = '';
    var $attribute_array = array();    
    var $insidelist = false;
    
    var $feature_array = array();
    var $feat = array();
    var $insidefeat = false;
    var $parentelement = '';
    
    private function GetNameWithoutWorkspace($name) {
        $tmp = explode(':',$name);
        if (sizeof($tmp) > 1) {
            return $tmp[1];
        } else {
            return $name;
        }
    }
    
    public function SetWFSParserAttributes($fulllayername) {

        $this->parser = xml_parser_create( "UTF-8" );
        xml_parser_set_option( $this->parser, XML_OPTION_CASE_FOLDING, 0 );
        xml_set_element_handler( $this->parser, array(&$this,"_startElementAttrib"), array(&$this,"_endElementAttrib") );
        xml_set_character_data_handler( $this->parser, array(&$this,"_characterDataAttrib") );
        $this->attribute_array = array(); 
    }
    
    public function parseAttributes($wfs_xml) {        
        xml_parse($this->parser, $wfs_xml, TRUE);        
    }
    
    public function free_parser () {
        xml_parser_free( $this->parser );
    }
    
    function _startElementAttrib($parser, $name, $attrs) {
        if ($name == 'xsd:element') {
            if (array_key_exists('minOccurs',$attrs)) {
                $this->attribute_array[] = $attrs['name'];
            }
        }
    }
    
    function _endElementAttrib($parser, $name) {
    }
    
    function _characterDataAttrib($parser, $data) {
    }
    
    public function GetAttributeList() {
        return $this->attribute_array;
    }
    
    public function SetWFSParserFeatures($fulllayername) {
        $this->layername = $this->GetNameWithoutWorkspace($fulllayername);        
        $this->fulllayername = $fulllayername;
        $this->parser = xml_parser_create( "UTF-8" );
        xml_parser_set_option( $this->parser, XML_OPTION_CASE_FOLDING, 0 );
        xml_set_element_handler( $this->parser, array(&$this,"_startElementFeature"), array(&$this,"_endElementFeature") );
        xml_set_character_data_handler( $this->parser, array(&$this,"_characterDataFeature") );
        $this->feature_array = array(); 
    }
    
    public function parseFeatures($wfs_xml) {        
        xml_parse($this->parser, $wfs_xml, TRUE);        
    }
    
    function _startElementFeature($parser, $name, $attrs) {
        $nakedname = $this->GetNameWithoutWorkspace($name);
        $this->parentelement = $nakedname;
        if ($name == $this->fulllayername) {
            $this->insidefeat = true;
            $this->feat = array();
            $this->feat['fid'] = $attrs['gml:id'];
        }         
    }
    
    function _endElementFeature($parser, $name) {
        if ($name == $this->fulllayername) {
            $this->feature_array[] = $this->feat;
            $this->insidefeat = false;
        }
    }
    
    function _characterDataFeature($parser, $data) {
        if ($this->insidefeat) {
            $this->feat[$this->parentelement] = $data;
        }
    }
    
    public function GetFeatureList() {
        return $this->feature_array;
    }
}
?>
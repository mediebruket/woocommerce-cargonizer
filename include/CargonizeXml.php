<?php

  class CargonizeXml{
    public $Xml;

    function __construct($args){
      if ( $args ){
        $this->makeXml($args);
      }
    }


    function makeXml($args){
      _log($args);
      $this->getNodes($args);
      _log($this->Xml);
    }


    function getNodes($args){
      foreach ($args as $key => $value) {
        $attributes = array();
        // _log($key);
        // _log($value);
        if ( isset($value['_attr']) && is_array($value['_attr']) ){
          // _log('attributes');
          $attributes = $value['_attr'];
          unset($value['_attr']);
        }
        // _log('-----');

        if ( is_array($value) ){
          if ( is_numeric($key) ){
            $array = $value;
            reset($array);
            $key = key($array);
            // $key = $first_key;
            // _log('key/value');
            // _log($key);
            // _log($value);
            $value = $value[$key];

            if ( isset($value['_attr']) ){
              $attributes = $value['_attr'];
              unset($value['_attr']);
            }
          }
          $this->Xml .= $this->openNode($key, $attributes );
          $this->getNodes($value);
          $this->Xml .= $this->closeNode($key);
        }
        else{
          $this->Xml .= $this->addNode($key, $value );
        }
      }
    }


    function addNode($name, $content=null, $attributes=array()){
      return $this->openNode($name, $attributes).$content.$this->closeNode($name);
    }


    function openNode($name, $attributes=array() ) {
      return sprintf('<%s%s>', $name, $this->addXmlAttributes($attributes) );
    }


    function addXmlAttributes($attributes){
      $string = null;
      foreach ($attributes as $key => $value) {
        $string .= $key.'="'.$value.'" ';
      }
      if ( $string ){
        $string = " ".rtrim($string);
      }

      return $string;
    }


    function closeNode($name ) {
      return sprintf('</%s>'."\n", $name);
    }

} // end of class
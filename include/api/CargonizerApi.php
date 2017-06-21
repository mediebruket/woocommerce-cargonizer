<?php

/*
curl -g -XGET -d@consignment.xml -H'Content-Type: application/xml' -H'X-Cargonizer-Key: b38515a578db604ba77f063801155add075a56e4' -H'X-Cargonizer-Sender: 1142' 'http://sandbox.cargonizer.no/consignments.xml'
*/


class CargonizerApi{
  protected $Key;
  protected $Sender;
  protected $Domain;

  public $TransportAgreements;

  function __construct($set_transport_agreements=false){

    if ( get_option( 'cargonizer-sandbox-modus' ) ){
      // _log('sandbox');
      $this->Key    = get_option('cargonizer-sandbox-api-key' );
      $this->Sender = get_option('cargonizer-sandbox-api-sender' );
      $this->Domain = 'http://sandbox.cargonizer.no/';
      //_log($this);
    }
    else{
      // _log('prod');
      $this->Key    = get_option('cargonizer-api-key' );
      $this->Sender = get_option('cargonizer-api-sender' );
      $this->Domain = 'http://cargonizer.no/';
      //_log($this);
    }

    if ( $set_transport_agreements ){
      $this->TransportAgreements = $this->getTransportAgreements();
    }
  }


  function getTransportAgreements(){
    // curl -g -XGET -H'X-Cargonizer-Key: b38515a578db604ba77f063801155add075a56e4' -H'X-Cargonizer-Sender: 1142' 'http://sandbox.cargonizer.no/transport_agreements.xml'
    //_log('CargonizerApi::getTransportAgreements');
    $result = $this->rest('transport_agreements.xml', $headers=array(), $method='GET', $xml=null, $debug=false );
    return $result;
  }


  function getPrinters(){
    // curl -g -XGET -H'X-Cargonizer-Key: b38515a578db604ba77f063801155add075a56e4' -H'X-Cargonizer-Sender: 1142' 'http://sandbox.cargonizer.no/transport_agreements.xml'
    _log('CargonizerApi::getPrinters()');

    return $this->rest('printers.xml', $headers=array(), $method='GET', $xml=null, $debug=false );
  }


  function postLabel( $consignment_id, $printer_id ){
    _log('CargonizerApi::postLabel('.$consignment_id.' '.$printer_id.')');
    // curl -g -XPOST -H'X-Cargonizer-Key: 12345' -H'X-Cargonizer-Sender: 678' 'http://cargonizer.no/consignments/label_direct?printer_id=123&consignment_ids[]=1&consignment_ids[]=2&piece_ids[]=3&piece_ids[]=4'

    if ( $consignment_id && $printer_id ){
      $args =
        array(
          'printer_id' => $printer_id,
          'consignment_ids[]' => $consignment_id
        );

      $resource = 'consignments/label_direct?';

      if ( $query_string = $this->buildQueryString($args) ){
        $resource .= $query_string;
      }


      return $this->rest( $resource,  $headers=array(), $method='GET', $xml=null, $debug=true );
    }
    else{
      return null;
    }
  }


  function postConsignment($xml){
    // curl -g -XGET -H'X-Cargonizer-Key: b38515a578db604ba77f063801155add075a56e4' -H'X-Cargonizer-Sender: 1142' 'http://sandbox.cargonizer.no/transport_agreements.xml'
    _log('CargonizerApi::postConsignment()');

    $headers =
      array(
        'Content-Type' =>  'application/xml'
      );

    return $this->rest('consignments.xml', $headers, 'POST', $xml, $debug=true, true );
  }


  function getAgreementByName($name){
    // _log('getAgreement');
    $agreement = null;

    foreach ($this->TransportAgreements['transport-agreements']['transport-agreement'] as $key => $value) {
      if ( isset($value['carrier']['identifier']) && $value['carrier']['identifier'] == $name ){
        $agreement = $value;
        break;
      }
    }

    return $agreement;
  }


  function estimateCosts( $xml ){
    // curl -g -XPOST -d@consignment.xml -H'X-Cargonizer-Key: 12345' -H'X-Cargonizer-Sender: 678' 'http://cargonizer.no/consignment_costs.xml'

    _log('CargonizerApi::estimateCosts()');

    $headers =
      array(
        'Content-Type' =>  'application/xml'
      );

    return $this->rest('consignment_costs.xml', $headers, 'POST', $xml, $debug=true );
  }


  function getAgreementById($id){
    // _log('getAgreement');
    $agreement = null;

    foreach ($this->TransportAgreements['transport-agreements']['transport-agreement'] as $key => $value) {
      if ( isset($value['carrier']['identifier']) && $value['carrier']['identifier'] == 'bring' ){
        $agreement = $value;
        break;
      }
    }

    return $agreement;
  }


  function buildQueryString( $query_args ){
    return http_build_query($query_args );

  }


  function rest( $resource, $headers=array(), $method='GET', $xml=null, $debug=false, $force_response = false ){
    _log('Cargonizer::rest()');

    $default_headers =
      array(
        'X-Cargonizer-Key'    => $this->Key,
        'X-Cargonizer-Sender' => $this->Sender
      );

    $headers = array_merge($default_headers, $headers);

    // _log('rest');
    // _log($resource);
    // _log($headers);

    $args = array(
      'timeout'     => 30,
      // 'redirection' => 5,
      //'httpversion' => '1.0',
      'user-agent'  => 'WordPress/Woocommerce: ' . get_bloginfo( 'url' ),
      // 'blocking'    => true,
      'headers'     => $headers,
      'body'        => $xml,
      // 'compress'    => false,
      // 'decompress'  => true,
      // 'sslverify'   => true,
      // 'stream'      => false,
      // 'filename'    => null
      'method'      => $method
    );


    $url = $this->Domain.$resource;
    $response = wp_remote_request( $url, $args );
    $status = wp_remote_retrieve_response_code($response);


    if ( $debug ){
      _log($url);
      _log($args);
      _log('response:');
      _log($response);
    }

    if ( $status==200 or $status==201 or $status == 202 or $force_response ){
      if ( $status == 200 or $status == 201 or $status == 400 ){
        return xmlToArray( simplexml_load_string(wp_remote_retrieve_body($response)) );
      }
      else{
        return wp_remote_retrieve_body($response);
      }
    }
    else{
      return null;
    }
  }


} // end of class

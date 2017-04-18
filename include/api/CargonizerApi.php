<?php

/*
curl -g -XGET -d@consignment.xml -H'Content-Type: application/xml' -H'X-Cargonizer-Key: b38515a578db604ba77f063801155add075a56e4' -H'X-Cargonizer-Sender: 1142' 'http://sandbox.cargonizer.no/consignments.xml'
*/

define('API_KEY', 'e6333abfd77d721910eec5230993f83595c66cdc');
define('API_SENDER', '2577');

define('SANDBOX_KEY', 'b38515a578db604ba77f063801155add075a56e4');
define('SANDBOX_SENDER', '1142');


class CargonizerApi{
  protected $Key;
  protected $Sender;
  protected $Domain;

  public $TransportAgreements;

  function __construct($set_transport_agreements=false){

    if ( get_option( 'cargonizer-sandbox-modus' ) ){
      _log('sandbox');
      $this->Key    = get_option('cargonizer-sandbox-api-key' );
      $this->Sender = get_option('cargonizer-sandbox-api-sender' );
      $this->Domain = 'http://sandbox.cargonizer.no/';
      //_log($this);
    }
    else{
      _log('prod');
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
    _log('getTransportAgreements');

    return $this->rest('transport_agreements.xml' );
  }

  function postConsignment($xml){
    // curl -g -XGET -H'X-Cargonizer-Key: b38515a578db604ba77f063801155add075a56e4' -H'X-Cargonizer-Sender: 1142' 'http://sandbox.cargonizer.no/transport_agreements.xml'
    _log('getTransportAgreements');

    $headers =
      array(
        'Content-Type' =>  'application/xml'
      );

    return $this->rest('consignments.xml', $headers, 'POST', $xml );
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


  function rest($resource, $headers=array(), $method='GET', $xml =null){
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
    //_log($url);
    _log($args);
    $response = wp_remote_request( $url, $args );
    _log('response:');
    // _log($response);

    $status = wp_remote_retrieve_response_code($response);

    _log('status: '.$status);

    if ( $status==200 || $status==201 ){
      return xmlToArray( simplexml_load_string(wp_remote_retrieve_body($response)) );
    }
    else{
      return null;
    }
  }


}

?>
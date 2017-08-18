<?php

class CargonizerCommonController{
  protected $Order;
  protected $WC_Order;
  protected $Settings;

  function __construct( ){
    $this->Settings = new CargonizerOptions();
    add_filter( 'wp_mail_content_type', array($this, 'setMailContentType') );
  }


  function getTransportAgreementChoices(){
    $choices = array();
    $agreements = $this->Settings->get('TransportAgreements');
    if ( is_array($agreements) ){
      foreach ($agreements as $key => $a) {
        $choices[$a['id']] = $a['title'];
      }
    }

    return $choices;
  }


   

  public static function getRecurringConsignmentInterval(){
    $intervals = array();

    for( $i=1; $i<=30; $i++ ){
      $intervals[$i] = sprintf( __('every %sth', 'wc-cargonizer'), $i );
    }
    return $intervals;
  }


  


  function setMailContentType(){
    return 'text/html';
  }


  function setCustomProvider($args){
    $args['Norway']['Cargonizer'] = 'tracking_url';
    ksort($args);

    return $args;
  }


  function isOrder( $object=true ){
    global $post;

    if ( isset($post->post_type) && $post->post_type == 'shop_order' ){
      if ( $object ){
        return new ShopOrder($post->ID);
      }
      else{
        return $post->ID;
      }
    }
    else{
      return null;
    }
  }


  function isConsignment($object=true ){
    global $post;

    if ( isset($post->post_type) && $post->post_type == 'consignment' ){
      if ( $object ){
        return new Consignment($post->ID);
      }
      else{
        return $post->ID;
      }

    }
    else{
      return null;
    }
  }


  function acf_setParcelPrinter($field){
    // _log('Cargonizer::acf_setParcelPrinter');
    // _log($this->Settings);
    if ( $printers = CargonizerOptions::getPrinterList() ){
      $field['choices'] = array();
      foreach ($printers as $printer_id => $printer_name) {
        $field['choices'][$printer_id] = $printer_name;
      }
    }

    if ( $default_printer =  $this->Settings->get('DefaultPrinter' ) ) {
      $field['default_value'] = $default_printer;
    }


    return $field;
  }


   

} // end of class
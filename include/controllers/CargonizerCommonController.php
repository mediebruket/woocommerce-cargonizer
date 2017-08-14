<?php

class CargonizerCommonController{
  protected $Order;
  protected $WC_Order;
  protected $Settings;

  function __construct( ){
    $this->Settings = new CargonizerOptions();

    add_filter('acf/load_field/name=parcel_printer', array($this, 'acf_setParcelPrinter'), 20 );
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


  function acf_setRecurringConsignmentInterval( $field ){
    for( $i=1; $i<=30; $i++ ){
      $field['choices'][$i] = sprintf( __('every %sth', 'wc-cargonizer'), $i );
    }
    return $field;
  }

  public static function getRecurringConsignmentInterval(){
    $intervals = array();

    for( $i=1; $i<=30; $i++ ){
      $intervals[$i] = sprintf( __('every %sth', 'wc-cargonizer'), $i );
    }
    return $intervals;
  }


  function acf_setParcelPrintOnPost( $field ){
    // _log('acf_setParcelPrintOnPost');
    // _log($field);
    if ( get_option('cargonizer-print-on-export' ) ){
      $field['default_value'] = true;
    }

    return $field;
  }


  function setMailContentType(){
    return 'text/html';
  }


  function acf_setParcelAutoTransfer( $field ){
    if ( get_option('cargonizer-auto-transfer' ) ){
      $field['default_value'] = true;
    }

    return $field;
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


  function acf_setDefaultHeight($field){
    if ( $value = get_option('cargonizer-parcel-height' ) ){
      $field['default_value'] = $value;
    }
    return $field;
  }


  function acf_setDefaultLength($field){
    if ( $value = get_option('cargonizer-parcel-length' ) ){
      $field['default_value'] = $value;
    }
    return $field;
  }


  function acf_setDefaultWeight($field){
    $weight = null;

    if ( isset($_GET['post']) && is_numeric($_GET['post']) ){
      $post = get_post($_GET['post']);

      if ( $post->post_type == 'shop_order'){
        $Order = new WC_Order($_GET['post']);

        $order_items = $Order->get_items();
        if ( is_array($order_items)) {
          foreach( $order_items as $item ) {
            if ( $item['product_id'] > 0 ) {
              $_product = $Order->get_product_from_item( $item );
              if ( $_product && is_object($_product) && method_exists($_product, 'is_virtual') && !$_product->is_virtual() ) {
                $weight += $_product->get_weight() * $item['qty'];
              }
            }
          }
        }

        $field['default_value'] = $weight;
      }
    }


    return $field;
  }


  function acf_setDefaultWidth($field){
    if ( $value = get_option('cargonizer-parcel-width' ) ){
      $field['default_value'] = $value;
    }
    return $field;
  }

} // end of class
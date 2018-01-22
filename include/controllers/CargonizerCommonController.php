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


  public static function getRecurringConsignmentInterval( $prefix = null ){
    $intervals = array();

    for( $i=1; $i<=30; $i++ ){
      $intervals[$i] = sprintf( __('%s %sth', 'wc-cargonizer'), $prefix, $i );
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


  function isOrder( $post_id, $object=true ){
    // global $post;
    $post = new StdClass();
    if ( is_numeric($post_id) ){
      $post = get_post($post_id);
    }

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


  public static function getProductsByCarrierId( $carrier_id ){
    $products = array();

    $agreements = get_transient('transport_agreements');

    if ( is_array($agreements) ){
      foreach ($agreements as $key => $a) {
        if ( $a['id'] == $carrier_id ){
          if ( is_array($a) && isset($a['products']) && is_array($a['products']) ){
            $products = $a['products'];
            break;
          }
        }
      }
    }

    //_log($products);

    return $products;
  }




} // end of class
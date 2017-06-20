<?php

class Parcel{
  public $ConsignmentId;
  public $ID;
  public $Id;
  public $Items;
  public $IsCargonized;
  public $Printer;
  public $ParcelType;
  public $ParcelMessage;
  public $ShippingDate;
  public $TrackingProvider;
  public $TransportAgreements;
  public $TransportAgreementId;
  public $TransportAgreementProduct;
  public $TransportAgreementProductType;
  public $Meta;
  public $WC_Order;


  function __construct($post_id){

    if ( is_numeric($post_id) ){
      // set woocommerce order object
      $this->WC_Order = new WC_Order($post_id);
      $this->ID = $this->Id = $post_id;

      if ( $post = get_post($post_id) ){
        // set wordpress attributes
        foreach ($post as $attribute => $value) {
          $this->$attribute = $value;
        }

        $this->Meta           = $this->getPostMeta();
        $this->ShippingDate   = $this->getShippingDate();
        $this->IsCargonized   = $this->isCargonized();
        $this->Printer        = $this->getPrinter();
        $this->ParcelType     = $this->getParcelType();
        $this->ParcelServices = $this->getParcelServices();
        $this->ParcelMessage  = $this->getParcelMessage();

        // recurring
        $this->IsRecurring                  = $this->getIsRecurring();
        $this->RecurringCarrierId           = $this->getRecurringCarrierId();
        $this->RecurringConsignmentType     = $this->getRecurringConsignmentType();
        $this->RecurringConsignmentServices = $this->getRecurringConsignmentServices();
        $this->RecurringConsignmentItems    = $this->getRecurringConsignmentItems();
        $this->RecurringInterval            = $this->getRecurringInterval();
        $this->RecurringConsignmentMessage  = $this->getRecurringConsignmentMessage();

        $this->ConsignmentId = $this->getConsignmentId();
        $this->getTransportAgreementSettings();
        $this->Items = $this->getItems();

        $this->Products = $this->WC_Order->get_items();

        if ( is_array($this->Products) ){
          foreach ($this->Products as $key => $product) {
            $this->Products[$key]['is_subscription'] = WC_Subscriptions_Product::is_subscription( $product['product_id'] ) ;
            unset($this->Products[$key]['item_meta_array']);
            unset($this->Products[$key]['item_meta']);
            unset($this->Products[$key]['line_tax_data']);
          }
        }
        // _log($this->Products);
      }
    }
  }


  function getPostMeta(){
    return get_post_custom( $this->ID );
  }


  function getPrinter(){
    return gi($this->Meta, 'parcel_printer');
    //_log($this->Printer);
  }


  function getShippingDate(){
    return gi($this->Meta, 'parcel_shipping_date');
    //_log($this->Printer);
  }


  function getIsRecurring(){
    return gi($this->Meta, 'parcel-is-recurring');
    //_log($this->Printer);
  }


  function getRecurringInterval(){
    return gi($this->Meta, 'parcel-recurring-consignment-interval' );
  }


  function getParcelMessage(){
    return gi($this->Meta, 'message_consignee' );
  }


  function getRecurringCarrierId(){
    return gi($this->Meta, 'parcel_recurring_carrier_id');
    //_log($this->Printer);
  }


  function getRecurringConsignmentType(){
    return gi($this->Meta, 'parcel-recurring-consignment-type');
    //_log($this->Printer);
  }


  function getRecurringConsignmentMessage(){
    return gi($this->Meta, 'parcel-consignment-message');
    //_log($this->Printer);
  }


  function getRecurringConsignmentServices(){
    return maybe_unserialize( gi($this->Meta, 'parcel-recurring-consignment-services') );
    //_log($this->Printer);
  }


  function getRecurringConsignmentItems(){
    // _log('Parcel::getRecurringConsignmentItems()');

    $items = acf_getField('parcel-recurring-consignment-items', $this->ID);

    if ( is_array($items) ){
      foreach ($items as $key => $item) {
        if ( isset($item['parcel_recurring_consignment_type']) ){
          $items[$key]['parcel_package_type'] = $item['parcel_recurring_consignment_type'];
        }
        if ( isset($item['parcel_recurring_consignment_description']) ){
          $items[$key]['parcel_description'] = $item['parcel_recurring_consignment_description'];
        }
        if ( isset($item['parcel_recurring_consigment_weight']) ){
          $items[$key]['parcel_weight'] = $item['parcel_recurring_consigment_weight'];
        }
        if ( isset($item['parcel_recurring_consignment_height']) ){
          $items[$key]['parcel_height'] = $item['parcel_recurring_consignment_height'];
        }
        if ( isset($item['parcel_recurring_consignment_length']) ){
          $items[$key]['parcel_length'] = $item['parcel_recurring_consignment_length'];
        }
        if ( isset($item['parcel_recurring_consignment_width']) ){
          $items[$key]['parcel_width'] = $item['parcel_recurring_consignment_width'];
        }
        if ( isset($item['parcel_recurring_consignment_amount']) ){
          $items[$key]['parcel_amount'] = $item['parcel_recurring_consignment_amount'];
        }
        if ( isset($item['parcel_recurring_consignment_type']) ){
          $items[$key]['parcel_type'] = $item['parcel_recurring_consignment_type'];
        }
      }
    }

    return $items;
  }


  function hasFutureShippingDate(){
    if ( $this->ShippingDate > date('Ymd') ){
      return true;
    }
    else{
      return false;
    }
  }


  function getParcelType(){
    return gi($this->Meta, 'parcel_type');
    //_log($this->Printer);
  }


  function getParcelServices(){
    return maybe_unserialize( gi($this->Meta, 'parcel_services') );
    //_log($this->Printer);
  }


  function getConsignmentId(){
    return gi($this->Meta, 'consignment_id');
  }



  function isCargonized(){
    return  gi($this->Meta, 'is_cargonized');
  }

  function setCargonized(){
    update_post_meta( $this->ID, 'is_cargonized', '1' );
  }


  function addNote( $type = 'exported' ){
    _log('Cargonizer::addNote('.$this->ID.')');

    $data = array(
      'comment_post_ID'       => $this->ID,
      'comment_author'        => 'WooCommerce Cargonizer',
      'comment_author_email'  => get_option('admin_email' ),
      'comment_content'       => sprintf( __('Cargonizer: Parcel %s', 'wc-cargonizer'), $type ),
      'comment_agent'         => 'WooCommerce Cargonizer',
      'comment_type'          => 'order_note',
      'comment_parent'        => 0,
      'user_id'               => 1,
      'comment_author_IP'     => 'null',
      'comment_date'          => current_time('mysql'),
      'comment_approved'      => 1,
    );

    if ( $type == 'exported' ){
      $data['comment_content'] = '<br/>'.sprintf( __('Consignment id: %s', 'wc-cargonizer'), get_post_meta( $this->ID, 'consignment_id', true ) );
    }

    if ( wp_insert_comment($data) ){
      _log('new note added');
    }
    else{
      _log('error: wp_insert_comment');
      _log($data);
    }
  }


  function reset(){
    _log('Parcell::reset('.$this->ID.')');
    $rf =
      array(
          'is_cargonized',
          'parcel_printer',
          'transport_agreement',
          'parcel_type',
          'parcel_services',
          'message_consignee',
          'items',
          'create_consignment',
          'confirmation',
          'consignment_created_at',
          'consignment_id',
          'consignment_tracking_code',
          'consignment_tracking_url',
          'consignment_pdf',
        );

    foreach ($rf as $index => $field) {
      _log('reset: '.$field);
      acf_updateField($field, null, $this->ID );
    }
  }


  function isReady( $force = false ){
    _log('Parcel::isReady()');
    $is_ready = false;
    // if parcel is not exported / cargonized
    if ( !gi($this->Meta, 'is_cargonized') or $force ){
      // if parcel has transport agreement id & product && items

      if ( !$this->TransportAgreementId ){
        _log('missing transport agreement id');
      }

      if ( !$this->TransportAgreementProduct ){
        _log('missing transport agreement product');
      }

      if ( !$this->Items ){
        _log('missing items');
      }

      if ( $this->TransportAgreementId && $this->TransportAgreementProduct && $this->Items ){
        // checkbox create_consignment is on
        if ( gi($this->Meta, 'create_consignment') ){
          $is_ready = true;
        }
        else{
          _log('do not send to cargonizer');
        }
      }
    }
    else{
      _log('already cargonized');
    }


    return $is_ready;
  }


  function getTransportAgreementSettings(){
    // _log('Parcel::getTransportAgreementSettings');
    $this->TransportAgreementId = null;
    if ( $ta = gi($this->Meta, 'transport_agreement') ){

      $transport_agreement = explode('|', $ta);
      // _log('$ta');
      // _log($ta);
      if ( isset($transport_agreement[0]) ){
        $this->TransportAgreementId = $transport_agreement[0];
      }
    }

    $this->TransportAgreementProduct = $this->TransportAgreementProductType = null;
    if ( $parcel_type = gi($this->Meta, 'parcel_type') ){
      $parcel_type = explode('|', $parcel_type);
      // _log('$parcel_type');
      // _log($parcel_type);
      if ( isset($parcel_type[0]) ){
        $this->TransportAgreementProduct = $parcel_type[0];
      }

      if ( isset($parcel_type[1]) ){
        $this->TransportAgreementProductType = $parcel_type[1];
      }
    }
  }


  function getItems(){
    return acf_getField('consignment_items', $this->ID);
  }


  public static function getPlaceholders(){
    return array( '@order_id@', '@shop_name@', '@parcel_tracking_url@', '@parcel_tracking_link@', '@parcel_tracking_code@', '@parcel_date@' );
  }


  public static function _getTransportAgreements(){
    return get_transient('transport_agreements');
  }


  function saveConsignmentDetails( $consignment ){
    _log('Parcel::saveConsignmentDetails');
    _log($consignment);
    acf_updateField('consignment_created_at', $consignment['created-at']['$'], $this->ID);

    if ( $consignment['bundles']['bundle'] ){
      $bundle = $consignment['bundles']['bundle'];
      if ( isset($bundle[0]) ){
        $consignment_id = $bundle[0]['consignment-id']['$'];
      }
      else {
       $consignment_id = $bundle['consignment-id']['$'];
      }

      acf_updateField('consignment_id', $consignment_id, $this->ID);
    }


    acf_updateField('consignment_tracking_code', $consignment['number'], $this->ID);
    acf_updateField('consignment_tracking_url', $consignment['tracking-url'], $this->ID);
    acf_updateField('consignment_pdf', $consignment['consignment-pdf'], $this->ID);
    //acf_updateField('consignment_estimated_costs', '1234', $this->ID);
    $this->getPostMeta();
  }


} // end of class
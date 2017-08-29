<?php

class ShopOrder{
  public $AutoTransfer;
  public $CarrierId;
  public $ConsignmentId;
  public $ID;
  public $Id;
  public $Items;
  public $IsCargonized;
  public $Printer;
  public $ParcelType;
  public $ParcelMessage;
  public $ParcelServices;
  public $ParcelPackages;
  public $ShippingDate;
  public $TrackingProvider;
  public $TransportAgreements;
  public $TransportAgreementId;
  public $TransportAgreementProduct;
  public $TransportAgreementProductType;
  public $Meta;
  public $WC_Order;


  function __construct($post_id){
    //_log("ShopOrder::__construct");

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
        $this->CarrierId      = $this->getCarrierId();
        $this->CarrierProduct = $this->getCarrierProduct();


        $this->IsCargonized   = $this->isCargonized();
        $this->Printer        = $this->getPrinter();
        $this->PrintOnExport  = $this->getPrintOnExport();
        $this->AutoTransfer   = $this->getAutoTransfer();

        $this->ParcelType     = $this->getParcelType();
        $this->ParcelServices = $this->getParcelServices();
        $this->ParcelMessage  = $this->getParcelMessage();
        $this->ParcelPackages = $this->getPackages();


        // recurring
        $this->IsRecurring                      = $this->getIsRecurring();
        $this->RecurringCarrierId               = $this->getRecurringCarrierId();
        $this->RecurringCarrierProduct          = $this->getRecurringCarrierProduct();
        $this->RecurringConsignmentProductType  = $this->getRecurringConsignmentProductType();
        $this->RecurringConsignmentServices     = $this->getRecurringConsignmentProductServices();
        $this->RecurringConsignmentItems        = $this->getRecurringConsignmentItems();
        $this->RecurringInterval                = $this->getRecurringInterval();
        $this->RecurringConsignmentMessage      = $this->getRecurringConsignmentMessage();
        $this->RecurringStartDate               = $this->getRecurringStartDate();

        $this->ConsignmentId            = $this->getConsignmentId();
        $this->ConsignmentCreatedAt     = $this->getConsignmentCreatedAt();
        $this->ConsignmentTrackingUrl   = $this->getConsignmentTrackingUrl();
        $this->ConsignmentTrackingCode  = $this->getConsignmentTrackingCode();
        $this->ConsignmentPdf           = $this->getConsignmentPdf();
        //$this->getTransportAgreementSettings();


        $order_items = $this->WC_Order->get_items();
        $this->Products = array();

        if ( is_array($order_items) ){
          foreach ($order_items as $key => $product) {
            $this->Products[$key] = $product->get_data();
            $this->Products[$key]['is_subscription'] = false;
            if ( class_exists('WC_Subscriptions_Product') ){
              $this->Products[$key]['is_subscription'] = WC_Subscriptions_Product::is_subscription( $product['product_id'] );
            }
          } // foreach
        } // if

      } // if post
    } // if is numeric
  } // construct



  function getPostMeta(){
    return get_post_custom( $this->ID );
  }


  function getPackages(){
    $meta_key = 'parcel_packages';
    $packages = maybe_unserialize( gi($this->Meta, $meta_key) );

    if ( !$packages or is_array($packages) and empty($packages) ){
      _log('get default package...');
      $default = $this->getDefaultPackage();
      $packages = array('1' => $default);
      update_post_meta( $this->ID, $meta_key, $packages );
    }

    return $packages;
  }


  function getRecurringConsignmentItems(){
    $meta_key = 'recurring_consignment_packages';
    $packages = maybe_unserialize( gi($this->Meta, $meta_key) );

    if ( !$packages or is_array($packages) and empty($packages) ){
      $default = $this->getDefaultPackage();
      $packages = array('1' => $default);
      update_post_meta( $this->ID, $meta_key, $packages );
    }

    return $packages;
  }


  function getDefaultPackage(){
    return $default = array(
        'id' => 1,
        'parcel_amount'       => 1,
        'parcel_description'  => '',
        'parcel_weight'       =>  $this->getTotalWeight(),
        'parcel_height'       => get_option('cargonizer-parcel-height'),
        'parcel_length'       => get_option('cargonizer-parcel-length'),
        'parcel_width'        => get_option('cargonizer-parcel-width')
      );
  }


  function getTotalWeight(){
    $weight = null;
    $order_items = $this->WC_Order->get_items();

    if ( is_array($order_items) ){
      foreach( $order_items as $item ){
        if ( $item['product_id'] > 0 ){
          $_product = $this->WC_Order->get_product_from_item( $item );
          if ( $_product && is_object($_product) && method_exists($_product, 'is_virtual') && !$_product->is_virtual() ) {
            $weight += $_product->get_weight() * $item['qty'];
          }
        }
      }
    }

    if (  $weight ){
      $weight = number_format($weight, 2, '.', ' ');
    }

    return $weight;
  }



  function getPrinter(){
    return gi($this->Meta, 'parcel_printer');
  }


  function getPrintOnExport(){
    if ( isset($this->Meta['parcel_print_on_post']) ){
      return gi($this->Meta, 'parcel_print_on_post' );
    }
    else{
      return get_option( 'cargonizer-print-on-export' );
    }
  }


  function getCarrierId(){
    return gi($this->Meta, 'parcel_carrier_id');
  }


  function getCarrierProduct(){
    return gi($this->Meta, 'parcel_carrier_product');
  }


  function getAutoTransfer(){
     if ( isset($this->Meta['parcel_auto_transfer']) ){
      return gi($this->Meta, 'parcel_auto_transfer' );
    }
    else{
      return get_option( 'cargonizer-auto-transfer' );
    }
  }


  function getShippingDate(){
    return gi($this->Meta, 'parcel_shipping_date');
    //_log($this->Printer);
  }


  function getIsRecurring(){
    return gi($this->Meta, 'is_recurring');
    //_log($this->Printer);
  }


  function getRecurringInterval(){
    if ( isset($this->Meta['recurring_consignment_interval'][0]) ){
      return $this->Meta['recurring_consignment_interval'][0];
    }
    else{
      return get_option( 'cargonizer-recurring-consignments-default-interval', 1 );
    }
  }


  function getRecurringStartDate(){
    if ( isset($this->Meta['recurring_consignment_start_date'][0]) ){
      return gi($this->Meta, 'recurring_consignment_start_date');
    }
    // estimate a default start date
    else{
      $start_date = Consignment::calcNextShippingDate( $this->RecurringInterval, $auto_inc=false );
      if ( get_option( 'cargonizer-recurring-consignments-skip-interval' ) ){
        $today = time();
        //$today =  strtotime("2017-08-15");
        $after_date = date('Y')."-".date('m')."-".get_option( 'cargonizer-recurring-consignments-skip-after' );
        $after_time = strtotime( $after_date );

        if ( $today > $after_time ){
          $start_date = Consignment::calcSkippedShipppingDate( $start_date, get_option( 'cargonizer-recurring-consignments-count-skip-intervals', 0 ) );
        }
      }

      return $start_date;
    }
  }


  function getParcelMessage(){
    if ( isset($this->Meta['parcel_message_consignee'][0]) ){
      return $this->Meta['parcel_message_consignee'][0];
    }
    else{
      $default = get_option('cargonizer-parcel-message-consignee' );
      $default = str_replace('@order_id@', $this->Id, $default);
      return $default;
    }
  }


  function getRecurringCarrierId(){
    return gi($this->Meta, 'recurring_consignment_carrier_id');
    //_log($this->Printer);
  }


  function getRecurringCarrierProduct(){
    return gi($this->Meta, 'recurring_consignment_carrier_product');
  }


  function getRecurringConsignmentProductType(){
    return gi($this->Meta, 'recurring_consignment_product_type');
    //_log($this->Printer);
  }


  function getRecurringConsignmentType(){
    return gi($this->Meta, 'recurring_consignment_product_type');
    //_log($this->Printer);
  }


  function getRecurringConsignmentMessage(){
    if (  isset($this->Meta['recurring_consignment_message_consignee'][0]) ){
      return $this->Meta['recurring_consignment_message_consignee'][0];
    }
    else{
      $default = get_option('cargonizer-parcel-message-consignee' );
      $default = str_replace('@order_id@', $this->Id, $default);
      return $default;
    }

    //_log($this->Printer);
  }


  function getRecurringConsignmentProductServices(){
    $services = array();

    if ( isset($this->Meta['recurring_consignment_product_services']) ){
      $services = $this->Meta['recurring_consignment_product_services'];

      if ( is_array($services) && isset($services[0]) && is_string($services[0]) ){
        $services = $services[0];
      }
    }
    else{
      $services = null;
    }


    return maybe_unserialize( $services );
    //_log($this->Printer);
  }


  function hasFutureShippingDate(){
    if ( cleanDate($this->ShippingDate) > date('Ymd') ){
      return true;
    }
    else{
      return false;
    }
  }


  function getParcelType(){
    return gi($this->Meta, 'parcel_carrier_product_type');
    //_log($this->Printer);
  }


  function getParcelServices(){
    $services = array();

    if ( isset($this->Meta['parcel_carrier_product_services']) ){
      $services = $this->Meta['parcel_carrier_product_services'];

      if ( is_array($services) && isset($services[0]) &&  is_string($services[0]) ){
        $services = $services[0];
      }
    }
    else{
      $services = null;
    }

    return maybe_unserialize( $services );
  }


  function getConsignmentId(){
    return gi($this->Meta, 'consignment_id');
  }


  function getConsignmentCreatedAt(){
    return gi($this->Meta, 'consignment_created_at');
  }

  function getConsignmentTrackingUrl(){
    return gi($this->Meta, 'consignment_tracking_url');
  }


  function getConsignmentTrackingCode(){
    return gi($this->Meta, 'consignment_tracking_code');
  }


  function getConsignmentPdf(){
    return gi($this->Meta, 'consignment_pdf');
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
    _log('ShopOrder::reset('.$this->ID.')');
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
      delete_post_meta($this->ID, $field);
    }
  }


  function isReady( $force = false ){
    _log('ShopOrder::isReady()');
    $is_ready = false;
    // if parcel is not exported / cargonized
    if ( !gi($this->Meta, 'is_cargonized') or $force ){
      // if parcel has transport agreement id & product && items

      if ( !$this->CarrierId ){
        _log('missing CarrierId');
      }

      if ( !$this->CarrierProduct ){
        _log('missing CarrierProduct');
      }

      if ( !$this->ParcelPackages ){
        _log('missing items');
      }

      if ( $this->CarrierId && $this->CarrierProduct && $this->ParcelPackages ){
        // checkbox create_consignment is on
        if ( gi($_POST, 'parcel_create_consignment_now') ){
          $is_ready = true;
        }
        else{
          _log('parcel_create_consignment_now not checked');
        }
      }
    }
    else{
      _log('already cargonized');
    }


    return $is_ready;
  }


  // function getTransportAgreementSettings(){
  //   _log('ShopOrder::getTransportAgreementSettings');
  //   $this->TransportAgreementId = null;
  //   if ( $ta = gi($this->Meta, 'transport_agreement') ){
  //     _log($ta);

  //     $transport_agreement = explode('|', $ta);
  //     // _log('$ta');
  //     // _log($ta);
  //     if ( isset($transport_agreement[0]) ){
  //       $this->TransportAgreementId = $transport_agreement[0];
  //     }
  //   }

  //   $this->TransportAgreementProduct = $this->TransportAgreementProductType = null;
  //   if ( $parcel_type = gi($this->Meta, 'parcel_type') ){
  //     $parcel_type = explode('|', $parcel_type);
  //     // _log('$parcel_type');
  //     // _log($parcel_type);
  //     if ( isset($parcel_type[0]) ){
  //       $this->TransportAgreementProduct = $parcel_type[0];
  //     }

  //     if ( isset($parcel_type[1]) ){
  //       $this->TransportAgreementProductType = $parcel_type[1];
  //     }
  //   }
  // }


  public static function getPlaceholders(){
    return array( '@order_id@', '@shop_name@', '@parcel_tracking_url@', '@parcel_tracking_link@', '@parcel_tracking_code@', '@parcel_date@' );
  }


  public static function _getTransportAgreements(){
    return get_transient('transport_agreements');
  }


  function saveConsignmentDetails( $consignment ){
    _log('Parcel::saveConsignmentDetails');
    _log($consignment);

    update_post_meta( $this->ID, 'consignment_created_at', $consignment['created-at']['$'] );
    update_post_meta( $this->ID, 'consignment_tracking_code', $consignment['number'] );
    update_post_meta( $this->ID, 'consignment_tracking_url', $consignment['tracking-url'] );
    update_post_meta( $this->ID, 'consignment_pdf', $consignment['consignment-pdf'] );


    if ( $consignment['bundles']['bundle'] ){
      $bundle = $consignment['bundles']['bundle'];
      if ( isset($bundle[0]) ){
        $consignment_id = $bundle[0]['consignment-id']['$'];
      }
      else {
       $consignment_id = $bundle['consignment-id']['$'];
      }
      update_post_meta( $this->ID, 'consignment_id', $consignment_id );
    }

    $this->getPostMeta();
  }


} // end of class
<?php
/**
 * class ShopOrder to handle the custom post type "shop_order" (registered by woocommerce)
 *
 **/

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
  public $Products;
  public $ShippingDate;
  public $ServicePartner;
  public $TrackingProvider;
  public $TransportAgreements;
  public $TransportAgreementId;
  public $TransportAgreementProduct;
  public $TransportAgreementProductType;
  public $Meta;
  public $WC_Order;


  /**
   * constructor
   *
   * @post_id int(order id)
   **/
  function __construct($post_id){
    // _log("ShopOrder::__construct");

    if ( is_numeric($post_id) ){
      // set woocommerce order object
      $this->WC_Order = new WC_Order($post_id);
      $this->ID = $this->Id = $post_id;

      if ( $post = get_post($post_id) ){
        // set wordpress attributes
        foreach ($post as $attribute => $value) {
          $this->$attribute = $value;
        }

        $order_items = $this->WC_Order->get_items();
        $this->Products = $this->getProducts();

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

        $this->ServicePartner = $this->getServicePartner();


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

      } // if post
    } // if is numeric
  } // construct


  /**
   * get all products which are related to the order
   *
   **/
  function getProducts(){
    $products = array();

    $order_items = $this->WC_Order->get_items();
    if ( is_array($order_items) ){
      foreach ($order_items as $key => $product) {
        $products[$key] = $product->get_data();
        $products[$key]['is_subscription'] = false;
        if ( class_exists('WC_Subscriptions_Product') ){
          $products[$key]['is_subscription'] = WC_Subscriptions_Product::is_subscription( $product['product_id'] );
        }
      } // foreach
    } // if

    return $products;
  }


  function getPostMeta(){
    return get_post_custom( $this->ID );
  }


  function getServicePartner(){
    //_log('ShopOrder::getServicePartner()');
    $number = gi($this->Meta, 'wcc-service-partner-id');
    $name       = gi($this->Meta, 'wcc-service-partner-name');
    $address    = gi($this->Meta, 'wcc-service-partner-address');
    $postcode   = gi($this->Meta, 'wcc-service-partner-postcode');
    $city       = gi($this->Meta, 'wcc-service-partner-city');
    $country    = gi($this->Meta, 'wcc-service-partner-country');

    //_log($name);
    $partner = null;
    if ( $name && $address && $postcode && $city ){
      $partner = compact('number', 'name', 'address', 'postcode', 'city', 'country');
    }

    //_log($partner);
    return $partner;
  }


  function getPackages(){
    $meta_key = 'parcel_packages';
    $packages = maybe_unserialize( gi($this->Meta, $meta_key) );

    if ( !$packages or is_array($packages) and empty($packages) ){
      //_log('get default package...');
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
        'parcel_weight'       => $this->getTotalWeight(),
        'parcel_height'       => get_option('cargonizer-parcel-height'),
        'parcel_length'       => get_option('cargonizer-parcel-length'),
        'parcel_width'        => get_option('cargonizer-parcel-width')
      );
  }


  /**
   * calculate the total weight of the order
   *
  **/
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


    if ( $weight && get_option( 'woocommerce_weight_unit', 'kg' ) == 'g' ){
      $weight /= 1000;
    }

    if ( $weight ){
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
    $carrier_id = gi($this->Meta, 'parcel_carrier_id');
    if( !$carrier_id ){
      $carrier_id = get_option( 'cargonizer-carrier-id' );
    }

    return $carrier_id;
  }


  function getCarrierProduct(){
    $carrier_product = gi($this->Meta, 'parcel_carrier_product');

    if ( !$carrier_product ){
      $carrier_product = get_option( 'cargonizer-default-carrier-product' );
    }

    return $carrier_product;
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
    // _log('ShopOrder::getParcelMessage');
    // _log($this->Products);
    if ( isset($this->Meta['parcel_message_consignee'][0]) ){
      return $this->Meta['parcel_message_consignee'][0];
    }
    else{
      $default = get_option('cargonizer-parcel-message-consignee' );
      $default = str_replace('@order_id@', $this->Id, $default);
      $default = str_replace('@products@', $this->getProductsList($recurring=false), $default);
      return $default;
    }
  }


  function getProductsList( $recurring=false ){
    $list = array();
    foreach ($this->Products as $key => $p) {
      if ( $recurring && $p['is_subscription'] ){
        $list[] = $p['name'];
      }
      elseif( !$recurring && !$p['is_subscription'] ){
        $list[] = $p['name'];
      }
    }

    $products = implode(', ', $list);
    if ( strlen($products) > 56 ){
      $products = substr($products, 0, 56);
    }

    return $products;
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
      $default = str_replace('@products@', $this->getProductsList($recurring=false), $default);
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
    $product_type = gi($this->Meta, 'parcel_carrier_product_type');

    if (!$product_type){
      $product_type = get_option( 'cargonizer-default-product-type' );
    }


    return $product_type;
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


  /**
   * adds a note to the order
   *
   * @type string
   **/
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


  /**
   * resets all cargonizer settings and consignment details
   *
  **/
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


  /**
   * checks if a order is ready to cargonize
   * conditions:
   *  - is already carognized?
   *  - has carrier id?
   *  - has carrier product?
   *  - has items/collis ?
   *  - is checkbox "send consignment now" set?
   *
   * @force bool
   **/
  function isReady( $force = false ){
    _log('ShopOrder::isReady('.$force.')');
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
        if ( gi($_POST, 'parcel_create_consignment_now') or $force ){
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


  public static function getPlaceholders(){
    return array( '@order_id@', '@shop_name@', '@parcel_tracking_url@', '@parcel_tracking_link@', '@parcel_tracking_code@', '@parcel_date@', '@service_partner@' );
  }


  /**
   * saves the details of a consignments
   *
   * @consignment array
   **/
  function saveConsignmentDetails( $consignment ){
    _log('ShopOrder::saveConsignmentDetails');
    // _log($consignment);
    if ( is_array($consignment) && isset($consignment[0]) && is_object($consignment[0]) ){
      update_post_meta( $this->ID, 'consignment_created_at', mbx($consignment[0], 'created-at')  );
      update_post_meta( $this->ID, 'consignment_tracking_code', mbx($consignment[0], 'number') );
      update_post_meta( $this->ID, 'consignment_tracking_url', mbx($consignment[0], 'tracking-url') );
      update_post_meta( $this->ID, 'consignment_pdf', mbx($consignment[0], 'consignment-pdf') );

      $bundles = mbx( $consignment[0], 'bundles/bundle', 'array' );
      if ( is_array($bundles) && isset($bundles[0]) ){
        $consignment_id = mbx($bundles[0], 'consignment-id');
        update_post_meta( $this->ID, 'consignment_id', $consignment_id );
      }

      $this->getPostMeta();
    }
  }


} // end of class
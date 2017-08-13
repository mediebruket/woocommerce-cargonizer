<?php
add_action( 'init', array('Consignment', '_registerPostType'), 10 );
// add_action( 'init', array('Consignment', '_updateNextShippingDates'), 20 ); // OBS check next shipping date first
add_action( 'pre_get_posts', array('Consignment', '_orderConsignmentsByShippingDate'), 20 );
add_filter( 'woocommerce_package_rates' , array('Consignment', '_setShippingCosts'), 10, 2 );

class Consignment{
  public $ID;
  public $Id;
  public $CarrierId;
  public $CarrierProduct;
  public $CarrierProductId;
  public $CarrierProductType;
  public $CarrierProductService;
  public $CustomerId;
  public $History;
  public $IsRecurring;
  public $IsCargonized;
  public $Items;
  public $NextShippingDate;
  public $LastShippingDate;
  public $OrderId;
  public $OrderProducts;
  public $Printer;
  public $PrintOnExport;
  public $ExportToCarrier;
  public $RecurringInterval;
  public $Subscriptions;
  public $Meta;

  function __construct( $post_id ){

    $this->Id = $this->ID = $post_id;

    // existing consignment
    if ( is_numeric($post_id) ){
      $this->init();
    }

    $this->CarrierId        = $this->getCarrierId();
    $this->CarrierProduct   = $this->getCarrierProduct();
    $this->CarrierProductServices   = $this->getCarrierProductServices();

    if ( $this->CarrierProduct ){
      $tmp = explode('|', $this->CarrierProduct );
      $this->CarrierProductId    = ( isset($tmp[0]) ) ? $tmp[0] : null;
      $this->CarrierProductType  =  ( isset($tmp[1]) ) ? $tmp[1] : null;
    }

    // _log($this);
  }

  function init(){
    $this->Meta             = $this->getPostMeta();

    $this->Items            = $this->getItems();
    $this->IsRecurring      = $this->isRecurring();
    $this->CustomerId       = $this->getCustomerId();
    $this->OrderId          = $this->getOrderId();
    $this->OrderProducts    = $this->getOrderProducts();
    $this->SubscriptionProducts    = $this->getSubscriptionProducts();
    $this->Subscriptions    = $this->getSubscriptionsByOrderId();
    $this->ReceiverEmail    = $this->getReceiverEmail();
    $this->ReceiverPhone    = $this->getReceiverPhone();
    $this->RecurringInterval = $this->getRecurringInterval();

    $this->History          = $this->getHistory();
    $this->Printer          = $this->getPrinter();
    $this->PrintOnExport    = $this->getPrintOnExport();
    $this->AutoTransfer     = $this->getAutoTransfer();

    $this->NextShippingDate  = $this->getNextShippingDate();
    $this->LastShippingDate  = $this->getLastShippingDate();
  }


  function update( $meta_key, $meta_value ){
    update_post_meta( $this->Id, $meta_key, $meta_value );
  }


  function set( $attr, $value ){
    $this->$attr = $value;
  }


  function setMeta( $attr, $value ){
    $this->Meta[$attr] = $value;
  }


  function getPostMeta(){
    return get_post_custom( $this->ID );
  }


  function getOrderId(){
    if ( $this->IsRecurring ){
      return gi($this->Meta, 'recurring_consignment_order_id');
    }
    else{
      return gi($this->Meta, 'consignment_order_id');
    }
  }


  function getOrderProducts(){
    return maybe_unserialize( gi($this->Meta, 'consignment_order_products') );
  }


  function getCustomerId(){
    return gi($this->Meta, 'customer_id');
  }


  function isRecurring(){
    return gi($this->Meta, 'consignment_is_recurring');
  }


  function getReceiverEmail(){
    return gi($this->Meta, 'email');
  }


  function getReceiverPhone(){
    return gi($this->Meta, 'phone');
  }


  function getPrinter(){
    return gi($this->Meta, 'parcel_printer');
  }


  function getPrintOnExport(){
    return gi($this->Meta, 'consignment_print_on_export');
  }


  function getAutoTransfer(){
    //_log( get_post_meta( $this->ID, 'consignment_auto_transfer', true ) );
    return gi($this->Meta, 'consignment_auto_transfer');
  }


  function getItems(){
    return acf_getField('consignment_items', $this->ID);
  }


  function getRecurringInterval(){
    return gi($this->Meta, 'recurring_consignment_interval');
  }


  function getNextShippingDate(){
    return gi($this->Meta, 'consignment_next_shipping_date');
  }


  function getLastShippingDate(){
    $date = null;
    // _log('$this->History');
    // _log($this->History[0]['created_at']);
    if ( is_array($this->History) && isset($this->History[0]) && isset($this->History[0]['created_at']) ){
      $date = $this->History[0]['created_at'];
    }

    return $date;
  }


  function getCarrierId(){
    $carrier_id = gi($this->Meta, 'consignment_carrier_id');

    if ( !$carrier_id  ){
      $carrier_id = get_option('cargonizer-carrier-id' );
    }


    return $carrier_id;
  }


  function getCarrierProduct(){
    $consignment_product = gi($this->Meta, 'consignment_product');

    // _log('$consignment_product');
    // _log($consignment_product);
    if ( !$consignment_product ){
      $consignment_product = get_option('cargonizer-carrier-products');

      if ( isset($consignment_product[0]) ){
        $consignment_product = $consignment_product[0];
      }

      // _log($consignment_product);
    }

    return $consignment_product;
  }


  function getCarrierProductServices(){
    return acf_getField('consignment_services', $this->Id );
  }


  public static function createOrUpdate( $Order, $recurring=false ){
    _log('Consignment::createOrUpdate('.$Order->ID.')');
    $post_id = null;
    //_log($Order);
    $args = array(
      'post_author' => get_current_user_id(),
      'post_title' =>  sprintf( 'Order #%s %s', $Order->ID, ( ($recurring) ? '| recurring ' : null) ),
      'post_status' => 'publish',
      'post_type' => 'consignment',
      'post_parent' => 0,
    );

    if ( $cid = self::getConsignmentIdByOrderId( $Order->ID, $recurring ) ){
      _log('update existing consignment: '.$cid);
      $args['ID'] = $cid;
    }
    else{
      _log('create new consignment');
    }

    if ( $post_id = wp_insert_post( $args ) ){
      _log('consignment: '.$post_id);

      // _log('Order');
      // _log($Order);

      $meta_order_key = 'consignment_order_id';
      if ( $recurring ){
        $meta_order_key = 'recurring_consignment_order_id';
      }
      update_post_meta( $post_id, $meta_order_key, $Order->ID );
      update_post_meta( $post_id, 'consignment_is_recurring', $recurring );
      update_post_meta( $post_id, 'parcel_printer', $Order->Printer );
      update_post_meta( $post_id, 'consignment_print_on_export', $Order->PrintOnExport );
      update_post_meta( $post_id, 'consignment_auto_transfer', $Order->AutoTransfer );

      // user id
      update_post_meta( $post_id, 'customer_id', gi($Order->Meta, '_customer_user' ) );

      // products
      update_post_meta( $post_id, 'consignment_order_products', $Order->Products );


      if ( $recurring ){
        update_post_meta( $post_id, 'recurring_consignment_interval', $Order->RecurringInterval );
        update_post_meta( $post_id, 'consignment_carrier_id', $Order->RecurringCarrierId );
        update_post_meta( $post_id, 'consignment_start_date', $Order->RecurringStartDate );
        update_post_meta( $post_id, 'consignment_product', $Order->RecurringConsignmentType );
        update_post_meta( $post_id, 'consignment_services', $Order->RecurringConsignmentServices );
        update_post_meta( $post_id, 'consignment_message', $Order->RecurringConsignmentMessage );
        acf_updateField('consignment_items', $Order->RecurringConsignmentItems, $post_id);
      }
      else{
        _log('single consignment');
        acf_updateField('consignment_items', $Order->Items, $post_id);
        update_post_meta( $post_id, 'consignment_carrier_id', $Order->TransportAgreementId );
        update_post_meta( $post_id, 'consignment_product', $Order->ParcelType );
        update_post_meta( $post_id, 'consignment_services', $Order->ParcelServices );
        update_post_meta( $post_id, 'consignment_message', $Order->ParcelMessage );
        update_post_meta( $post_id, 'consignment_next_shipping_date', $Order->ShippingDate );
      }

      // copy meta values
      update_post_meta( $post_id, '_billing_first_name',  gi( $Order->Meta, '_billing_first_name' ) );
      update_post_meta( $post_id, '_billing_last_name',   gi( $Order->Meta, '_billing_last_name' ) );
      update_post_meta( $post_id, '_billing_country',     gi( $Order->Meta, '_billing_country' ) );
      update_post_meta( $post_id, '_billing_postcode',    gi( $Order->Meta, '_billing_postcode' ) );
      update_post_meta( $post_id, '_billing_city',        gi( $Order->Meta, '_billing_city' ) );
      update_post_meta( $post_id, '_billing_address_1',   gi( $Order->Meta, '_billing_address_1' ) );
      update_post_meta( $post_id, '_billing_address_2',   gi( $Order->Meta, '_billing_address_2' ) );


      // firstname
      $sfn = gi( $Order->Meta, '_shipping_first_name' );
      if ( !$sfn ){
        $sfn =  gi( $Order->Meta, '_billing_first_name' );
      }
      update_post_meta( $post_id, '_shipping_first_name', $sfn );

      // lastname
      $sln = gi( $Order->Meta, '_shipping_last_name' );
      if ( !$sln ){
        $sln = gi( $Order->Meta, '_billing_last_name' );
      }
      update_post_meta( $post_id, '_shipping_last_name', $sln );

      // country
      $sc = gi( $Order->Meta, '_shipping_country' );
      if ( !$sc ){
        $sc = gi( $Order->Meta, '_billing_city' ) ;
      }
      update_post_meta( $post_id, '_shipping_country', $sc );

      // postcode
      $spc = gi( $Order->Meta, '_shipping_postcode' );
      if ( !$spc  ){
        $spc = gi( $Order->Meta, '_billing_postcode' );
      }
      update_post_meta( $post_id, '_shipping_postcode', $spc );

      // city
      $sc = gi( $Order->Meta, '_shipping_city' );
      if ( !$sc ){
        $sc = gi( $Order->Meta, '_billing_city' );
      }
      update_post_meta( $post_id, '_shipping_city', $sc );


      // address 1
      $sa1 = gi( $Order->Meta, '_shipping_address_1' );
      if ( !$sa1 ){
        $sa1 = gi( $Order->Meta, '_billing_address_1' );
      }
      update_post_meta( $post_id, '_shipping_address_1', $sa1 );

      // address 2
      $sa2 = gi( $Order->Meta, '_shipping_address_2' );
      if ( !$sa2 ){
        $sa2 =  gi( $Order->Meta, '_billing_address_2' );
      }
      update_post_meta( $post_id, '_shipping_address_2', $sa2 );


      $address = $Order->WC_Order->get_address();
      update_post_meta( $post_id, 'email', gi( $address, 'email' ) );
      update_post_meta( $post_id, 'phone', gi( $address, 'phone' ) );

      if ( !isset($args['ID']) ){
        self::addNote($Order->ID, $post_id);
      }
    }

    return $post_id;
  }


  function getHistory(){
    // _log('Consignment::getHistory()');
    $history = array();
    if ( isset($this->Meta['consignment_history']) ){
      $cm = $this->Meta['consignment_history'];
      // _log($cm);
      if ( is_array($cm) ){
        foreach ($cm as $key => $row) {
          $history[] = maybe_unserialize($row );
        }
      }
    }

    return array_reverse( $history );
  }


  function hasSubscriptionWarning(){
    // _log('Consignment::hasSubscriptionWarning()');
    $warning = false;

    if ( function_exists('wcs_user_has_subscription') ){
      if ( $this->IsRecurring && $this->CustomerId ){

        if ( is_array($this->SubscriptionProducts) && !empty($this->SubscriptionProducts) ){
          // _log('has pids');
          // _log($this->SubscriptionProducts);
          foreach ( $this->SubscriptionProducts as $key => $product_id ) {
            // if ( !wcs_user_has_subscription( $this->CustomerId, $product_id  ) ){
            //   $warning = true;
            //   break;
            // }
            if (
              // if has subscription product and subscription is not active
              $this->isSubscriptionProduct($product_id) && !$this->isSubscriptionProductActive($product_id)
              or
              $this->Subscriptions->post_status != 'wc-active' // if subscription has an end date
            )
            {
              $warning = true;
              break;
            }
          }
        }
      }
    }


    return $warning;
  }


  function isSubscriptionProductActive( $product_id ){
    _log('Consignment::isSubscriptionProductActive('.$product_id.')');
    $is_active = true;
    if ( function_exists('wcs_user_has_subscription') ){
      if ( !wcs_user_has_subscription( $this->CustomerId, $product_id ) ){
        _log('no subscription: '.$product_id);
        $is_active = false;
      }
      else {
        if ( $this->OrderId ){
        }
      }
    }


    return $is_active;
  }


  function getSubscriptionsByOrderId( $order_id =null ){
    if ( !$order_id ){
      $order_id = $this->OrderId;
    }
    global $wpdb;
    $sql = "SELECT * FROM %s WHERE post_type ='shop_subscription' AND post_parent = '%s' ";
    $sql = sprintf($sql, $wpdb->posts, $order_id);

    return $wpdb->get_row($sql);
  }


  function isSubscriptionProduct( $product_id ){
    if ( is_numeric(array_search($product_id, $this->SubscriptionProducts)) ){
      return true;
    }
    else{
      return false;
    }
  }


  function getSubscriptionProducts(){
    $product_ids = array();

    if ( is_array($this->OrderProducts) ){
      foreach ($this->OrderProducts as $key => $p) {
        if ( isset($p['is_subscription']) && $p['is_subscription'] ){
          $product_ids[] = $p['product_id'];
        }
      }
    }

    return $product_ids;
  }


  function updateHistory( $consignment ){
    _log('Consignment::updateHistory()');
    // _log($consignment);

    $new_entry = array();

    if ( $created_at = $consignment['created-at']['$'] ){
      $new_entry['created_at'] = $created_at;
    }

    if ( $number = $consignment['number'] ){
      $new_entry['consignment_tracking_code'] = $number;
    }


    if ( $tracking_url = $consignment['tracking-url'] ){
      $new_entry['consignment_tracking_url'] = $tracking_url;
    }


    if( $pdf = $consignment['consignment-pdf'] ){
      $new_entry['consignment_pdf'] = $pdf;
    }

    if ( $consignment['bundles']['bundle'] ){
      $bundle = $consignment['bundles']['bundle'];
      $new_entry['consignment_id'] = null;
      if ( isset($bundle[0]) ){
        $new_entry['consignment_id'] = $bundle[0]['consignment-id']['$'];
      }
      else {
       $new_entry['consignment_id'] = $bundle['consignment-id']['$'];
      }
    }

    if( !empty($new_entry) ){
      add_post_meta( $this->Id, 'consignment_history', $new_entry, false );
      _log('history updated');
      return $new_entry;
    }
    else{
      return false;
    }
  }


  function prepareExport(){
    _log('Consignment::prepareExport()');

    //_log($this->Meta);
    // _log('prepareExport');
    // _log($this->Items);
    // http://www.logistra.no/api-documentation/12-utviklerinformasjon/16-api-consignments.html
    $export['consignments']['consignment']['_attr'] =
      array(
        'transport_agreement' => $this->CarrierId,
        'estimate' => "true",
        'print' => ( $this->PrintOnExport ) ? 'true' : 'false'
        );

    $export['consignments']['consignment']['values'] = array(
      0 => array(
        'value' => array(
          '_attr' =>
            array(
              'name' => 'order',
              'value' => $this->ID
            )
          )
        )
    );


      
    $export['consignments']['consignment']['transfer'] = ( $this->AutoTransfer ) ? 'true' : 'false';
        

    // $export['consignments']['consignment']['booking_request'] = 'false';
    $export['consignments']['consignment']['product'] = $this->CarrierProductId;

    //
    // ---------------- addresses ----------------
    //

     // _log($address);
    // customer address
    $export['consignments']['consignment']['parts']['consignee']['name']      = gi( $this->Meta, '_shipping_first_name' )." ".gi( $this->Meta,'_shipping_last_name' );
    $export['consignments']['consignment']['parts']['consignee']['country']   = ( gi( $this->Meta, '_shipping_country' ) ) ? gi( $this->Meta, '_shipping_country' ) : 'NO';
    $export['consignments']['consignment']['parts']['consignee']['postcode']  = gi( $this->Meta, '_shipping_postcode' );
    $export['consignments']['consignment']['parts']['consignee']['city']      = gi( $this->Meta, '_shipping_city' );
    $export['consignments']['consignment']['parts']['consignee']['address1']  = gi( $this->Meta, '_shipping_address_1' );
    $export['consignments']['consignment']['parts']['consignee']['address2']  = gi( $this->Meta, '_shipping_address_2' );


    if ( ! trim($export['consignments']['consignment']['parts']['consignee']['name']) ){
      // customer address
      $export['consignments']['consignment']['parts']['consignee']['name'] = gi( $this->Meta, '_billing_first_name' )." ".gi( $this->Meta,'_billing_last_name' );
    }

    if ( !$export['consignments']['consignment']['parts']['consignee']['country'] ) {
      $export['consignments']['consignment']['parts']['consignee']['country'] = ( gi( $this->Meta, '_billing_country' ) ) ? gi( $this->Meta, '_billing_country' ) : 'NO';
    }

    if ( !$export['consignments']['consignment']['parts']['consignee']['postcode'] ){
      $export['consignments']['consignment']['parts']['consignee']['postcode'] = gi( $this->Meta, '_billing_postcode' );
    }

    if ( !$export['consignments']['consignment']['parts']['consignee']['city'] ){
      $export['consignments']['consignment']['parts']['consignee']['city']  = gi( $this->Meta, '_billing_city' );
    }

    if ( !$export['consignments']['consignment']['parts']['consignee']['address1'] ){
      $export['consignments']['consignment']['parts']['consignee']['address1']= gi( $this->Meta, '_billing_address_1' );
    }

    if ( !$export['consignments']['consignment']['parts']['consignee']['address2'] ){
      $export['consignments']['consignment']['parts']['consignee']['address2'] = gi( $this->Meta, '_billing_address_2' );
    }


    $export['consignments']['consignment']['parts']['consignee']['email']     = gi( $this->Meta, 'email' );
    $export['consignments']['consignment']['parts']['consignee']['mobile']    = gi( $this->Meta, 'phone' );


    // return address
    $export['consignments']['consignment']['parts']['return_address']['name']     = get_option('cargonizer-return-address-name');
    $export['consignments']['consignment']['parts']['return_address']['country']  = get_option('cargonizer-return-address-country');
    $export['consignments']['consignment']['parts']['return_address']['postcode'] = get_option('cargonizer-return-address-postcode');
    $export['consignments']['consignment']['parts']['return_address']['city']     = get_option('cargonizer-return-address-city');
    $export['consignments']['consignment']['parts']['return_address']['address1'] = get_option('cargonizer-return-address-address1');


    // set consignee
    // $export['consignments']['consignment']['consignee'] =  $export['consignments']['consignment']['parts']['consignee'];


    // ---------------- items ----------------
    //
    // <item type="PK" amount="1" weight="22" volume="122" description="Something else"/>

    $export['consignments']['consignment']['items'] = array(); // packages
    foreach ($this->Items as $key => $item) {
      $array = array('item' => null );

      $parcel_weight = null;
      if ( $pw = gi( $item, 'parcel_weight' ) ){
        $parcel_weight =  str_replace(',', '.', $pw);
      }

      $item_attributes =
        array(
          'type'        => ( $parcel_type = gi( $item, 'parcel_package_type' ) ) ? $parcel_type : $this->CarrierProductType,
          // 'type'        => 'package',
          'amount'      => gi( $item, 'parcel_amount' ),
          'weight'      => $parcel_weight,
          'length'      => gi( $item, 'parcel_length' ),
          'width'       => gi( $item, 'parcel_width' ),
          'height'      => gi( $item, 'parcel_height' ),
          'description' => gi($item, 'parcel_description' ),
        );


      if ( $volume = gi($item, 'parcel_volume' ) ){
        $item_attributes['volume'] = $volume;
      }
      else{
        $item_attributes['volume'] = $item_attributes['length']*$item_attributes['width']*$item_attributes['height']/1000;
      }


      $array['item']['_attr'] = $item_attributes;
      $export['consignments']['consignment']['items'][]= $array;
    }


    if ( $parcel_services = gi( $this->Meta, 'consignment_services') ){
      $services = maybe_unserialize($parcel_services);


      if ( is_array($services) && !empty($services) ){
        foreach ($services as $key => $identifier) {
          // <service id="bring_e_varsle_for_utlevering"></service>
          if ( trim($identifier) ){
            $array = array('service' => null );
            $array['service']['_attr'] = array(
              'id' => $identifier
              );
            $export['consignments']['consignment']['services'][] = $array; // packages
          }
        }
      }
    }

    $export['consignments']['consignment']['messages']['consignor'] = 'messages-consignor';
    $export['consignments']['consignment']['messages']['consignee'] = gi( $this->Meta, 'consignment_message');

    $export['consignments']['references']['consignor'] = $this->OrderId;
    $export['consignments']['references']['consignee'] = $this->CustomerId;

    _log($export);
    return $export;
  }


  public static function getPlaceholders(){
    return array( '@order_id@', '@shop_name@', '@parcel_tracking_url@', '@parcel_tracking_link@', '@parcel_tracking_code@', '@parcel_date@' );
  }


  function notifyCustomer( $history_entry ){
    _log('Consignment::notifyCustomer()');
    // update meta fields
    //$this->Meta = get_post_custom($this->ID );

    if ( $email = $this->ReceiverEmail ){

      $tmp_placeholders = self::getPlaceholders();
      $placeholders = array();
      _log('generate placeholders');
      foreach ($tmp_placeholders as $pi => $ph) {

        $placeholders[$ph] = null;

        if ( $ph == '@order_id@'){
          $placeholders[$ph] = $this->ID;
        }
        elseif ( $ph == '@shop_name@'){
          $placeholders[$ph] = get_bloginfo('name' );
        }
        elseif ( $ph == '@parcel_tracking_url@'){
          $placeholders[$ph] = gi($history_entry, 'consignment_tracking_url');
        }
        elseif ( $ph == '@parcel_tracking_link@'){
          $placeholders[$ph] = sprintf('<a href="%s">%s</a>', gi($history_entry, 'consignment_tracking_url'), gi($history_entry, 'consignment_tracking_code') );
        }
        elseif ( $ph == '@parcel_tracking_code@'){
          $placeholders[$ph] = gi($history_entry, 'consignment_tracking_code');
        }
        elseif ( $ph == '@parcel_date@'){
          $placeholders[$ph] = date( get_option( 'date_format' ), strtotime(gi($history_entry, 'created_at')) );
        }
      }

      // _log($placeholders);

      $notification = array(
        'subject' => get_option('cargonizer-customer-notification-subject'),
        'message' => nl2br(htmlentities( get_option('cargonizer-customer-notification-message') , ENT_QUOTES, "UTF-8")),
        );

      foreach ($notification as $key => $string) {
        foreach ( $placeholders as $ph => $ph_value ) {
          $string = str_replace($ph, $ph_value, $string);
        }

        $notification[$key] = $string;
      }

      wp_mail( $email, $notification['subject'], $notification['message'] );
      _log('notification sent to: '.$email);
      _log('$notification');
      _log($notification);

    }
    else{
      _log('no customer mail');
    }

  }

  public static function addNote( $order_id, $consignment_id ){
    _log('Consignment::addNote('.$order_id.')');

    $data = array(
      'comment_post_ID'       => $order_id,
      'comment_author'        => 'WooCommerce Cargonizer',
      'comment_author_email'  => get_option('admin_email' ),
      'comment_content'       => sprintf( __('Consignment created: <a href="%s">edit</a>', 'wc-cargonizer'), get_edit_post_link($consignment_id) ),
      'comment_agent'         => 'WooCommerce Cargonizer',
      'comment_type'          => 'order_note',
      'comment_parent'        => 0,
      'user_id'               => 1,
      'comment_author_IP'     => 'null',
      'comment_date'          => current_time('mysql'),
      'comment_approved'      => 1,
    );


    if ( wp_insert_comment($data) ){
      _log('new note added');
    }
    else{
      _log('error: wp_insert_comment');
      _log($data);
    }
  }


  public static function getConsignmentIdByOrderId( $order_id, $recurring=false ){
    _log('Consignment::getConsignmentIdByOrderId('.$order_id.')');
    global $wpdb;
    $meta_key = 'consignment_order_id';
    if ( $recurring ){
      $meta_key = 'recurring_consignment_order_id';
    }
    $sql = sprintf("SELECT post_id FROM %s WHERE meta_key = '%s' and meta_value= '%s';", $wpdb->postmeta, $meta_key, $order_id );
    // _log($sql);
    return $wpdb->get_var($sql);
  }


  public static function getAllRecurringConsignments(){
    global $wpdb;
    $sql =  "SELECT p.ID FROM  %s p, %s pm WHERE p.ID = pm.post_id AND p.post_type = 'consignment' AND pm.meta_key = 'consignment_is_recurring' and pm.meta_value = '1'";
    $sql = sprintf($sql, $wpdb->posts, $wpdb->postmeta );

    return $wpdb->get_col($sql);
  }


  public static function _updateNextShippingDates(){
    $post_ids = self::getAllRecurringConsignments();
    if ( is_array($post_ids) ){
      foreach ($post_ids as $key => $post_id) {
        if ( $interval = get_post_meta( $post_id, 'recurring_consignment_interval', true ) ){
          $next_calculated_shipping_date = self::calcNextShippingDate( $interval );
          $current_next_shipping_date = get_post_meta( $post_id, 'consignment_next_shipping_date', true );
          $start_date = get_post_meta( $post_id, 'consignment_start_date', true );
          $next_shipping_date = null;
          if ( strtotime($next_calculated_shipping_date) > strtotime($current_next_shipping_date) ){
            _log('calculated date is bigger than current date');
            $next_shipping_date = $next_calculated_shipping_date;
          }
          if ( strtotime($start_date) > strtotime($next_calculated_shipping_date) ){
            _log('custom start date is bigger than next calculated shipping date');
            $next_shipping_date = $start_date;
          }
          if ( $next_shipping_date ){
            _log('update next shipping date');
            update_post_meta( $post_id, 'consignment_next_shipping_date', $next_shipping_date );
            _log('next shipping date updated ('.$post_id.'): ' .$next_shipping_date );
          }
          //else{
           // _log('no update');
          //}
        }
      }
    }
  }


  function setNextShippingDate( $auto_inc=false ){
    _log('Consignment::setNextShippingDate()');
    // has Subscription
    // check Subs
    if ( $this->IsRecurring && $this->RecurringInterval ){
      _log('is recurring');

      $nsd = self::calcNextShippingDate( $this->RecurringInterval, $auto_inc );
      $active = true;
      if ( $this->hasSubscriptionWarning() ){
        _log('has subscription warning');
        $end_date = get_post_meta( $this->Subscription->ID, '_schedule_end', true );

        _log('end date: '.$end_date);
        if ( $end_date < $nsd ){
          _log('end date is smaller than next shipping date');
          $active = false;
        }
      }
      else{
        _log('no warning');
      }

      if ( $nsd && $active ){
        $this->update( 'consignment_next_shipping_date', $nsd );
        _log('next shipping date updated: '.$nsd);
        $this->set('NextShippingDate', $nsd);
      }
    }
  }


  public static function calcNextShippingDate( $interval, $auto_inc=false ){

    if ( is_numeric($interval) && $interval > 0 ){
      $month = date('m');
      $year = date('Y');

      if ( date('d') > $interval or $auto_inc ){
        if ( $month != 12 ){
          $month += 1;
        }
        else{
          $month = 1;
          $year += 1;
        }
      }

      return $year.str_pad($month, 2, "0", STR_PAD_LEFT).str_pad($interval, 2, "0", STR_PAD_LEFT);
    }
    else{
      return null;
    }

  }


  public static function _orderConsignmentsByShippingDate( $query ){
    if ( gi($_GET, 'post_type') == 'consignment' && gi($_GET, 'orderby') == 'consignment-next-shipping-date'){
      $order = 'desc';
      if ( $o = gi($_GET, 'order') ){
        $order = $o;
        $query->set( 'meta_key', 'consignment_next_shipping_date' );
        $query->set( 'order', $order );
        $query->set( 'orderby', 'meta_value_num' );

      }
    }

    return $query;
  }


  public static function getJsonObject( $post_id, $echo = true ){

    $Consignment = new Consignment($post_id);

    $html = sprintf( '<script>var Consignment=%s;</script>', json_encode($Consignment) );

    if ( $echo ){
      echo $html;
    }
    else{
      return $html;
    }
  }


  public static function _setShippingCosts($rates, $package){
    //_log('Consignment::setShippingCosts()');
    // _log($rates);
    // _log($package);

    $esc = get_option('cargonizer-estimate-shipping-costs');

    if ( $esc && isset($package['contents']) && !empty($package['contents']) ){
      $products = $package['contents'];
      // _log( 'has: ' .count($products). ' items' );
      $parcel = array(
          'width' => 0,
          'height' => 0,
          'length' => 0,
          'weight' => 0,
          'volume' => 0
        );

      foreach ($products as $key => $p) {
        $qty = $p['quantity'];
        // _log('$qty: '.$qty);

        $WC_P = new WC_Product( $p['product_id']);
        $width = $WC_P->get_width();
        $height = $WC_P->get_height();
        $length = $WC_P->get_length();
        $weight = $WC_P->get_weight();


        $volume = 0;

        if ( $width && $height && $length ){
          $volume = $width * $height * $length / 1000;
          // _log('volume: '.$volume);
        }

        $parcel['volume'] += $volume * $qty;
        $parcel['weight'] += ($weight * $qty);
      }


      $Consignment = new Consignment( null );

      if ( isset($package['destination']) && !empty($package['destination']) ){
        // _log('set destination');
        $Consignment->setMeta( '_shipping_first_name', 'Ola' );
        $Consignment->setMeta( '_shipping_last_name', 'Nordmann' );
        $Consignment->setMeta( '_shipping_postcode', $package['destination']['postcode'] );
        $Consignment->setMeta( '_shipping_country', $package['destination']['country'] );
        $Consignment->setMeta( '_shipping_city', $package['destination']['country'] );
        $Consignment->setMeta( '_shipping_address_1', $package['destination']['address'] );
        $Consignment->setMeta( '_shipping_address_2', $package['destination']['address_2'] );

        $Consignment->Items[] =
            array(
              'parcel_package_type' => $Consignment->CarrierProductType,
              'parcel_amount' => 1,
              'parcel_length' => null,
              'parcel_width'  => null,
              'parcel_height' => null,
              'parcel_description' => 'cost estimation',
              'parcel_weight' => $parcel['weight'],
              'parcel_volume' => $parcel['volume'],
            );

        $CargonizeXml = new CargonizeXml( $Consignment->prepareExport() );
        $CargonizerApi = new CargonizerApi();
        $result = $CargonizerApi->estimateCosts( $CargonizeXml->Xml );

        // _log($result['consignment-cost']);
        if ( isset($result['consignment-cost']['estimated-cost']) && is_array($result['consignment-cost']['estimated-cost']) ){
          foreach ($rates as $key => $value) {
            if ( is_numeric( strpos($key, 'flat_rate') ) ) {
              // _log($rates);
              // _log($key);
              $rates[$key]->cost = $result['consignment-cost']['estimated-cost']['$'];
              $rates[$key]->taxes = array( 1 => $result['consignment-cost']['estimated-cost']['$'] *0.25 );
            } // if
          } // foreach
        }// if
      }
      else{
        _log('no destination ');
        _log($package);
      }
    }
    // _log($rates);
    return $rates;
  }


  public static function _registerPostType(){

    $single = __('consignment' );
    $multi  = __('consignments' );

    $labels = array(
        'name'              => ucfirst($single),
        'singular_name'     => ucfirst($single),
        'add_new'           => __('Add ', 'wc-cargonizer'),
        'all_items'         => 'all '.$multi ,
        'add_new_item'      => 'add '.$single,
        'edit_item'         => 'edit '.$single,
        'new_item'            => 'add '.$single,
        'view_item'           => 'show '.$single,
        'search_items'        => 'search '.$single,
        'not_found'           => 'No '.$single.' found',
        'not_found_in_trash'  => 'No '.$single.' in trash',
        'parent_item_colon'   => 'Parent Post:',
        'menu_name'           => ucfirst($multi)
    );

    $args = array(
      'labels'               => $labels,
      'description'          => "",
      'public'               => true,
      'exclude_from_search'  => true,
      'publicly_queryable'   => true,
      'show_ui'              => true,
      'show_in_nav_menus'    => false,
      'show_in_menu'         => false,
      'show_in_admin_bar'    => false,
      'menu_position'        => 42,
      'capability_type'      => 'post',
      'hierarchical'         => false,
      'supports'             => array('title' ),
      'has_archive'          => true,
      'rewrite'              => false,
      'query_var'            => true,
      'can_export'           => true,
      'taxonomies'           => array()
    );
    register_post_type( 'consignment' ,$args) ;

  }

}
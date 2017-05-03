<?php

class Parcel{
  public $ConsignmentId;
  public $ID;
  public $Items;
  public $IsCargonized;
  public $Meta;
  public $Printer;
  public $ParcelType;
  public $TrackingProvider;
  public $TransportAgreements;
  public $TransportAgreementId;
  public $TransportAgreementProduct;
  public $TransportAgreementProductType;

  public $WC_Order;



  function __construct($post_id){
    if ( is_numeric($post_id) ){
      // set woocommerce order object
      $this->WC_Order = new WC_Order($post_id);

      if ( $post = get_post($post_id) ){
        // set wordpress attributes
        foreach ($post as $attribute => $value) {
          $this->$attribute = $value;
        }

        $this->Meta           = $this->getPostMeta();
        $this->IsCargonized   = $this->isCargonized();
        $this->Printer        = $this->getPrinter();
        $this->ParcelType     = $this->getParcelType();
        $this->ParcelServices = $this->getParcelServices();

        $this->ConsignmentId = $this->getConsignmentId();
        $this->getTransportAgreementSettings();
        $this->Items = $this->getItems();
        //$this->TrackingProvider = $this->getTrackingProvider();
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

  function isReady( $force = false ){
    _log('Parcel::isReady');
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
      }
    }

    _log($is_ready);

    return $is_ready;
  }


  function getTransportAgreementSettings(){
    //_log('Parcel::getTransportAgreementSettings');
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
    return acf_getField('items', $this->ID);
  }


  function saveTransportAgreements(){
    // _log('saveTransportAgreements');
    // _log($this->TransportAgreements);
    set_transient( 'transport_agreements', $this->TransportAgreements, 1*60*60 );
  }


  public static function _getTransportAgreements(){
    return get_transient('transport_agreements');
  }

  // function getTransportAgreements($force_update=false){
  //   _log('getTransportAgreements');
  //   $ta = get_transient('transport_agreements');

  //   if ( $ta && !$force_update ){
  //     _log('transient');
  //     $this->TransportAgreements = $ta;
  //   }
  //   else{
  //     _log('no transient');
  //     $Api = new CargonizerApi(true);
  //     _log($Api->TransportAgreements);
  //     $this->setTransportAgreements( $Api->TransportAgreements['transport-agreements']['transport-agreement'] );
  //   }
  // }


  function prepareExport(){
    _log('Parcel::prepareExport');

    // _log('prepareExport');
    // _log($this->Items);
    // http://www.logistra.no/api-documentation/12-utviklerinformasjon/16-api-consignments.html
    $export['consignments']['consignment']['_attr'] =
      array(
        'transport_agreement' => $this->TransportAgreementId,
        'estimate' => "true",
        'print' => ( get_option('cargonizer-print-on-export' ) == 'on' ) ? true : false
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
    $export['consignments']['consignment']['transfer'] = 'true';
    $export['consignments']['consignment']['booking_request'] = 'true';
    $export['consignments']['consignment']['product'] = $this->TransportAgreementProduct;

    //
    // ---------------- addresses ----------------
    //

    $address = $this->WC_Order->get_address();
     // _log($address);
    // customer address
    $export['consignments']['consignment']['parts']['consignee']['name']      = gi( $this->Meta, '_shipping_first_name' )." ".gi( $this->Meta,'_shipping_last_name' ); // customer address
    $export['consignments']['consignment']['parts']['consignee']['country']   = ( gi( $this->Meta, '_shipping_country' ) ) ? gi( $this->Meta, '_shipping_country' ) : 'NO';
    $export['consignments']['consignment']['parts']['consignee']['postcode']  = gi( $this->Meta, '_shipping_postcode' );
    $export['consignments']['consignment']['parts']['consignee']['city']      = gi( $this->Meta, '_shipping_city' );
    $export['consignments']['consignment']['parts']['consignee']['address1']  = gi( $this->Meta, '_shipping_address_1' );
    $export['consignments']['consignment']['parts']['consignee']['address2']  = gi( $this->Meta, '_shipping_address_2' );
    $export['consignments']['consignment']['parts']['consignee']['email']     = gi( $address, 'email' );
    $export['consignments']['consignment']['parts']['consignee']['mobile']    = gi( $address, 'phone' );


    // return address
    $export['consignments']['consignment']['parts']['return_address']['name']     = get_option('cargonizer-return-address-name');
    $export['consignments']['consignment']['parts']['return_address']['country']  = get_option('cargonizer-return-address-country');
    $export['consignments']['consignment']['parts']['return_address']['postcode'] = get_option('cargonizer-return-address-postcode');
    $export['consignments']['consignment']['parts']['return_address']['city']     = get_option('cargonizer-return-address-city');
    $export['consignments']['consignment']['parts']['return_address']['address1'] = get_option('cargonizer-return-address-address1');


    // set consignee
    $export['consignments']['consignment']['consignee'] =  $export['consignments']['consignment']['parts']['consignee'];


    // ---------------- items ----------------
    //
    // <item type="PK" amount="1" weight="22" volume="122" description="Something else"/>

    $export['consignments']['consignment']['items'] = array(); // packages
    foreach ($this->Items as $key => $item) {
      $array = array('item' => null );

      $parcel_weight = null;
      if ( $pw = gi( $item, 'parcel_weight' ) ){
        $parcel_weight = $pw;
      }

      $item_attributes =
        array(
          'type'        => ( $parcel_type = gi( $item, 'parcel_package_type' ) ) ? $parcel_type : $this->TransportAgreementProductType,
          // 'type'        => 'package',
          'amount'      => gi( $item, 'parcel_amount' ),
          'weight'      => $parcel_weight,
          'length'      => gi( $item, 'parcel_length' ),
          'width'       => gi( $item, 'parcel_width' ),
          'height'      => gi( $item, 'parcel_height' ),
          'description' => gi($item, 'parcel_description' ),
        );

      $item_attributes['volume'] = $item_attributes['length']*$item_attributes['width']*$item_attributes['height'];

      $array['item']['_attr'] = $item_attributes;


      $export['consignments']['consignment']['items'][]= $array;
    }


    if ( $parcel_services = gi( $this->Meta, 'parcel_services') ){
      $services = maybe_unserialize($parcel_services);

      if ( is_array($services) && !empty($services) ){
        foreach ($services as $key => $identifier) {
          // <service id="bring_e_varsle_for_utlevering"></service>
          $array = array('service' => null );
          $array['service']['_attr'] = array(
            'id' => $identifier
            );
          $export['consignments']['consignment']['services'][] = $array; // packages

        }
      }
    }


    // $export['consignments']['references']['consignor'] = null;
    // $export['consignments']['references']['consignee'] = null;

    $export['consignments']['consignment']['messages']['consignor'] = $this->post_excerpt;
    $export['consignments']['consignment']['messages']['consignee'] = gi( $this->Meta, 'message_consignee');

    // _log($export);
    return $export;
  }


  public static function getPlaceholders(){
    return array( '@order_id@', '@shop_name@', '@parcel_tracking_url@', '@parcel_tracking_link@', '@parcel_tracking_code@', '@parcel_date@' );
  }


  function saveConsignment( $consignment ){
    _log('saveConsignment');
    _log($consignment);
    acf_updateField('consignment_created_at', $consignment['created-at']['$'], $this->ID);
    acf_updateField('consignment_id', $consignment['bundles']['bundle'][0]['consignment-id']['$'], $this->ID);
    acf_updateField('consignment_tracking_code', $consignment['number'], $this->ID);
    acf_updateField('consignment_tracking_url', $consignment['tracking-url'], $this->ID);
    acf_updateField('consignment_pdf', $consignment['consignment-pdf'], $this->ID);
    //acf_updateField('consignment_estimated_costs', '1234', $this->ID);
    $this->getPostMeta();
  }


  function notiyCustomer(){
    _log('Parcel::notiyCustomer()');
    // update meta fields
    //$this->Meta = get_post_custom($this->ID );
    $address = $this->WC_Order->get_address();
    if ( $email = gi( $address, 'email' ) ){

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
          $placeholders[$ph] = gi($this->Meta, 'consignment_tracking_url');
        }
        elseif ( $ph == '@parcel_tracking_link@'){
          $placeholders[$ph] = sprintf('<a href="%s">%s</a>', gi($this->Meta, 'consignment_tracking_url'), gi($this->Meta, 'consignment_tracking_code') );
        }
        elseif ( $ph == '@parcel_tracking_code@'){
          $placeholders[$ph] = gi($this->Meta, 'consignment_tracking_code');
        }
        elseif ( $ph == '@parcel_date@'){
          $placeholders[$ph] = date( get_option( 'date_format' ), strtotime(gi($this->Meta, 'consignment_created_at')) );
        }
      }

      _log($placeholders);

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

      // _log('$notification');
      // _log($notification);
      wp_mail( $email, $notification['subject'], $notification['message'] );
      _log('notification sent');
    }
    else{
      _log('no customer mail');
    }

  }


  // function setTransportAgreements($array){
  //   _log('setTransportAgreements');

  //   if ( is_array($array) ){
  //     foreach ($array as $key => $value) {
  //       // _log($value);
  //       if ( isset($value['carrier']['identifier']) && isset($value['id']['$']) ){
  //         // _log($value['carrier']['identifier']);

  //         // set carrier
  //         $carrier = array(
  //           'id'          => $value['id']['$'],
  //           'identifier'  => $value['carrier']['identifier'],
  //           'name'        => $value['carrier']['name'],
  //           'desc'        => $value['description'],
  //           'title'       => $value['carrier']['identifier'].' ('.$value['description'] .')'
  //         );


  //         // set products
  //         $products = array();
  //         if ( is_array($value['products']['product']) ){
  //           foreach ( $value['products']['product']  as $key => $product) {
  //               // _log($key);
  //               // _log($product);

  //             $types = array();
  //             if ( isset($product['item_types']['item_type']) && is_array($product['item_types']['item_type']) ){
  //               foreach ($product['item_types']['item_type'] as $index => $type){
  //                 if ( $abbreviation = gi($type, '@abbreviation' ) ){
  //                   $types[ $type['@abbreviation'] ] = $type['@name_no'];
  //                 }

  //               }
  //             }

  //             if ( !empty($types) ){
  //               $products[] = array(
  //                 'name'        => $product['name'],
  //                 'identifier'  => $product['identifier'],
  //                 'types'       => $types,
  //                 );
  //             }
  //           }
  //         }


  //         // add carrier if has products
  //         if ( $products ){
  //           $carrier['products'] = $products;
  //           $this->TransportAgreements[] = $carrier;
  //         }

  //       }
  //     }

  //     $this->saveTransportAgreements();
  //   }

  //   // _log($this->TransportAgreements);
  // }
  //


} // end of class
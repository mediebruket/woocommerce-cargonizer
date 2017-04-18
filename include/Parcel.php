<?php

class Parcel{
  public $ID;
  public $TrackingProvider;
  public $Items;
  public $WC_Order;
  public $TransportAgreements;
  public $TransportAgreementId;
  public $TransportAgreementProduct;
  public $Meta;


  function __construct($post_id){
    if ( is_numeric($post_id) ){
      // set woocommerce order object
      $this->WC_Order = new WC_Order($post_id);

      if ( $post = get_post($post_id) ){
        // set wordpress attributes
        foreach ($post as $attribute => $value) {
          $this->$attribute = $value;
        }

        // set post meta
        $this->Meta = get_post_custom($this->ID );

        $this->setTransportAgreementSettings();

        // set tracking provider
        $this->TrackingProvider = $this->getTrackingProvider();

        // set itmes
        $this->Items = $this->getItems();
      }
    }
  }

  function isReady(){
    $is_ready = false;
    // if parcel is not exported / cargonized
    if ( !gi($this->Meta, 'is_cargonized') ){
      // if parcel has transport agreement id & product && items
      if ( $this->TransportAgreementId && $this->TransportAgreementProduct && $this->Items ){
        // checkbox create_consignment is on
        if ( gi($this->Meta, 'create_consignment') ){
          $is_ready = true;
        }
      }
    }

    return $is_ready;
  }


  function setTransportAgreementSettings(){
    if ( $ta = gi($this->Meta, 'transport_agreement') ){
      $transport_agreement = explode('|', $ta);
      $this->TransportAgreementId = $transport_agreement[0];
      $this->TransportAgreementProduct = $transport_agreement[1];
    }
  }

  public static function _getTransportAgreementSettings($post_id){
    $meta = get_post_custom( $post_id );
    $ta_settings = array();
    if ( $ta = gi($meta, 'transport_agreement') ){
      $transport_agreement = explode('|', $ta);
      $ta_settings['id'] = $transport_agreement[0];
      $ta_settings['product'] = $transport_agreement[1];

      return $ta_settings;
    }
  }


  function getTrackingProvider(){
    return get_post_meta( $this->ID, '_tracking_provider', true );
  }

  function getItems(){
    return acf_getField('items', $this->ID);
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
    // _log('prepareExport');
    // _log($this->Items);
    // http://www.logistra.no/api-documentation/12-utviklerinformasjon/16-api-consignments.html
    $export['consignments']['consignment']['_attr'] = array( 'transport_agreement' => $this->TransportAgreementId, 'estimate' => "true" );
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
    $export['consignments']['consignment']['parts']['consignee']['name']       = gi( $this->Meta, '_shipping_first_name' )." ".gi( $this->Meta,'_shipping_last_name' ); // customer address
    $export['consignments']['consignment']['parts']['consignee']['country']    = ( gi( $this->Meta, '_shipping_country' ) ) ? gi( $this->Meta, '_shipping_country' ) : 'NO';
    $export['consignments']['consignment']['parts']['consignee']['postcode']  = gi( $this->Meta, '_shipping_postcode' );
    $export['consignments']['consignment']['parts']['consignee']['city']       = gi( $this->Meta, '_shipping_city' );
    $export['consignments']['consignment']['parts']['consignee']['address1']   = gi( $this->Meta, '_shipping_address_1' );
    $export['consignments']['consignment']['parts']['consignee']['address2']   = gi( $this->Meta, '_shipping_address_2' );
    $export['consignments']['consignment']['parts']['consignee']['email']      = gi( $address, 'email' );
    $export['consignments']['consignment']['parts']['consignee']['mobile']     = gi( $address, 'phone' );

    // return address // verlo address
    $export['consignments']['consignment']['parts']['return_address']['name'] = 'Verlo AS';
    $export['consignments']['consignment']['parts']['return_address']['country'] = 'NO';
    $export['consignments']['consignment']['parts']['return_address']['postcode'] = '6700';
    $export['consignments']['consignment']['parts']['return_address']['city'] = 'Måløy';
    $export['consignments']['consignment']['parts']['return_address']['address1'] = 'Gate 1 nr 88';

    // set consignee
    $export['consignments']['consignment']['consignee'] =  $export['consignments']['consignment']['parts']['consignee'];

    //
    // ---------------- items ----------------
    //
    // <item type="PK" amount="1" weight="22" volume="122" description="Something else"/>

    $export['consignments']['consignment']['items'] = array(); // packages
    foreach ($this->Items as $key => $item) {
      $array = array('item' => null );
      $array['item']['_attr'] =
        array(
          'type'    => gi( $item, 'parcel_type' ),
          'amount'  => gi( $item, 'parcel_amount' ),
          'weight'  => gi( $item, 'parcel_weight' ),
          'length'  => gi( $item, 'parcel_length' ),
          'width'   => gi( $item, 'parcel_width' ),
          'description' => gi($item, 'parcel_description' ),
        );

      $export['consignments']['consignment']['items'][]= $array;
    }


    // $export['consignments']['services'] = array(); // packages
    // $export['consignments']['references']['consignor'] = null;
    // $export['consignments']['references']['consignee'] = null;

    $export['consignments']['consignment']['messages']['consignor'] = $this->post_excerpt;
    $export['consignments']['consignment']['messages']['consignee'] = gi( $this->Meta, 'message_consignee');

    // _log($export);
    return $export;
  }


  function notiyCustomer(){
    // update meta fields
    $this->Meta = get_post_custom($this->ID );
    $address = $this->WC_Order->get_address();

    if ( $email = gi( $address, 'email' ) ){

      $subject = sprintf('NBS: Bestilling %s hos %s er ferdigbehandlet', $this->ID,  get_bloginfo('name') );
      $message = '
                  <p>Hei!</p>
                  <p>Din ordre er nå ferdigbehandlet.</p>
                  <p>Pakken kan spores på <a href="%s">%s</a> med sendingsnummer %s.</p>
                  <p > Takk for at du handler hos oss!</p>
                  <p style="margin-top:30px">Vennlig hilsen<br/>
                  Verlo.no</p>
                  ';


      $message = sprintf(  $message,
                gi($this->Meta, 'consignment_tracking_url'),
                dirname( gi($this->Meta, 'consignment_tracking_url') ),
                gi($this->Meta, 'consignment_tracking_code')
              );

      wp_mail( $email, $subject, $message );
      _log('notification send');
    }
    else{
      _log('no customer mail');
    }

  }




}
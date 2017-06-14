<?php
add_action( 'init', array('Consignment', '_registerPostType'), 10 );
add_action( 'init', array('Consignment', '_updateNextShippingDate'), 20 );
add_action( 'pre_get_posts', array('Consignment', '_orderConsignmentsByShippingDate'), 20 );

class Consignment{
  public $ID;
  public $Id;
  public $CarrierId;
  public $CarrierProduct;
  public $CarrierProductId;
  public $CarrierProductType;
  public $CarrierProductService;
  public $History;
  public $IsRecurring;
  public $IsCargonized;
  public $Items;
  public $OrderId;
  public $Meta;

  function __construct( $post_id ){
    $this->Id               = $this->ID = $post_id;
    $this->Meta             = $this->getPostMeta();
    $this->OrderId          = $this->getOrderId();
    $this->CarrierId        = $this->getCarrierId();
    $this->CarrierProduct   = $this->getCarrierProduct();
    $this->CarrierProductServices   = $this->getCarrierProductServices();
    $this->Items            = $this->getItems();
    $this->IsRecurring      = $this->isRecurring();
    $this->History          = $this->getHistory();

    if ( $this->CarrierProduct ){
      $tmp = explode('|', $this->CarrierProduct );
      $this->CarrierProductId    = ( isset($tmp[0]) ) ? $tmp[0] : null;
      $this->CarrierProductType  =  ( isset($tmp[1]) ) ? $tmp[1] : null;
    }
    // _log($this);
  }


  function getPostMeta(){
    return get_post_custom( $this->ID );
  }


  function getOrderId(){
    return gi($this->Meta, 'recurring_consignment_order_id');
  }

  function isRecurring(){
    return gi($this->Meta, 'consignment_is_recurring');
  }


  function getItems(){
    return acf_getField('consignment_items', $this->ID);
  }


  function getCarrierId(){
    return gi($this->Meta, 'consignment_carrier_id');
  }


  function getCarrierProduct(){
    return gi($this->Meta, 'consignment_product');
  }


  function getCarrierProductServices(){
    return acf_getField('consignment_services', $this->Id );
  }



  public static function createOrUpdate( $Parcel, $recurring=false ){
    _log('Consignment::createOrUpdate('.$Parcel->ID.')');

    //_log($Parcel);
    $args = array(
      'post_author' => get_current_user_id(),
      'post_title' =>  sprintf( 'Order #%s %s', $Parcel->ID, ( ($recurring) ? '| recurring ' : null) ),
      'post_status' => 'publish',
      'post_type' => 'consignment',
      'post_parent' => 0,
    );

    if ( $cid = self::getConsignmentIdByOrderId( $Parcel->ID, $recurring ) ){
      _log('update existing consignment: '.$cid);
      $args['ID'] = $cid;
    }
    else{
      _log('create new consignment');
    }

    if ( $post_id = wp_insert_post( $args ) ){
      _log('consignment: '.$post_id);

      $meta_order_key = 'consignment_order_id';
      if ( $recurring ){
        $meta_order_key = 'recurring_consignment_order_id';
      }
      update_post_meta( $post_id, $meta_order_key, $Parcel->ID );
      update_post_meta( $post_id, 'consignment_is_recurring', $recurring );

      if ( $recurring ){
        update_post_meta( $post_id, 'recurring_consignment_interval', $Parcel->RecurringInterval );
        update_post_meta( $post_id, 'consignment_carrier_id', $Parcel->RecurringCarrierId );
        update_post_meta( $post_id, 'consignment_product', $Parcel->RecurringConsignmentType );
        update_post_meta( $post_id, 'consignment_services', $Parcel->RecurringConsignmentServices );
        update_post_meta( $post_id, 'consignment_message', $Parcel->RecurringConsignmentMessage );
        acf_updateField('consignment_items', $Parcel->RecurringConsignmentItems, $post_id);
      }
      else{
        _log('single future consignment');
        acf_updateField('consignment_items', $Parcel->Items, $post_id);
        update_post_meta( $post_id, 'consignment_carrier_id', $Parcel->TransportAgreementId );
        update_post_meta( $post_id, 'consignment_product', $Parcel->ParcelType );
        update_post_meta( $post_id, 'consignment_services', $Parcel->ParcelServices );
        update_post_meta( $post_id, 'consignment_message', $Parcel->ParcelMessage );
        update_post_meta( $post_id, 'consignment_next_shipping_date', $Parcel->ShippingDate );
      }

      _log('Parcel');
      _log($Parcel);


      // copy meta values
      $address = $Parcel->WC_Order->get_address();
      update_post_meta( $post_id, '_shipping_first_name', gi( $Parcel->Meta, '_shipping_first_name' ) );
      update_post_meta( $post_id, '_shipping_last_name',  gi( $Parcel->Meta, '_shipping_last_name' ) );
      update_post_meta( $post_id, '_shipping_country',    gi( $Parcel->Meta, '_shipping_country' ) );
      update_post_meta( $post_id, '_shipping_postcode',   gi( $Parcel->Meta, '_shipping_postcode' ) );
      update_post_meta( $post_id, '_shipping_city',       gi( $Parcel->Meta, '_shipping_city' ) );
      update_post_meta( $post_id, '_shipping_address_1',  gi( $Parcel->Meta, '_shipping_address_1' ) );
      update_post_meta( $post_id, '_shipping_address_2',  gi( $Parcel->Meta, '_shipping_address_2' ) );

      update_post_meta( $post_id, '_billing_first_name',  gi( $Parcel->Meta, '_billing_first_name' ) );
      update_post_meta( $post_id, '_billing_last_name',   gi( $Parcel->Meta, '_billing_last_name' ) );
      update_post_meta( $post_id, '_billing_country',     gi( $Parcel->Meta, '_billing_country' ) );
      update_post_meta( $post_id, '_billing_postcode',    gi( $Parcel->Meta, '_billing_postcode' ) );
      update_post_meta( $post_id, '_billing_city',        gi( $Parcel->Meta, '_billing_city' ) );
      update_post_meta( $post_id, '_billing_address_1',   gi( $Parcel->Meta, '_billing_address_1' ) );
      update_post_meta( $post_id, '_billing_address_2',   gi( $Parcel->Meta, '_billing_address_2' ) );

      update_post_meta( $post_id, 'email', gi( $address, 'email' ) );
      update_post_meta( $post_id, 'phone', gi( $address, 'phone' ) );


      // TODO ??
      // Products: name, sku, count

      if ( !isset($args['ID']) ){
        self::addNote($Parcel->ID, $post_id);
      }
    }
  }


  function getHistory(){
    _log('getHistory');
    _log($this->Meta['consignment_history']);
    return $this->Meta['consignment_history'];
  }


  function updateHistory(){
    $consignment = array();
    $consignment['created_at'] = '2017-01-02';
    $consignment['consignment_id'] = '2017-01-02';
    $consignment['consignment_tracking_code'] = rand(9999999, 999999999);
    $consignment['consignment_tracking_url'] = '<a href="#">show</a>';
    $consignment['consignment_pdf'] = '<a href="#">download</a>';

    add_post_meta( $this->Id, 'consignment_history', $consignment, false );
  }


  function prepareExport(){
    _log('Consignment::prepareExport');

    //_log($this->Meta);
    // _log('prepareExport');
    // _log($this->Items);
    // http://www.logistra.no/api-documentation/12-utviklerinformasjon/16-api-consignments.html
    $export['consignments']['consignment']['_attr'] =
      array(
        'transport_agreement' => $this->CarrierId,
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


    if ( $parcel_services = gi( $this->Meta, 'consignment_services') ){
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

    $export['consignments']['consignment']['messages']['consignor'] = 'messages-consignor';
    $export['consignments']['consignment']['messages']['consignee'] = gi( $this->Meta, 'consignment_message');

    // _log($export);
    return $export;
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
    _log($sql);
    return $wpdb->get_var($sql);
  }


  public static function getAllRecurringConsignments(){
    global $wpdb;
    $sql =  "SELECT p.ID FROM  %s p, %s pm WHERE p.ID = pm.post_id AND p.post_type = 'consignment' AND pm.meta_key = 'consignment_is_recurring' and pm.meta_value = '1'";
    $sql = sprintf($sql, $wpdb->posts, $wpdb->postmeta );

    return $wpdb->get_col($sql);
  }


  public static function _updateNextShippingDate(){
    $post_ids = self::getAllRecurringConsignments();
    if ( is_array($post_ids) ){
      foreach ($post_ids as $key => $post_id) {

        if ( $interval = get_post_meta( $post_id, 'recurring_consignment_interval', true ) ){
          $month = date('m');
          $year = date('Y');

          if ( date('d') > $interval ){
            if ( $month != 12 ){
              $month += 1;
            }
            else{
              $month = 1;
              $year += 1;
            }
          }

          $new_shipping_date = $year.str_pad($month, 2, "0", STR_PAD_LEFT).str_pad($interval, 2, "0", STR_PAD_LEFT);
          update_post_meta( $post_id, 'consignment_next_shipping_date', $new_shipping_date );
        }
      }
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
      'exclude_from_search'  => false,
      'publicly_queryable'   => true,
      'show_ui'              => true,
      'show_in_nav_menus'    => true,
      'show_in_menu'         => true,
      'show_in_admin_bar'    => true,
      'menu_position'        => 42,
      'capability_type'      => 'post',
      'hierarchical'         => true,
      'supports'             => array('title' ),
      'has_archive'          => true,
      'rewrite'              => array('slug' => 'consignment', 'with_front' => FALSE),
      'query_var'            => true,
      'can_export'           => true,
      'taxonomies'           => array('post_tag')
    );
    register_post_type( 'consignment' ,$args) ;

  }

}
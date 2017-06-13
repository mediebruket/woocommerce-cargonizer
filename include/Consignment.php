<?php
add_action( 'init', array('Consignment', '_registerPostType') );

class Consignment{
  public $ID;
  public $Id;
  public $CarrierId;
  public $CarrierProduct;
  public $CarrierProductId;
  public $CarrierProductType;
  public $CarrierProductService;
  public $IsRecurring;
  public $IsCargonized;
  public $OrderId;
  public $Meta;

  function __construct( $post_id ){
    $this->Id               = $this->ID = $post_id;
    $this->Meta             = $this->getPostMeta();
    $this->OrderId          = $this->getOrderId();
    $this->CarrierId        = $this->getCarrierId();
    $this->CarrierProduct   = $this->getCarrierProduct();
    $this->CarrierProductServices   = $this->getCarrierProductServices();
    $this->IsRecurring      = $this->isRecurring();

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
    _log( $Parcel->WC_Order->get_address() );
    //_log($Parcel);

    $args = array(
      'post_author' => get_current_user_id(),
      'post_title' =>  sprintf( 'Related to order #%s %s', $Parcel->ID, ( ($recurring) ? '| recurring ' : null) ),
      'post_status' => 'publish',
      'post_type' => 'consignment',
      'post_parent' => 0,
    );

    if ( $cid = self::getConsignmentIdByOrderId( $Parcel->ID ) ){
      _log('update existing consignment: '.$cid);
      $args['ID'] = $cid;
    }
    else{
      _log('create new consignment');
    }

    if ( $post_id = wp_insert_post( $args ) ){
      _log('consignment: '.$post_id);

      $meta_key = 'consignment_order_id';
      update_post_meta( $post_id, 'recurring_consignment_order_id', $Parcel->ID );
      update_post_meta( $post_id, 'recurring_consignment_interval', $Parcel->RecurringInterval );

      // save
      update_post_meta( $post_id, 'consignment_carrier_id', $Parcel->RecurringCarrierId );
      update_post_meta( $post_id, 'consignment_product', $Parcel->RecurringConsignmentType );
      update_post_meta( $post_id, 'consignment_services', $Parcel->RecurringConsignmentServices );
      update_post_meta( $post_id, 'consignment_message', $Parcel->RecurringConsignmentMessage );
      update_post_meta( $post_id, 'consignment_is_recurring', $Parcel->IsRecurring );

      // _log('Parcel');
      // _log($Parcel);
      acf_updateField('consignment_items', $Parcel->RecurringConsignmentItems, $post_id);

      // TODO
      // is recurring
      // Products: name, sku, count

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


      if ( !isset($args['ID']) ){
        self::addNote($Parcel->ID, $post_id);
      }

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
    global $wpdb;
    $meta_key = 'consignment_order_id';
    if ( $recurring ){
      $meta_key = 'recurring_consignment_order_id';
    }
    $sql = sprintf("SELECT post_id FROM %s WHERE meta_key = '%s' and meta_value= '%s';", $wpdb->postmeta, $meta_key, $order_id );
    return $wpdb->get_var($sql);
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
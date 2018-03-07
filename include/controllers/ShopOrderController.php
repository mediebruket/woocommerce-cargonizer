<?php

add_action( 'save_post', array('ShopOrderController', 'saveServicePartner'), $priority = 10, $accepted_args = 2 );


class ShopOrderController extends CargonizerCommonController{
  protected $Order;
  protected $WC_Order;
  protected $Settings;

  function __construct( ){
    parent::__construct();
    add_action( 'save_post', array($this, 'saveConsignmentSettgings'), 10, 1 );
    add_action( 'save_post', array($this, 'saveConsignment'), 20, 1 );
    add_action( 'init',  array($this, 'resetConsignment') , 10, 2 );
    add_filter( 'bulk_actions-edit-shop_order', array($this, 'filterBulkActions') );
    add_action( 'handle_bulk_actions-edit-shop_order', array($this, 'bulkCreateConsigments' ), 10, 3 );
    add_action( 'admin_notices',  array($this, 'setBulkAdminNotice')  );
  }


  function setBulkAdminNotice() {
    // _log('ShopOrderController::setBulkAdminNotice()');
    if ( isset($_REQUEST['bulk_created_consignments']) && is_numeric($_REQUEST['bulk_created_consignments']) ) {
      printf( '<div id="message" class="updated">%s %s</div>', $_REQUEST['bulk_created_consignments'], __('consignments created', 'wc-cargonizer') );
    }
  }


  function bulkCreateConsigments( $redirect_to, $doaction, $post_ids ){
    _log('ShopOrderController::bulkCreateConsigments()');
    _log($doaction);
    _log($post_ids);

    if ( $doaction !== 'create_consignment' ) {
      return $redirect_to;
    }

    if ( is_array($post_ids) && !empty($post_ids) ) {
      foreach ($post_ids as $key => $order_id) {
        $_REQUEST['post_ID'] = $order_id;
        $this->saveConsignment($order_id, $force=true, $create=false);
      }
    }
    else{
      _log('no post ids checked');
    }

    $redirect_to = add_query_arg( 'bulk_created_consignments', count( $post_ids ), $redirect_to );


    return $redirect_to;
  }


  function filterBulkActions($actions){
    $actions['create_consignment'] = __('Create consignment', 'wc-cargonizer');

    return $actions;
  }


  public static function saveServicePartner( $post_id, $post){
    if ( !is_admin() ){
      if ($post->post_type == 'shop_order' ){
        _log('ShopOrderController::saveServicePartner()');
        $copy = array( 'wcc-service-partner-id', 'wcc-service-partner-name', 'wcc-service-partner-address', 'wcc-service-partner-postcode', 'wcc-service-partner-city', 'wcc-service-partner-country' );
        foreach($copy as $key => $index) {
          update_post_meta( $post_id, $index, gi($_REQUEST, $index ) );
          _log('copied: '.$index);
        }
      }
    }
  }


  function saveConsignmentSettgings( $post_id ){
    //_log($_REQUEST);
    if ( gi($_REQUEST, 'post_ID') == $post_id ){
      $post = get_post($post_id);

      if ( $post->post_type == 'shop_order' ){
        _log('ShopOrderController::saveConsignmentSettgings()');
        $AdminShopOptions = new AdminShopOptions();
        $tabs = array("Parcel");

        if ( isset($_POST['is_recurring']) && $_POST['is_recurring'] == '1' ){
          $tabs[] = "Recurring";
        }
        else{
          update_post_meta( $post_id, 'is_recurring', 0 );
        }

        foreach ($tabs as $tab_index => $tab) {
          $method = 'load'.$tab.'Options';
          $options = $AdminShopOptions->$method();

          foreach ( $options as $key => $o ){
            if ( $o['type'] != 'table'){
              $index = $o['name'];
              $meta_value = ( isset($_POST[$index]) ) ? $_POST[$index] : null;
              if ( update_post_meta( $post_id, $index, $meta_value ) ){
                _log('updated: '.$index);
              }
            }
          }
        }
      }
    }
  }


  function saveConsignment( $post_id, $force=false, $create=true ){

    if ( !isset($_REQUEST['post_ID']) or $_REQUEST['post_ID'] != $post_id ){
      return false;
    }

    if ( $Order = $this->isOrder( $post_id, $return_object=true ) ){
      // _log('ShopOrderController::saveConsignment()');
      $consignment_post_id = null;
      $is_future = ($Order->hasFutureShippingDate()) ? true : false;

      if ( $is_future ){
        $consignment_post_id = Consignment::createOrUpdate( $Order, $recurring=false );
      }

      if ( $Order->isReady($force) && !$is_future ){
        _log('... is ready');
        if ( $cid = Consignment::createOrUpdate( $Order, $recurring=false ) ){
          _log('consignment created/updated: '.$cid);

          if ( $create ){
            _log('create consignment now');
            $result = ConsignmentController::createConsignment($cid);
          }
        }
      }
      else{
        _log('not ready or has future shipping date');
      }


      if ( $Order->IsRecurring ){
        _log('create recurring');
        Consignment::createOrUpdate( $Order, $recurring=true );
      }
    }
  }


  function resetConsignment(){
    if ( isset($_GET['wcc_action']) && $_GET['wcc_action'] == 'reset_consignment' ){
      $order_id = $_GET['post'];
      if ( is_numeric($order_id) ){
        $Order = new ShopOrder($order_id);
        $Order->reset();
        $Order->addNote('reset');

        if ( $location = get_edit_post_link($order_id) ){
          wp_redirect( str_replace('&amp;', '&', $location) );
          die();
        }
      }
    }
  }

} // end of class

new ShopOrderController();
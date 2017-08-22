<?php

class ShopOrderController extends CargonizerCommonController{
  protected $Order;
  protected $WC_Order;
  protected $Settings;

  function __construct( ){
    parent::__construct();

    add_action( 'save_post', array($this, 'saveConsignmentSettgings'), 10, 1 );
    add_action( 'save_post', array($this, 'saveConsignment'), 20, 1 );
    add_action( 'init',  array($this, 'resetConsignment') , 10, 2 );
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


  function saveConsignment( $post_id ){
    if ( !isset($_REQUEST['post_ID']) or $_REQUEST['post_ID'] != $post_id ){
      return false;
    }

    if ( $Order = $this->isOrder( $return_object=true ) ){
      _log('ShopOrderController::saveConsignment()');
      $consignment_post_id = null;
      $is_future = ($Order->hasFutureShippingDate()) ? true : false;

      if ( $is_future ){
        $consignment_post_id = Consignment::createOrUpdate( $Order, $recurring=false );
      }

      if ( $Order->isReady($force=false) && !$is_future ){
        _log('... is ready');
        if ( $cid = Consignment::createOrUpdate( $Order, $recurring=false ) ){
          _log('consignment created/updated: '.$cid);
          _log('create consignment now');
          $result = ConsignmentController::createConsignment($cid);
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
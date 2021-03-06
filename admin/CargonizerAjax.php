<?php

class CargonizerAjax{

  function __construct(){
    add_action( 'wp_ajax_wcc_print_order', array( $this, '_printOrder' ) );
    add_action( 'wp_ajax_edit', array( $this, '_addPackageRow' ) );
    add_action( 'wp_ajax_nopriv_ajaxedit', array( $this, '_addPackageRow' ) );
    add_action( 'wp_ajax_delete', array( $this, '_deletePackageRow' ) );
    add_action( 'wp_ajax_wcc_create_consignment', array( $this, '_createAjaxConsignment' ) );
    add_action( 'wp_ajax_wcc_print_latest_consignment', array( $this, '_printLatestConsignment' ) );
  }


  function _addPackageRow(){
    // _log('CargonizerAjax::_addPackageRow()');
    // _log($_REQUEST);

    if ( $post_id = gi($_REQUEST, 'post_id') ){
      unset($_REQUEST['action'] );
      unset($_REQUEST['post_id'] );
      $post = get_post( $post_id );

      if ( isset($_REQUEST['id']) && is_object($post) ){
        // _log($post->post_type);
        $meta_key = ( $post->post_type == 'consignment' ) ? 'consignment_packages' : 'parcel_packages';

        if ( isset($_REQUEST['recurring']) && $_REQUEST['recurring'] == '1' && $post->post_type != 'consignment' ){
          $meta_key = 'recurring_consignment_packages';
          unset($_REQUEST['recurring'] );
        }

        // _log($meta_key);
        $packages = get_post_meta( $post_id, $meta_key, true );
        $packages[ $_REQUEST['id'] ] = $_REQUEST;
        // _log($packages);
        update_post_meta( $post_id, $meta_key, $packages );
      }
    }
    wp_die();
  }


  function _deletePackageRow(){
    // _log('CargonizerAjax::_deletePackageRow');
    $post_id = gi($_REQUEST, 'post_id');
    $package_id = gi($_REQUEST, 'id');
    // _log($_REQUEST);

    if ( $post_id && $package_id ){
      $post = get_post($post_id );
      $meta_key = ( $post->post_type == 'consignment' ) ? 'consignment_packages' : 'parcel_packages';

      if ( isset($_REQUEST['recurring']) && $_REQUEST['recurring'] == '1' && $post->post_type != 'consignment' ){
        $meta_key = 'recurring_consignment_packages';
      }

      $packages = get_post_meta( $post_id, $meta_key, true );

      // _log('before');
      // _log($packages);
      if ( isset($packages[$package_id]) ){
        unset($packages[$package_id]);
      }
      // _log('after');
      // _log($packages);

      update_post_meta( $post_id, $meta_key, $packages );
    }
    else{
      _log('missing:');
      _log($post_id);
      _log($package_id);
    }

    wp_die();
  }


  function _printOrder(){
    _log('Cargonizer::printOrder');

    $response = '1';
    if ( isset($_POST['order_id']) && is_numeric($_POST['order_id']) ){
      $Order = new ShopOrder( $_POST['order_id'] );

      _log('ConsignmentId: '.$Order->ConsignmentId);
      _log('Printer: '. $Order->Printer);

      if ( !$Order->ConsignmentId ){
        $response = __('Missing consignment id', 'wc-cargonizer');
      }

      if ( !$Order->Printer ){
        $response = __('Missing printer_abort(printer_handle)', 'wc-cargonizer');
      }

      if ( $Order->ConsignmentId && $Order->Printer ){
        $Api = new CargonizerApi();
        if ( $Api->postLabel( $Order->ConsignmentId, $Order->Printer ) == 'Printing' ){
          $printer = $Order->Printer;
          if ( $ta = get_transient( 'wcc_printer_list' ) ){
            if ( isset( $ta[$Order->Printer] ) ){
              $printer = $ta[$Order->Printer]. " (".$Order->Printer.")";
            }
          }
          $response =  sprintf( __('Label was printed on printer', 'wc-cargonizer'), $printer ) ;
        }
        else{
          $response = __('Could not connect to Cargonizer', 'wc-cargonizer');
        }
      }
    }

    echo $response;
    wp_die();
  }


  function _createAjaxConsignment(){
    _log('CargonizerAjax::_createAjaxConsignment()');

    $response = array('status' => null, 'message' => null);
    if ( isset($_POST['order_id']) && is_numeric($_POST['order_id']) ){
      $result = ConsignmentController::createConsignment( $_POST['order_id'] );
      // _log('result');
      // _log($result);
      if ( is_array($result) && isset($result['consignment_id']) ){
        $response['status'] = __('ok', 'wc-cargonizer');
        $response['message'] = sprintf( __('New consignment created: %s', 'wc-cargonizer'), $result['consignment_id'] );
      }
      else{
        $response['status'] = __('error', 'wc-cargonizer');

        if ( is_string($result) ){
          $response['message'] = $result;
        }
        else if ( is_array($result) && isset($result[0]) ){
          $response['message'] =  implode(',', $result);
        }
      }
    }

    echo json_encode($response);
    wp_die();
  }


  function _printLatestConsignment(){
    _log('Cargonizer::_printLatestConsignment()');
    // _log($_POST);

     $response = array('status' => null, 'message' => null);

    if ( isset($_POST['order_id']) && is_numeric($_POST['order_id']) ){
      $Consignment = new Consignment( $_POST['order_id'] );

      $consignment_id = null;
      if ( isset($Consignment->History[0]['consignment_id']) ){
        $consignment_id = $Consignment->History[0]['consignment_id'];
      }

      _log('ConsignmentId: '.$consignment_id);
      _log('Printer: '. $Consignment->Printer);

      if ( !$consignment_id ){
        $response['status'] = 'error';
        $response['message'] = __('Missing consignment id', 'wc-cargonizer');
      }

      if ( !$Consignment->Printer ){
        $response['status'] = 'error';
        $response['message'] = __('Missing printer_abort (printer_handle)', 'wc-cargonizer');
      }

      if ( $consignment_id && $Consignment->Printer ){
        $Api = new CargonizerApi();
        if ( $Api->postLabel( $consignment_id, $Consignment->Printer ) == 'Printing' ){
          $printer = $Consignment->Printer;
          if ( $ta = get_transient( 'wcc_printer_list' ) ){
            if ( isset($ta[$Consignment->Printer]) ){
              $printer = $ta[$Consignment->Printer]. " (".$Consignment->Printer.")";
            }
          }
          $response['status'] = 'ok';
          $response['message'] = sprintf( __('Label was printed on printer %s', 'wc-cargonizer'), $printer );
        }
        else{
         $response['status']   = 'error';
         $response['message']  = __('Could not connect to Cargonizer', 'wc-cargonizer');
        }
      }
    }
    else{
      $response['status'] = 'error';
      $response['message'] = __('Missing post id', 'wc-cargonizer');
    }

    echo json_encode($response);
    wp_die();
  }

} // end of class

new CargonizerAjax();
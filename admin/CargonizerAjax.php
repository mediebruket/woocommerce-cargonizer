<?php

class CargonizerAjax{


  function __construct(){
    add_action( 'wp_ajax_wcc_print_order', array( $this, '_printOrder' ) );
    add_action( 'wp_ajax_edit', array( $this, '_addPackageRow' ) );
    add_action( 'wp_ajax_nopriv_ajaxedit', array( $this, '_addPackageRow' ) );
    add_action( 'wp_ajax_delete', array( $this, '_deletePackageRow' ) );
    add_action( 'wp_ajax_wcc_create_consignment', array( $this, '_createConsignment' ) );
    add_action( 'wp_ajax_wcc_print_latest_consignment', array( $this, '_printLatestConsignment' ) );
  }


  function _addPackageRow(){
    if ( $post_id = gi($_REQUEST, 'post_id') ){
      unset($_REQUEST['action'] );
      unset($_REQUEST['post_id'] );
      $packages = get_post_meta( $post_id, 'parcel_packages', true );
      $packages[ $_REQUEST['id'] ] = $_REQUEST;
      update_post_meta( $post_id, 'parcel_packages', $packages );
    }
    wp_die();
  }


  function _deletePackageRow(){    
    $post_id = gi($_REQUEST, 'post_id');
    $package_id = gi($_REQUEST, 'id');

    if ( $post_id && $package_id ){
      $packages = get_post_meta( $post_id, 'parcel_packages', true );
      if ( isset($packages[$package_id]) ){
        unset($packages[$package_id]);
        $packages = array_values($packages);
      }

      update_post_meta( $post_id, 'parcel_packages', $packages );
    }
  }


  function _printOrder(){
    _log('Cargonizer::printOrder');

    $response = '1';
    if ( isset($_POST['order_id']) && is_numeric($_POST['order_id']) ){
      $Order = new ShopOrder( $_POST['order_id'] );

      _log('ConsignmentId: '.$Order->ConsignmentId);
      _log('Printer: '. $Order->Printer);

      if ( !$Order->ConsignmentId ){
        $response = 'Missing consignment id';
      }

      if ( !$Order->Printer ){
        $response = 'Missing printer_abort(printer_handle)';
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
          $response = 'Label was printed on printer '.$printer;
        }
        else{
          $response = 'Could not connect to Cargonizer';
        }
      }
    }

    echo $response;
    wp_die();
  }


  function _createConsignment(){
    _log('CargonizerAjax::_createConsignment()');

    $response = array('status' => null, 'message' => null);
    if ( isset($_POST['order_id']) && is_numeric($_POST['order_id']) ){
      $result = ConsignmentController::createConsignment( $_POST['order_id'] );
      
      // _log('result');
      // _log($result);
      if ( is_array($result) && isset($result['consignments']['consignment']['id']['$']) ){
        $response['status'] = 'ok';
        $response['message'] = sprintf( __('New consignment created: %s', 'wc-cargonizer'),  $result['consignments']['consignment']['id']['$'] );
      }
      else{
        $response['status'] = 'error';

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
        $response['message'] = 'Missing consignment id';
      }

      if ( !$Consignment->Printer ){
        $response['status'] = 'error';
        $response['message'] = 'Missing printer_abort(printer_handle)';
      }

      if ( $consignment_id && $Consignment->Printer ){
        $Api = new CargonizerApi();
        if ( $Api->postLabel( $consignment_id, $Consignment->Printer ) == 'Printing' ){
          $printer = $Consignment->Printer;
          if ( $ta = get_transient( 'wcc_printer_list' ) ){
            if ( isset( $ta[$Consignment->Printer] ) ){
              $printer = $ta[$Consignment->Printer]. " (".$Consignment->Printer.")";
            }
          }
          $response['status'] = 'ok';
          $response['message'] = 'Label was printed on printer '.$printer;
        }
        else{
         $response['status']   = 'error';
         $response['message']  = 'Could not connect to Cargonizer';
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
<?php

class CargonizerAjax{

  function __construct(){
    add_action( 'wp_ajax_wcc_print_order', array( $this, '_printOrder' ) );
    add_action( 'wp_ajax_wcc_create_consignment', array( $this, '_createConsignment' ) );
    add_action( 'wp_ajax_wcc_print_latest_consignment', array( $this, '_printLatestConsignment' ) );
  }


  function _printOrder(){
    _log('Cargonizer::printOrder');

    $response = '1';
    if ( isset($_POST['order_id']) && is_numeric($_POST['order_id']) ){
      $Parcel = new Parcel( $_POST['order_id'] );

      _log('ConsignmentId: '.$Parcel->ConsignmentId);
      _log('Printer: '. $Parcel->Printer);

      if ( !$Parcel->ConsignmentId ){
        $response = 'Missing consignment id';
      }

      if ( !$Parcel->Printer ){
        $response = 'Missing printer_abort(printer_handle)';
      }

      if ( $Parcel->ConsignmentId && $Parcel->Printer ){
        $Api = new CargonizerApi();
        if ( $Api->postLabel( $Parcel->ConsignmentId, $Parcel->Printer ) == 'Printing' ){
          $printer = $Parcel->Printer;
          if ( $ta = get_transient( 'wcc_printer_list' ) ){
            if ( isset( $ta[$Parcel->Printer] ) ){
              $printer = $ta[$Parcel->Printer]. " (".$Parcel->Printer.")";
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
      $result = Cargonizer::_createConsignment( $_POST['order_id'] );
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
}

new CargonizerAjax();
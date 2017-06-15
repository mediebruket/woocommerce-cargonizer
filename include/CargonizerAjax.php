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
    _log('CargonizerAjax::_createConsignment');

    $response = 0;
    if ( isset($_POST['order_id']) && is_numeric($_POST['order_id']) ){
      Cargonizer::_createConsignment( $_POST['order_id'] );
      $response = 1;
    }

    echo $response;
    wp_die();
  }


  function _printLatestConsignment(){
    _log('Cargonizer::_printLatestConsignment()');
    _log($_POST);

    $response = '0';
    if ( isset($_POST['order_id']) && is_numeric($_POST['order_id']) ){
      $Consignment = new Consignment( $_POST['order_id'] );

      $consignment_id = null;
      if ( isset($Consignment->History[0]['consignment_id']) ){
        $consignment_id = $Consignment->History[0]['consignment_id'];
      }

      _log('ConsignmentId: '.$consignment_id);
      _log('Printer: '. $Consignment->Printer);

      if ( !$consignment_id ){
        $response = 'Missing consignment id';
      }

      if ( !$Consignment->Printer ){
        $response = 'Missing printer_abort(printer_handle)';
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
}

new CargonizerAjax();
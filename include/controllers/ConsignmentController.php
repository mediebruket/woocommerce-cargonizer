<?php

class ConsignmentController extends CargonizerCommonController{
  protected $Order;
  protected $WC_Order;
  protected $Settings;

  function __construct( ){
    parent::__construct();

    add_action( 'save_post', array($this, 'preventRevision'), 10, 1 );
  }


  public static function createConsignment( $post_id  ){
    _log('ConsignmentController:: ('.$post_id.')');

    $response = false;
    if ( is_numeric($post_id) ){

      $Consignment = new Consignment( $post_id );
      $CargonizeXml = new CargonizeXml( $Consignment->prepareExport() );
      $CargonizerApi = new CargonizerApi();
      $result = null;
      _log('post consignment');


      $result = $CargonizerApi->postConsignment($CargonizeXml->Xml);

      if ( $result ){
        // _log($result);
        if ( is_array($result) && isset($result['consignments']['consignment']['errors']) ){
          _log('consignment: error');
          $response = $result['consignments']['consignment']['errors']['error'];
        }
        elseif ( is_array($result) && isset($result['consignments']['consignment']) ){
          _log('consignment: success');
          $response = $result;

          $Consignment->setNextShippingDate( $auto_inc=true );
          if ( $new_entry = $Consignment->updateHistory( $result['consignments']['consignment'] ) ){
            $Consignment->notifyCustomer( $new_entry );
          }

          // update order
          if ( $Consignment->OrderId ){
            _log('has order id');
            $Parcel = new ShopOrder( $Consignment->OrderId );
            $Parcel->setCargonized();
            $Parcel->saveConsignmentDetails( $consignment = $result['consignments']['consignment'] );
            $Parcel->addNote();
          }
          else{
            _log('no order id');
          }
        }
        else{
          _log('consignment: else error');
          _log($result);
        }
      }
      else{
        _log('no result');
      }
    }

    return $response;
  }


  function preventRevision($post_id){    
    $post = get_post($post_id);

    if ( wp_is_post_revision( $post_id ) or !$post->post_title ){
      return;  
    }
  }

  
  function acf_filterCustomerId( $field ){
    global $post_id;

    $customer_id = null;
    if ( $post_id  ){
      $Consignment = new Consignment($post_id);
      if ( $Consignment->CustomerId ){
        $customer_id = $Consignment->CustomerId;
      }
    }

    $field['message'] = str_replace('@acf_consignment_customer_id@', $customer_id, $field['message'] );

    return $field;
  }


  function acf_filterOrderProducts( $field ){
    global $post_id;

    if ( $post_id  ){
      $Consignment = new Consignment($post_id);

      $html = $rows = null;

      if ( is_array($Consignment->OrderProducts) && !empty($Consignment->OrderProducts) ){
        if ( isset($Consignment->Subscriptions) && $Consignment->Subscriptions ){
          foreach ( $Consignment->OrderProducts as $key => $product) {
            // _log('$product');
            // _log($product);
            // _log($log);
            $status = CargonizerIcons::ok();
            // _log($Consignment->Subscriptions);
            $post_status = str_replace('wc-', null, $Consignment->Subscriptions->post_status);

            if (
              // if has subscription product and subscription is not active
              $Consignment->isSubscriptionProduct($product['product_id']) && !$Consignment->isSubscriptionProductActive($product['product_id'])
              or
              $post_status != 'active' // if subscription has an end date

            ){
              $status = CargonizerIcons::warning();
            }

            $rows .= sprintf(
                '<tr><td>%s</td> <td>%s</td> <td>%s</td> <td>%s</td> <td >%s</td></tr>',
                $product['product_id'],
                $product['name'],
                $product['qty'],
                ( ( isset($product['is_subscription']) && $product['is_subscription'] ) ? 'yes' : 'no' ),
                $status. ' <a href="'.get_edit_post_link( $Consignment->Subscriptions->ID ).'" target="_blank">'.$post_status.'</a>'
            );
            // _log($log);
          }
        }
      }

      if ( $rows ){
        $th = '<tr> <th>%s</th> <th>%s</th> <th>%s</th> <th>%s</th> <th>%s</th>';
        $th = sprintf(
            $th,
            __('Id', 'wc-cargonizer'),
            __('Name', 'wc-cargonizer'),
            __('Count', 'wc-cargonizer'),
            __('Subscription', 'wc-cargonizer'),
            __('Status')
          );
        $html = '<table class="table">'. $th.$rows. '</table>';
      }

      $field['message'] = str_replace('@acf_consignment_products@', $html, $field['message'] );

    }


    return $field;
  }


  function acf_filterHistory($field){
    //_log('acf_filterHistory');
    global $post_id;

    $html = null;
    if ( $post_id ){
      $Consignment = new Consignment($post_id);

      $rows = null;

      if ( is_array($Consignment->History) ){
        foreach ( $Consignment->History as $key => $log) {
          // _log($log);
          $rows .= sprintf(
              '<tr><td>%s</td> <td>%s</td> <td>%s</td> <td><a href="%s" target="_blank">show</a></td> <td><a href="%s" target="_blank">download</a></td></tr>',
              $log['created_at'],
              $log['consignment_id'],
              $log['consignment_tracking_code'],
              strip_tags($log['consignment_tracking_url']),
              strip_tags($log['consignment_pdf'])
            );
          // _log($log);
        }
      }

      $head =  '<tr> <th>%s</th> <th>%s</th> <th>%s</th> <th>%s</th> <th>%s</th> </tr>';
      $head = sprintf(
          $head,
          __('Created at', 'wc-cargonizer'),
          __('Consignment id', 'wc-cargonizer'),
          __('Tracking code', 'wc-cargonizer'),
          __('Tracking url', 'wc-cargonizer'),
          __('PDF', 'wc-cargonizer')
        );
      $html = '<table class="table table-striped">'.$head.$rows.'</table>';

    }
    
    $field['message'] = str_replace('@acf_consignment_history@', $html, $field['message'] );

    return $field;
  }





  function acf_checkConsignmentStatus($field){
    if ( $post_id = $this->isOrder($object=false) ){
      if ( $is_cargonized = get_post_meta( $post_id, 'is_cargonized', true ) ){
        $field = null;
      }
    }

    return $field;
  }


  function acf_setCarrierProducts($field){
    // _log('Cargonizer::acf_setCarrierProducts');
    // _log($field);
    $choices = array();

    if ( $post_id = $this->isOrder($object=false) ){
      $CargonizerSettings = new CargonizerOptions();
      $ta = $CargonizerSettings->get('SelectedTransportAgreement');
      $ts = $CargonizerSettings->get('TransportProduct');

      if ( is_array($ta) && isset($ta['products']) && is_array($ta['products']) ){
        // _log($ta_settings);
        // _log('settings');
        foreach ($ta['products'] as $key => $p) {

          if ( isset($p['types']) && is_array($p['types']) ){
            foreach ($p['types'] as $ti => $type) {
              $index = $p['identifier']."|".$ti;

              if ( is_array($ts) ){
                if ( is_numeric(array_search($index, $ts)) ){
                  $choices[$index] = $p['name']." (".$type.")";
                }
              }
              elseif ( $index == $ts ){
                $choices[$index] = $p['name']." (".$type.")";
              }

            }
          }
          else{
            $choices[$key] = $p['name'];
          }
        }
      }
    }

    if ( $choices ){
      $field['choices'] = array_merge($field['choices'], $choices);
    }


    return $field;
  }



  function acf_setParcelTypes($field){
    // _log($field);
    $choices = array();

    if ( $post_id = $this->isOrder($object=false) ){
      $CargonizerSettings = new CargonizerOptions();
      $ta = $CargonizerSettings->get('SelectedTransportAgreement');
      $ts = $CargonizerSettings->get('TransportProduct');

      if ( is_array($ta) && isset($ta['products']) && is_array($ta['products']) ){
        // _log($ta_settings);
        // _log('settings');
        foreach ($ta['products'] as $key => $p) {
          if ( isset($p['types']) && is_array($p['types']) ){
            foreach ($p['types'] as $ti => $type) {
              $index = $p['identifier']."|".$ti;

              if ( is_array($ts) ){
                if ( is_numeric(array_search($index, $ts)) ){
                  $choices[$ti] = $p['name']." (".$type.")";
                }
              }
              else if ( $index == $ts ){
                $choices[$ti] = $p['name']." (".$type.")";
              }

            }
          }
          else{
            $choices[$key] = $p['name'];
          }
        }
      }

      if ( $choices ){
        $field['choices'] = array_merge($field['choices'], $choices);
      }
    }


    return $field;
  }


  function acf_setProductServices($field){
    // _log('acf_setProductServices');
    // _log($field);
    $choices = array();

    if ( $post_id = $this->isOrder($object=false) ){
      $CargonizerSettings = new CargonizerOptions();
      $ta = $CargonizerSettings->get('SelectedTransportAgreement');
      $ts = $CargonizerSettings->get('TransportProduct');

      if ( is_array($ta) && isset($ta['products']) && is_array($ta['products']) ){

        // _log('settings');
        foreach ( $ta['products'] as $key => $p) {
          if ( $key == 0 ){
            foreach ($p['services'] as $key => $s) {
              $choices[$s['identifier']] = $s['name'];
            }
          }
        }
      }
    }

    if ( !empty($choices) ){
      $field['choices'] = $choices;
      $field['default_value'] = 'bring_e_varsle_for_utlevering';
    }


    return $field;
  }


  function acf_setTransportAgreements($field){
    if ( $Consignment = $this->isConsignment() ){
      if (  $choices = $this->getTransportAgreementChoices() ){
        $field['choices'] = $choices;
      }
    }

    return $field;
  }



  function acf_setParcelPrinter($field){
    // _log('Cargonizer::acf_setParcelPrinter');
    // _log($this->Settings);
    if ( $printers = CargonizerOptions::getPrinterList() ){
      $field['choices'] = array();
      foreach ($printers as $printer_id => $printer_name) {
        $field['choices'][$printer_id] = $printer_name;
      }
    }

    if ( $default_printer =  $this->Settings->get('DefaultPrinter' ) ) {
      $field['default_value'] = $default_printer;
    }


    return $field;
  }

} // end of class

new ConsignmentController();

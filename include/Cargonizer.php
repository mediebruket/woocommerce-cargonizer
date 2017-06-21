<?php

class Cargonizer{
  protected $Order;
  protected $WC_Order;
  protected $Settings;

  function __construct( ){
    $this->Settings = new CargonizerOptions();

    add_action( 'save_post', array($this, 'saveConsignment'), 10, 1 );
    add_action( 'init',  array($this, 'resetConsignment') , 10, 2 );

    // add_filter('wc_shipment_tracking_get_providers', array($this, 'setCustomProvider') ); ???

    add_filter('acf/load_field/name=parcel_recurring_carrier_id', array($this, 'acf_setTransportAgreements'), 20 );
    add_filter('acf/load_field/name=consignment_carrier_id', array($this, 'acf_setTransportAgreements'), 30 );
    add_filter('acf/load_field/name=transport_agreement', array($this, 'acf_setTransportAgreements'), 40 );

    add_filter('acf/load_field/key=field_5940c85af6b7d', array($this, 'acf_filterHistory'), 40, 1 );
    add_filter('acf/load_field/key=field_59477f6eddf36', array($this, 'acf_filterOrderProducts'), 40, 1 );
    add_filter('acf/load_field/key=field_59477faf14b71', array($this, 'acf_filterCustomerId'), 40, 1 );

    add_filter('acf/load_field/name=parcel-recurring-consignment-interval', array($this, 'acf_setRecurringConsignmentInterval'), 40 );
    add_filter('acf/load_field/name=recurring_consignment_interval', array($this, 'acf_setRecurringConsignmentInterval'), 40 );

    add_filter('acf/load_field/name=parcel_printer', array($this, 'acf_setParcelPrinter'), 20 );
    add_filter('acf/load_field/name=parcel_type', array($this, 'acf_setCarrierProducts'), 10 );
    add_filter('acf/load_field/name=parcel_package_type', array($this, 'acf_setParcelTypes'), 10 );
    add_filter('acf/load_field/name=create_consignment', array($this, 'acf_checkConsignmentStatus'), 10 );

    add_filter('acf/load_field/name=parcel_services', array($this, 'acf_setProductServices') );

    add_filter('acf/load_field/name=parcel_height', array($this, 'acf_setDefaultHeight') );
    add_filter('acf/load_field/name=parcel_recurring_consignment_height', array($this, 'acf_setDefaultHeight') );

    add_filter('acf/load_field/name=parcel_length', array($this, 'acf_setDefaultLength') );
    add_filter('acf/load_field/name=parcel_recurring_length', array($this, 'acf_setDefaultLength') );

    add_filter('acf/load_field/name=parcel_width', array($this, 'acf_setDefaultWidth') );
    add_filter('acf/load_field/name=parcel_recurring_width', array($this, 'acf_setDefaultWidth') );

    add_filter( 'wp_mail_content_type', array($this, 'setMailContentType') );
  }


  function getTransportAgreementChoices(){

    $choices = array();
    $agreements = $this->Settings->get('TransportAgreements');
    if ( is_array($agreements) ){
      foreach ($agreements as $key => $a) {
        $choices[$a['id']] = $a['title'];
      }
    }

    return $choices;
  }


  function saveConsignment( $post_id ){
    if ( !isset($_REQUEST['post_ID']) or $_REQUEST['post_ID'] != $post_id ){
      return false;
    }

    if ( $Parcel = $this->isOrder(true) ){
      //_log($Parcel);

      $consignment_post_id = null;
      $is_future = ($Parcel->hasFutureShippingDate()) ? true : false;

      if ( $is_future ){
        $consignment_post_id = Consignment::createOrUpdate( $Parcel, $recurring=false );
      }

      if ( $Parcel->isReady($force=false) && !$is_future ){
        _log('Parcel is ready');
        if ( $cid = Consignment::createOrUpdate( $Parcel, $recurring=false ) ){
          _log('consignment created/updated: '.$cid);
          _log('create consignment now');
          $result = self::_createConsignment($cid);
        }
      }
      else{
        _log('not ready or has future shipping date');
      }

      if ( $Parcel->IsRecurring ){
        _log('create recurring');
        Consignment::createOrUpdate( $Parcel, $recurring=true );
      }
    }
  }





  function isOrder($object=true ){
    global $post;

    if ( isset($post->post_type) && $post->post_type == 'shop_order' ){
      if ( $object ){
        return new Parcel($post->ID);
      }
      else{
        return $post->ID;
      }

    }
    else{
      return null;
    }
  }


  function isConsignment($object=true ){
    global $post;

    if ( isset($post->post_type) && $post->post_type == 'consignment' ){
      if ( $object ){
        return new Consignment($post->ID);
      }
      else{
        return $post->ID;
      }

    }
    else{
      return null;
    }
  }


  function setCustomProvider($args){
    $args['Norway']['Cargonizer'] = 'tracking_url';
    ksort($args);

    return $args;
  }


  public static function _createConsignment( $post_id  ){
    _log('Cargonizer::_createConsignment('.$post_id.')');

    $response = false;
    if ( is_numeric($post_id) ){

      $Consignment = new Consignment( $post_id );
      $CargonizeXml = new CargonizeXml( $Consignment->prepareExport() );
      $CargonizerApi = new CargonizerApi();
      $result = true;
      // _log('post consignment');

      $result = $CargonizerApi->postConsignment($CargonizeXml->Xml);

      if ( $result ){
        // _log($result);
        if ( is_array($result) && isset($result['consignments']['consignment']['errors']) ){
          _log('error');
          $response = $result['consignments']['consignment']['errors']['error'];
        }
        elseif ( is_array($result) && isset($result['consignments']['consignment']) ){
          _log('success');
          $response = $result;

          $Consignment->setNextShippingDate( $auto_inc=true );
          if ( $new_entry = $Consignment->updateHistory( $result['consignments']['consignment'] ) ){
            $Consignment->notifyCustomer( $new_entry );
          }

          // update order
          if ( $Consignment->OrderId ){
            _log('has order id');
            $Parcel = new Parcel( $Consignment->OrderId );
            $Parcel->setCargonized();
            $Parcel->saveConsignmentDetails( $consignment = $result['consignments']['consignment'] );
            $Parcel->addNote();
          }
          else{
            _log('no order id');
          }
        }
        else{
          _log('else error');
          _log($result);
        }
      }
      else{
        _log('no result');
      }
    }

    return $response;
  }


  function resetConsignment(){
    if ( _is($_GET, 'wcc_action') == 'reset_consignment' ){

      $order_id = _is($_GET, 'post');
      if ( is_numeric($order_id) ){

        $Parcel = new Parcel($order_id);
        $Parcel->reset();
        $Parcel->addNote( 'reset' );

        if ( $location = get_edit_post_link($order_id) ){
          wp_redirect( str_replace('&amp;', '&', $location) );
          die();
        }
      }
    }
  }



   function setMailContentType(){
    return 'text/html';
  }


  function acf_filterCustomerId( $field ){
    global $post_id;

    $customer_id = null;
    if ( $post_id  ){
      $Consignment = new Consignment($post_id);
      _log('1');
      _log($Consignment->CustomerId);
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
            $post_status = null;

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
                ( ($product['is_subscription']) ? 'yes' : 'no' ),
                $status. ' <a href="'.get_edit_post_link( $Consignment->Subscriptions->ID ).'" target="_blank">'.$post_status.'</a>'
            );
            // _log($log);
          }
        }
      }

      if ( $rows ){
        $th = '<tr> <th>%s</th> <th>%s</th> <th>%s</th> <th>%s</th> <th>%s</th>';
        $th = sprintf( $th, __('Id', 'wc-cargonizer'), __('Name', 'wc-cargonizer'), __('Count', 'wc-cargonizer'), __('Subscription', 'wc-cargonizer'), __('Status') );
        $html = '<table class="table">'. $th.$rows. '</table>';
      }

      $field['message'] = str_replace('@acf_consignment_products@', $html, $field['message'] );

    }


    return $field;
  }


  function acf_filterHistory($field){
    global $post_id;

    if ( $post_id  ){
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

      $field['message'] = str_replace('@acf_consignment_history@', $html, $field['message'] );
    }

    return $field;
  }


  function acf_setDefaultHeight($field){
    if ( $value = get_option('cargonizer-parcel-height' ) ){
      $field['default_value'] = $value;
    }
    return $field;
  }


  function acf_setDefaultLength($field){
    if ( $value = get_option('cargonizer-parcel-length' ) ){
      $field['default_value'] = $value;
    }
    return $field;
  }


  function acf_setDefaultWidth($field){
    if ( $value = get_option('cargonizer-parcel-width' ) ){
      $field['default_value'] = $value;
    }
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
      $ts = $CargonizerSettings->get('TransportServices');

      if ( is_array($ta) && isset($ta['products']) && is_array($ta['products']) ){
        // _log($ta_settings);
        // _log('settings');
        foreach ($ta['products'] as $key => $p) {

          if ( isset($p['types']) && is_array($p['types']) ){
            foreach ($p['types'] as $ti => $type) {
              $index = $p['identifier']."|".$ti;

              if ( is_numeric(array_search($index, $ts)) ){
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


  function acf_setRecurringConsignmentInterval( $field ){
    for( $i=1; $i<=30; $i++ ){
      $field['choices'][$i] = sprintf( __('every %sth', 'wc-cargonizer'), $i );
    }
    return $field;
  }


  function acf_setParcelTypes($field){
    // _log($field);
    $choices = array();

    if ( $post_id = $this->isOrder($object=false) ){
      $CargonizerSettings = new CargonizerOptions();
      $ta = $CargonizerSettings->get('SelectedTransportAgreement');
      $ts = $CargonizerSettings->get('TransportServices');

      if ( is_array($ta) && isset($ta['products']) && is_array($ta['products']) ){
        // _log($ta_settings);
        // _log('settings');
        foreach ($ta['products'] as $key => $p) {
          if ( isset($p['types']) && is_array($p['types']) ){
            foreach ($p['types'] as $ti => $type) {
              $index = $p['identifier']."|".$ti;
              if ( is_numeric(array_search($index, $ts)) ){
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
      $ts = $CargonizerSettings->get('TransportServices');

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
    // _log('Cargonizer::acf_setTransportAgreements');
    // _log($field);
    if ( $Parcel = $this->isOrder() ){

      if (  $choices = $this->getTransportAgreementChoices() ){
        $field['choices'] = $choices;
      }

      if( is_numeric($Parcel->TransportAgreementId) && $Parcel->TransportAgreementId ){
        $field['default_value'] = $Parcel->TransportAgreementId ;
      }
      elseif ( $ta = $this->Settings->get('SelectedTransportAgreement' ) ){
        $field['default_value'] = $ta['id'];
      }
    }
    else if ( $Consignment = $this->isConsignment() ){
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

$Cargonizer = new Cargonizer();

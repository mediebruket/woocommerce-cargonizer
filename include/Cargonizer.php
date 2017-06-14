<?php

class Cargonizer{
  protected $Order;
  protected $WC_Order;
  protected $Settings;

  function __construct(){
    $this->Settings = new CargonizerOptions();

    add_action( 'wp_ajax_wcc_print_order', array( $this, '_printOrder' ) );
    add_action( 'wp_ajax_wcc_create_consignment', array( $this, '_createConsignment' ) );

    add_action( 'save_post', array($this, 'createConsignment'), 10, 1 );
    add_action( 'init',  array($this, 'resetConsignment') , 10, 2 );

    // add_filter('wc_shipment_tracking_get_providers', array($this, 'setCustomProvider') ); ???

    add_filter('acf/load_field/name=parcel_recurring_carrier_id', array($this, 'acf_setTransportAgreements'), 20 );
    add_filter('acf/load_field/name=consignment_carrier_id', array($this, 'acf_setTransportAgreements'), 30 );
    add_filter('acf/load_field/name=transport_agreement', array($this, 'acf_setTransportAgreements'), 40 );

    add_filter('acf/load_field/key=field_5940c85af6b7d', array($this, 'acf_filterHistory'), 40, 1 );

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


  function setMailContentType(){
    return 'text/html';
  }

  function acf_filterHistory($field){
    global $post_id;


    if ( $post_id  ){
      $Consignment = new Consignment($post_id);

      $history = null;

      if ( is_array($Consignment->History) ){
        foreach ($Consignment->History as $key => $row) {
          $log = maybe_unserialize($row );
          _log($log);
          $history .= sprintf(
              '<tr><td>%s</td> <td>%s</td> <td>%s</td> <td><a href="%s">show</a></td> <td><a href="%s">download</a></td></tr>',
              $log['created_at'],
              $log['consignment_id'],
              $log['consignment_tracking_code'],
              strip_tags($log['consignment_tracking_url']),
              strip_tags($log['consignment_pdf'])
            );
          _log($log);
        }
      }

      $field['message'] = str_replace('@acf_history@', $history, $field['message'] );

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


  function acf_setParcelPrinter($field){
    //_log('Cargonizer::acf_setParcelPrinter');

    if ( $Parcel = $this->isOrder() ){
      // _log($this->Settings);
      if ( $printers = CargonizerOptions::getPrinterList() ){
        $field['choices'] = array();
        foreach ($printers as $printer_id => $printer_name) {
          $field['choices'][$printer_id] = $printer_name;
        }
      }
    }

    if ( $default_printer =  $this->Settings->get('DefaultPrinter' ) ) {
      $field['default_value'] = $default_printer;
    }


    return $field;
  }


  function createConsignment( $post_id ){
    if ( !isset($_REQUEST['post_ID']) or $_REQUEST['post_ID'] != $post_id ){
      return false;
    }

    if ( $Parcel = $this->isOrder(true) ){
      //_log($Parcel);
      if ( $Parcel->isReady($force=false) ){
        _log('Parcel is ready');
        // send to queue
        if ( $Parcel->hasFutureShippingDate() ){
          _log('has future date');
          // TODO check if is not cargonized
          Consignment::createOrUpdate( $Parcel, $recurring=false );
        }
        else{ // create consignment now
          _log('create consignment now');
          $CargonizeXml = new CargonizeXml( $Parcel->prepareExport() );
          $CargonizerApi = new CargonizerApi();
          $result = null;

          // _log($CargonizeXml);
          // TO DO uncomment
          //$result = $CargonizerApi->postConsignment($CargonizeXml->Xml);

          if ( $result ){
            if ( is_array($result) && isset($result['consignments']['consignment']) ){
              update_post_meta( $Parcel->ID, 'is_cargonized', '1' );
              $Parcel->saveConsignment( $consignment = $result['consignments']['consignment'] );
              $Parcel->notiyCustomer();
              $this->addNote( $Parcel );
            }
          }
        }

      }
      else{
        _log('not ready');
      }

      // if ( $Parcel->IsRecurring ){
      //   _log('create recurring');
      //   Consignment::createOrUpdate( $Parcel, $recurring=true );
      // }
    }
  }


  function addNote( $Parcel, $type = 'exported' ){
    _log('Cargonizer::addNote('.$Parcel->ID.')');

    $data = array(
      'comment_post_ID'       => $Parcel->ID,
      'comment_author'        => 'WooCommerce Cargonizer',
      'comment_author_email'  => get_option('admin_email' ),
      'comment_content'       => sprintf( __('Cargonizer: Parcel %s', 'wc-cargonizer'), $type ),
      'comment_agent'         => 'WooCommerce Cargonizer',
      'comment_type'          => 'order_note',
      'comment_parent'        => 0,
      'user_id'               => 1,
      'comment_author_IP'     => 'null',
      'comment_date'          => current_time('mysql'),
      'comment_approved'      => 1,
    );

    if ( $type == 'exported' ){
      $data['comment_content'] = '<br/>'.sprintf( __('Consignment id: %s', 'wc-cargonizer'), get_post_meta( $Parcel->ID, 'consignment_id', true ) );
    }


    if ( wp_insert_comment($data) ){
      _log('new note added');
    }
    else{
      _log('error: wp_insert_comment');
      _log($data);
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

  function _createConsignment(){
    _log('Cargonizer::_createConsignment()');
    $response = '1';
    if ( isset($_POST['order_id']) && is_numeric($_POST['order_id']) ){
      _log('create new Consignment');
      $Consignment = new Consignment( $_POST['order_id'] );
      _log('prepare export');
      $export = $Consignment->prepareExport();
      _log($export);
      $CargonizeXml = new CargonizeXml( $export );
      $CargonizerApi = new CargonizerApi();
      $result = true;
      _log('to post to api');

      //$result = $CargonizerApi->postConsignment($CargonizeXml->Xml);
      if ( $result ){
        // if ( is_array($result) && isset($result['consignments']['consignment']) ){
          // update next shipping date
          $Consignment->updateHistory();
          // update history( $result['consignments']['consignment'] );
          // notiyCustomer();
        // }
      }
    }

    echo $response;
    wp_die();
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


  function resetConsignment(){
    if ( _is($_GET, 'wcc_action') == 'reset_consignment' ){

      $order_id = _is($_GET, 'post');
      if ( is_numeric($order_id) ){

        //delete_post_meta( $order_id, $meta_key, $meta_value );
        $Parcel = new Parcel($order_id);
        $Parcel->reset();
        $this->addNote( $Parcel, 'reset' );

        if ( $location = get_edit_post_link($order_id) ){
          wp_redirect( str_replace('&amp;', '&', $location) );
          die();
        }
      }
    }
  }



} // end of class

$Cargonizer = new Cargonizer();

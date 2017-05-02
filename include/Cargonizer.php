<?php

class Cargonizer{
  protected $Order;
  protected $WC_Order;
  protected $Settings;

  function __construct(){
    $this->Settings = new CargonizerOptions();

    add_action( 'wp_ajax_wcc_print_order', array( $this, 'printOrder' ) );
    add_action( 'save_post', array($this, 'createConsignment') );

    // add_filter('wc_shipment_tracking_get_providers', array($this, 'setCustomProvider') );
    add_filter('acf/load_field/name=transport_agreement', array($this, 'acf_setTransportAgreements'), 20 );
    add_filter('acf/load_field/name=parcel_printer', array($this, 'acf_setParcelPrinter'), 20 );
    add_filter('acf/load_field/name=parcel_type', array($this, 'acf_setCarrierProducts'), 10 );
    add_filter('acf/load_field/name=parcel_package_type', array($this, 'acf_setParcelTypes'), 10 );
    add_filter('acf/load_field/name=create_consignment', array($this, 'acf_checkConsignmentStatus'), 10 );
    add_filter('acf/load_field/name=parcel_services', array($this, 'acf_setProductServices') );
    add_filter('acf/load_field/name=parcel_height', array($this, 'acf_setDefaultHeight') );
    add_filter('acf/load_field/name=parcel_length', array($this, 'acf_setDefaultLength') );
    add_filter('acf/load_field/name=parcel_width', array($this, 'acf_setDefaultWidth') );
    add_filter( 'wp_mail_content_type', array($this, 'setMailContentType') );
  }


  function setMailContentType(){
    return 'text/html';
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
    }


    if ( $choices ){
      $field['choices'] = array_merge($field['choices'], $choices);
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
    if ( $Parcel = $this->isOrder() ){
      // _log($this->Settings);
      if ( $ta = $this->Settings->get('SelectedTransportAgreement' ) ){
        $field['choices'] = array();
        $field['choices'][$ta['id']] = $ta['title'];
      }
    }

    return $field;
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


  function createConsignment(){
    if ( $Parcel = $this->isOrder() ){

      if ( $Parcel->isReady($force=true) ){
        $CargonizeXml = new CargonizeXml( $Parcel->prepareExport() );
        $CargonizerApi = new CargonizerApi();
        $result = null;

        // _log($CargonizeXml);
        $result = $CargonizerApi->postConsignment($CargonizeXml->Xml);

        if ( $result ){
          if ( is_array($result) && isset($result['consignments']['consignment']) ){
            update_post_meta( $Parcel->ID, 'is_cargonized', '1' );
            $Parcel->saveConsignment( $consignment = $result['consignments']['consignment']);
            $Parcel->notiyCustomer();
            $this->addNote( $Parcel );
          }
        }
      }
    }
  }


  function addNote( $Parcel, $tracking_url = null ){
    _log('Cargonizer::addNote('.$Parcel->ID.')');

    $data = array(
      'comment_post_ID'       => $Parcel->ID,
      'comment_author'        => 'WooCommerce Cargonizer',
      'comment_author_email'  => get_option('admin_email' ),
      'comment_content'       => sprintf( __('Parcel exported to Cargonizer.<br/>Consignment id: %s', 'wc-cargonizer'), $Parcel->Meta['consignment_id'][0] ),
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


  function isOrder($object=true){
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


  function setCustomProvider($args){
    $args['Norway']['Cargonizer'] = 'tracking_url';
    ksort($args);

    return $args;
  }


  function printOrder(){
    _log('Cargonizer::printOrder');
    _log($_POST);

    if ( isset($_POST['order_id']) && is_numeric($_POST['order_id']) ){
      $Parcel = new Parcel( $_POST['order_id'] );

      _log($Parcel->ConsignmentId);
      _log($Parcel->Printer);

      if ( $Parcel->ConsignmentId && $Parcel->Printer ){
        _log('print..');
        $Api = new CargonizerApi();
        $Api->postLabel( $Parcel->ConsignmentId, $Parcel->Printer );
      }

    }
    echo '1';
    wp_die();
  }



} // end of class

$Cargonizer = new Cargonizer();

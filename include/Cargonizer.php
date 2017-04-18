<?php

class Cargonizer{
  protected $Order;
  protected $WC_Order;
  protected $Settings;

  function __construct(){
    $this->Settings = new CargonizerOptions();

    add_action( 'save_post', array($this, 'createConsignment') );
    // add_filter('wc_shipment_tracking_get_providers', array($this, 'setCustomProvider') );
    add_filter('acf/load_field/name=transport_agreement', array($this, 'acf_setTransportAgreements'), 20 );
    add_filter('acf/load_field/name=parcel_type', array($this, 'acf_setCarrierProducts'), 10 );
    add_filter('acf/load_field/name=create_consignment', array($this, 'acf_checkConsignmentStatus'), 10 );
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

    _log('$choices');
    _log($load_field);

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


  function createConsignment(){
    if ( $Parcel = $this->isOrder() ){

      if ( $Parcel->isReady() ){
        $CargonizeXml = new CargonizeXml( $Parcel->prepareExport() );
        $CargonizerApi = new CargonizerApi();
        $result = null;

        $result = $CargonizerApi->postConsignment($CargonizeXml->Xml);
        if ( $result ){
          update_post_meta( $Parcel->ID, 'is_cargonized', '1' );
          $consignment = $result['consignments']['consignment'];
          // _log($consignment);
          acf_updateField('consignment_created_at', $consignment['created-at']['$'], $Parcel->ID);
          acf_updateField('consignment_id', $consignment['bundles']['bundle']['consignment-id']['$'], $Parcel->ID);
          acf_updateField('consignment_tracking_code', $consignment['number'], $Parcel->ID);
          acf_updateField('consignment_tracking_url', $consignment['tracking-url'], $Parcel->ID);
          acf_updateField('consignment_pdf', $consignment['consignment-pdf'], $Parcel->ID);

          $Parcel->notiyCustomer();
        }
      }
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



}

$Cargonizer = new Cargonizer();

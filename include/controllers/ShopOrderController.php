<?php

class ShopOrderController extends CargonizerCommonController{
  protected $Order;
  protected $WC_Order;
  protected $Settings;

  function __construct( ){
    parent::__construct();

    add_action( 'save_post', array($this, 'saveConsignmentSettgings'), 10, 1 );
    // add_action( 'save_post', array($this, 'saveConsignment'), 20, 1 );
    add_action( 'init',  array($this, 'resetConsignment') , 10, 2 );

    // transport agreements ...
    // ... default parcel
    add_filter('acf/load_field/name=parcel_recurring_carrier_id', array($this, 'acf_setTransportAgreements'), 20 );
    // ... and recurring parcel
    add_filter('acf/load_field/name=transport_agreement', array($this, 'acf_setTransportAgreements'), 40 );

    add_filter('acf/load_field/name=parcel_auto_transfer', array($this, 'acf_setParcelAutoTransfer'), 20 );


    add_filter('acf/load_field/name=parcel_type', array($this, 'acf_setCarrierProducts'), 10 );
    add_filter('acf/load_field/name=parcel_package_type', array($this, 'acf_setParcelTypes'), 10 );
    add_filter('acf/load_field/name=create_consignment', array($this, 'acf_checkConsignmentStatus'), 10 );

    add_filter('acf/load_field/name=parcel_services', array($this, 'acf_setProductServices') );

    add_filter('acf/load_field/name=message_consignee', array($this, 'acf_setDefaultMessageToConsignee') );

    add_filter('acf/load_field/name=parcel_height', array($this, 'acf_setDefaultHeight') );
    add_filter('acf/load_field/name=parcel_recurring_consignment_height', array($this, 'acf_setDefaultHeight') );

    add_filter('acf/load_field/name=parcel_length', array($this, 'acf_setDefaultLength') );
    add_filter('acf/load_field/name=parcel_recurring_consignment_length', array($this, 'acf_setDefaultLength') );

    add_filter('acf/load_field/name=parcel_weight', array($this, 'acf_setDefaultWeight') );

    add_filter('acf/load_field/name=parcel_width', array($this, 'acf_setDefaultWidth') );
    add_filter('acf/load_field/name=parcel_recurring_consignment_width', array($this, 'acf_setDefaultWidth') );


    // CargonizerCommonController
    add_filter('acf/load_field/name=parcel-recurring-consignment-interval', array($this, 'acf_setRecurringConsignmentInterval'), 40 );
    add_filter('acf/load_field/name=parcel_print_on_post', array($this, 'acf_setParcelPrintOnPost'), 20 );
  }


  function saveConsignmentSettgings( $post_id ){
    _log('saveConsignmentSettgings');
    _log($_REQUEST);
    if ( _is($_REQUEST, 'post_ID') == $post_id ){
      $AdminShopOptions = new AdminShopOptions();
      $options = $AdminShopOptions->loadParcelOptions();

      foreach ( $options as $key => $o ) {
        $index = $o['name'];
        _log('save '.$index);
        $meta_value = ( isset($_POST[$index]) ) ? $_POST[$index] : null;
        if ( update_post_meta( $post_id, $index, maybe_serialize($meta_value) ) ){
          _log('success ');
        }

      }

    }
  }

  function saveConsignment( $post_id ){
    if ( !isset($_REQUEST['post_ID']) or $_REQUEST['post_ID'] != $post_id ){
      return false;
    }

    if ( $Order = $this->isOrder( $return_object=true ) ){
      //_log($Order);
      $consignment_post_id = null;
      $is_future = ($Order->hasFutureShippingDate()) ? true : false;

      if ( $is_future ){
        $consignment_post_id = Consignment::createOrUpdate( $Order, $recurring=false );
      }

      if ( $Order->isReady($force=false) && !$is_future ){
        _log('Parcel is ready');
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
        $Order->addNote( 'reset' );

        if ( $location = get_edit_post_link($order_id) ){
          wp_redirect( str_replace('&amp;', '&', $location) );
          die();
        }
      }
    }
  }


  function acf_setDefaultMessageToConsignee( $field ){
    $placeholders = array('order_id' => null );
    $default_value = null;
    if ( $default_value = get_option('cargonizer-parcel-message-consignee' ) ){
      $placeholders['order_id'] = ( isset($_GET['post']) && is_numeric($_GET['post']) ) ? $_GET['post'] : null;
      foreach ($placeholders as $key => $value) {
        $default_value = str_replace('@'.$key.'@', $value, $default_value);
      }
    }

    $field['default_value'] = $default_value;

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
    // _log('Cargonizer::acf_setTransportAgreements');
    // _log($field);
    if ( $Order = $this->isOrder() ){
      if ( $choices = $this->getTransportAgreementChoices() ){
        $field['choices'] = $choices;
      }

      if( is_numeric($Order->TransportAgreementId) && $Order->TransportAgreementId ){
        $field['default_value'] = $Order->TransportAgreementId ;
      }
      elseif ( $ta = $this->Settings->get('SelectedTransportAgreement' ) ){
        $field['default_value'] = $ta['id'];
      }
    }


    return $field;
  }




} // end of class

new ShopOrderController();

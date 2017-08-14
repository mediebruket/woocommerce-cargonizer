<?php

class CargonizerOptions{
  protected $TransportAgreements;
  protected $SelectedTransportAgreement;
  protected $TransportCompanyId;
  protected $TransportProduct;
  protected $TransportServices;
  protected $DefaultPrinter;
  protected $RecurringConsignmentWarningTime;


  function __construct(){
    $this->TransportCompanyId         = $this->getTransportCompanyId();
    $this->TransportProduct           = $this->getTransportProduct();
    $this->TransportServices          = $this->getTransportServices();
    $this->DefaultPrinter             = $this->getDefaultPrinter();
    $this->PrintOnExport              = $this->getPrintOnExport();
    $this->TransportAgreements        = $this->getTransportAgreements();
    $this->SelectedTransportAgreement = $this->getSelectedTransportAgreement();
    $this->TransportAgreementServices = $this->getSelectedTransportAgreementServices();
    $this->RecurringConsignmentWarningTime = $this->getRecurringConsignmentWarningTime();
  }


  function init(){
    $this->__construct();
  }


  function get($Attribute){
    return $this->$Attribute;
  }


  function set($Attribute, $value){
    $this->$Attribute = $value;
  }


  function updateOptions( $type ){
    $method = 'load'.$type."Options";

    foreach ( $this->$method() as $key => $option){
      if ( isset($_POST[ $option['name'] ]) ){
        $post_value = $_POST[ $option['name'] ];

        if ( is_string($post_value) ){
          $post_value = trim( $post_value );
        }

        update_option( $option['name'], $post_value );
      }
      else{
        update_option( $option['name'], null );
      }
    }

    $this->init();
  }


  function getOptions( $type ){
    $method = 'load'.$type.'Options';
    ob_start();
    foreach ( $this->$method() as $key => $option):
    ?>
      <div class="mb-field-row"><?php CargonizerHtmlBuilder::buildOption( $option ); ?></div>
    <?php

    endforeach;
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
  }


  function getRecurringConsignmentWarningTime(){
    return get_option( 'cargonizer-recurring-consignments-warning-time', '1' );
  }

  function getTransportCompanyId(){
    return get_option('cargonizer-carrier-id');
  }


  function getDefaultPrinter(){
    return get_option('cargonizer-default-printer');
  }


  public static function getDefaultCarrierProduct(){
    $carrier_products = get_option( 'cargonizer-carrier-products' );
    $product_identifier = null;
    if ( is_string($carrier_products) ){
      $product_identifier = $carrier_products;
    }
    elseif ( is_array($carrier_products) && isset($carrier_products[0]) && $carrier_products[0] ){
      $product_identifier = $carrier_products[0];
    }

    return $product_identifier;
  }


  function getPrintOnExport(){
    return get_option('cargonizer-print-on-export');
  }


  function getTransportProduct(){
    $carrier_product = maybe_unserialize( get_option('cargonizer-carrier-products') );
    if ( is_string($carrier_product) ){
      return $carrier_product;
    }
    else if( is_array($carrier_product) && isset($carrier_product[0]) ){
      return $carrier_product[0];
    }
    else{
      return null;
    }
  }


  function getTransportServices(){
    return maybe_unserialize( get_option('cargonizer-delivery-carrier-product-services') );
  }


  function getSelectedTransportAgreement(){
    $selected_transport_agreement = null;

    if ( $this->TransportCompanyId && is_array($this->TransportAgreements) && !empty($this->TransportAgreements) ){
      foreach ($this->TransportAgreements as $key => $ta) {
        if ( $ta['id'] == $this->TransportCompanyId ){
          $selected_transport_agreement = $ta;
          break;
        }
      }
    }

    // _log($selected_transport_agreement);
    return $selected_transport_agreement;
  }


  function getSelectedTransportAgreementServices(){
    $services = array();
    if ( $this->TransportProduct ){
      $tp = explode('|', $this->TransportProduct );
      if ( $this->SelectedTransportAgreement && is_array($this->SelectedTransportAgreement) && isset($this->SelectedTransportAgreement['products']) ){
        foreach ($this->SelectedTransportAgreement['products'] as $key => $product) {
          if ( $product['identifier'] == $tp[0] ){
            if ( is_array($product['services']) ){
              foreach ($product['services'] as $name => $s){
                $services[ $s['identifier']] = $s['name'];
              }
            }
            break;
          }
        }
      }
    }

    // _log($services);

    return $services;
  }


  function getTransportAgreements($force_update=false){
    //_log('CargonizerOptions::getTransportAgreements()');
    $transport_agreements = get_transient('transport_agreements');

    if ( !$transport_agreements or $force_update ){
      // _log('update transient');
      $Api = new CargonizerApi(true);
      if ( $ta = $this->sanitizeTransportAgreements( $Api->TransportAgreements['transport-agreements']['transport-agreement'] ) ){
         $this->saveTransportAgreements( $ta );
         $transport_agreements = $ta;
      }
    }

    return $transport_agreements;
  }


  function sanitizeTransportAgreements($array){
    $transport_agreements = null;
    // _log('CargonizerSettings::sanitizeTransportAgreements()');

    if ( is_array($array) ){
      foreach ($array as $key => $value) {
        // _log($value);
        if ( isset($value['carrier']['identifier']) && isset($value['id']['$']) ){
          // _log($value['carrier']['identifier']);

          // set carrier
          $carrier = array(
            'id'          => $value['id']['$'],
            'identifier'  => $value['carrier']['identifier'],
            'name'        => $value['carrier']['name'],
            'desc'        => $value['description'],
            'title'       => $value['carrier']['identifier'].' ('.$value['description'] .')'
          );


          // set products
          $products = array();
          if ( is_array($value['products']['product']) ){
            foreach ( $value['products']['product']  as $key => $product) {
                // _log($key);
                // _log($product);

              $types = array();
              if ( isset($product['item_types']['item_type']) && is_array($product['item_types']['item_type']) ){
                foreach ($product['item_types']['item_type'] as $index => $type){
                  if ( $abbreviation = gi($type, '@abbreviation' ) ){
                    $types[ $type['@abbreviation'] ] = $type['@name_no'];
                  }
                }
              }

              $services = array();
              if ( isset($product['services']) && is_array($product['services']['service']) ){

                foreach ( $product['services']['service'] as $key => $service ) {

                  if ( isset($service['name']) && isset($service['identifier']) ){
                    // _log( $service['name']. " ". $service['identifier']);
                    $services[] =
                      array(
                        'name' => $service['name'],
                        'identifier' => $service['identifier']
                      );
                  }
                  // else{
                  //   _log('empty service');
                  //   _log($service);
                  // }

                }
              }
              else{
                //_log('no services: '.$product['name']);
              }


              if ( !empty($types) ){
                $products[] = array(
                  'name'        => $product['name'],
                  'identifier'  => $product['identifier'],
                  'types'       => $types,
                  'services'       => $services,
                  );
              }
            }
          }


          // add carrier if has products
          if ( $products ){
            $carrier['products'] = $products;
            $transport_agreements[] = $carrier;
          }

        }
      }
    }

    return $transport_agreements;
    // _log($this->TransportAgreements);
  }


  function saveTransportAgreements(  $transport_agreements ){
    // _log('CargonizerSettings::saveTransportAgreements');
    // _log($this->TransportAgreements);
    set_transient( 'transport_agreements', $transport_agreements, 1*60*60 );
  }


  function getCompanyList(){
    $companies = array();
    $this->getTransportAgreements();

    if ( $ta = get_transient( 'transport_agreements' ) ){
      foreach ($ta as $key => $row) {
        $companies[ $row['id'] ] = $row['title'];
      }
    }


    return $companies;
  }


  public static function getPrinterList(){
    //_log('CargonizerOptions::getPrinterList()');
    $array = array();
    $transient = 'wcc_printer_list';

    if ( $ta = get_transient( $transient ) ){
      // _log('has printer transient');
      foreach ($ta as $printer_id => $printer_name) {
        $array[ $printer_id ] = $printer_name;
      }
    }
    else{
      // _log('get printers from cargonizer');
      $Api = new CargonizerApi();
      $printers = $Api->getPrinters();


      $array = array();
      if ( isset($printers['printers']) && is_array($printers['printers']) && !empty($printers['printers']) ){
        foreach ($printers['printers'] as $key => $p) {
          if (  isset($p['id']) ){
            $array[ $p['id']['$'] ] = $p['name'] ;
          }
        }
      }

      if ( !empty($array) ){
        set_transient( $transient, $array, 1*60*60 );
      }
    }

    return $array;
  }


  function loadLicenceOptions(){
    return array(
       array(
        'name'  => 'woocomerce-cargonizer_licence_key',
        'label' => __('Licence key'),
        'type'  => 'text',
        'value' => get_option('cargonizer-licence-key'),
        'css'   => 'licence-key',
      )
    );
	}


  /* api options */
  function loadApiOptions(){
    return array(
      array(
        'name' => 'cargonizer-api-key',
        'label' => __('Api key'),
        'type' => 'text',
        'value' => get_option('cargonizer-api-key'),
      ),
      array(
        'name' => 'cargonizer-api-sender',
        'label' => __('Api sender'),
        'type' => 'text',
        'value' => get_option('cargonizer-api-sender'),
      ),
      array(
        'name' => 'cargonizer-sandbox-modus',
        'label' => __('Sandbox'),
        'type' => 'checkbox',
        'value' => get_option('cargonizer-sandbox-modus'),
        'option' => 'on'
      ),
      array(
        'name' => 'cargonizer-sandbox-api-key',
        'label' => __('Sandbox api key'),
        'type' => 'text',
        'value' => get_option('cargonizer-sandbox-api-key'),
      ),
      array(
        'name' => 'cargonizer-sandbox-api-sender',
        'label' => __('Sandbox api sender'),
        'type' => 'text',
        'value' => get_option('cargonizer-sandbox-api-sender'),
      ),
    );
  }


  function loadGeneralOptions(){
    $options =
      array(
        array(
          'name'    => 'cargonizer-default-printer',
          'label'   => __('Default printer'),
          //'desc' => __('Api settings required to load delivery companies'),
          'type'    => 'select',
          'value'   => $this->DefaultPrinter,
          'options' => self::getPrinterList(),
        ),
        array(
          'name'    => 'cargonizer-print-on-export',
          'label'   => __('Print on export'),
          //'desc' => __('Api settings required to load delivery companies'),
          'type'    => 'checkbox',
          'value'   => $this->PrintOnExport,
          'option'   => 'on',
        ),
        array(
          'name'    => 'cargonizer-carrier-id',
          'label'   => __('Default delivery company'),
          'desc'    => __('Api settings required to load delivery companies'),
          'type'    => 'select',
          'value'   => $this->TransportCompanyId,
          'options' => $this->getCompanyList(),
        ),
        array(
          'name'    => 'cargonizer-carrier-products',
          'label'   => __('Default product'),
          'desc'    => __('If empty, select delivery company and update'),
          'type'    => 'multiple_checkbox_2',
          'value'   => $this->TransportProduct,
          'options' => ( $this->SelectedTransportAgreement ) ? $this->SelectedTransportAgreement['products'] : array(),
        ),
        array(
          'name'    => 'cargonizer-delivery-carrier-product-services',
          'label'   => __('Default services'),
          // 'desc' => __('Api settings required to load delivery companies'),
          'type'    => 'multiple_checkbox',
          'value'   =>  $this->TransportServices,
          'options'   => $this->TransportAgreementServices
        ),
      );

     //_log($options);

    return $options;
  }


  function loadParcelOptions(){
    return array(
      array(
        'name' => 'cargonizer-parcel-height',
        'label' => __('Height&nbsp;(cm)'),
        'type' => 'text',
        'value' => get_option('cargonizer-parcel-height'),
      ),
      array(
        'name' => 'cargonizer-parcel-length',
        'label' => __('Length&nbsp;(cm)'),
        'type' => 'text',
        'value' => get_option('cargonizer-parcel-length'),
      ),
      array(
        'name' => 'cargonizer-parcel-width',
        'label' => __('Width&nbsp;(cm)'),
        'type' => 'text',
        'value' => get_option('cargonizer-parcel-width'),
      ),
      array(
        'name' => 'cargonizer-parcel-message-consignee',
        'label' => __('Message consignee'),
        'desc' => 'placeholders: @order_id@',
        'type' => 'text',
        'value' => get_option('cargonizer-parcel-message-consignee'),
      ),
      array(
        'name'    => 'cargonizer-recurring-consignments-warning-time',
        'label'   => __('Recurring consignments warning time'),
        'desc' => __('Default 1 day'),
        'type'    => 'text',
        'value'   => $this->RecurringConsignmentWarningTime,
      ),
      array(
        'name' => 'cargonizer-estimate-shipping-costs',
        'label' => __('Estimate shipping costs?'),
        'type' => 'checkbox',
        'value' => get_option('cargonizer-estimate-shipping-costs'),
        'option' => 'on'
      ),
      array(
        'name' => 'cargonizer-auto-transfer',
        'label' => __('Transfer automatically to carrier?'),
        'type' => 'checkbox',
        'value' => get_option('cargonizer-auto-transfer'),
        'option' => 'on'
      ),

    );
  }


  function loadNotificationOptions(){
    return array(
      array(
        'name' => 'cargonizer-customer-notification-subject',
        'label' => __('Subject'),
        'desc' => __('i.e. "Order @order_id@ at @shop_name@ is sent', 'wc-cargonizer' ),
        'type' => 'text',
        'value' => get_option('cargonizer-customer-notification-subject'),
      ),
      array(
        'name' => 'cargonizer-customer-notification-message',
        'label' => __('Message'),
        'desc' => __('E-Mail notification to customer after export to Cargonizer'),
        'type' => 'textarea',
        'value' => get_option('cargonizer-customer-notification-message'),
      ),
    );
  }


  function loadAddressOptions(){

    return array(
      array(
        'name' => 'cargonizer-return-address-name',
        'label' => __('Name'),
        'type' => 'text',
        'value' => get_option('cargonizer-return-address-name'),
      ),

      array(
        'name' => 'cargonizer-return-address-country',
        'label' => __('Country'),
        'type' => 'text',
        'value' => get_option('cargonizer-return-address-country'),
      ),

      array(
        'name' => 'cargonizer-return-address-postcode',
        'label' => __('Postcode'),
        'type' => 'text',
        'value' => get_option('cargonizer-return-address-postcode'),
      ),

      array(
        'name' => 'cargonizer-return-address-city',
        'label' => __('City'),
        'type' => 'text',
        'value' => get_option('cargonizer-return-address-city'),
      ),

      array(
        'name' => 'cargonizer-return-address-address1',
        'label' => __('Address'),
        'type' => 'text',
        'value' => get_option('cargonizer-return-address-address1'),
      ),

    );
  }


} // end of class
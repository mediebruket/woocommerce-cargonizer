<?php

class CargonizerOptions{
  protected $TransportAgreements;
  protected $SelectedTransportAgreement;
  protected $TransportCompanyId;
  protected $TransportServices;
  protected $DefaultPrinter;


  function __construct(){
    $this->setTransportCompanyId();
    $this->setTransportServices();
    $this->getTransportAgreements();
    $this->setDefaultPrinter();
    $this->setPrintOnExport();
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


  function setTransportCompanyId(){
    $this->TransportCompanyId = get_option('cargonizer-delivery-company-id');
  }


  function setDefaultPrinter(){
    $this->DefaultPrinter = get_option('cargonizer-default-printer');
  }

  function setPrintOnExport(){
    $this->PrintOnExport = get_option('cargonizer-print-on-export');
  }


  function setTransportServices(){
    $this->TransportServices = maybe_unserialize( get_option('cargonizer-delivery-services') );
  }


  function getTransportAgreements($force_update=false){
    // _log('CargonizerSettings::getTransportAgreements()');
    $transport_agreements = get_transient('transport_agreements');

    if ( $transport_agreements && !$force_update ){
      $this->TransportAgreements = $transport_agreements;
    }
    else{
      _log('update transient');
      $Api = new CargonizerApi(true);
      $this->setTransportAgreements( $Api->TransportAgreements['transport-agreements']['transport-agreement'] );
    }

    // _log($this->TransportCompanyId);
    // _log($this->TransportAgreements);

    if ( $this->TransportCompanyId && is_array($this->TransportAgreements) && !empty($this->TransportAgreements) ){
      foreach ($this->TransportAgreements as $key => $ta) {
        if ( $ta['id'] == $this->TransportCompanyId ){
          $this->SelectedTransportAgreement = $ta;
          break;
        }
      }
    }

    //_log($this->SelectedTransportAgreement);
  }


  function setTransportAgreements($array){
    // _log('CargonizerSettings::setTransportAgreements()');

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
            $this->TransportAgreements[] = $carrier;
          }

        }
      }

      $this->saveTransportAgreements();
    }

    // _log($this->TransportAgreements);
  }


  function saveTransportAgreements(){
    // _log('CargonizerSettings::saveTransportAgreements');
    // _log($this->TransportAgreements);
    set_transient( 'transport_agreements', $this->TransportAgreements, 1*60*60 );
  }



  public static function getCompanyList(){
    $companies = array();
    if ( $ta = get_transient( 'transport_agreements' ) ){
      foreach ($ta as $key => $row) {
        $companies[ $row['id'] ] = $row['title'];
      }
    }

    return $companies;
  }


  public static function getPrinterList(){
    _log('CargonizerOptions::getPrinterList()');
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


  public static function licenceSettings(){
    global $licence_settings;

    foreach ($licence_settings as $key => $value) {
    ?>
    <div>
    <label class="mb-admin-label" for="<?php echo $key; ?>"><?php echo $value; ?></label>
    <input type="input" class="licence-key" name="<?php echo $key; ?>" id="<?php echo $key; ?>" value="<?php echo get_option($key); ?>" size="50" >
    </div>

    <?php
    }
  		global $plugin_file;
  		$Plugin = new MB_Updater($plugin_file);
  		$valid = $Plugin->checkLicenceKey();
  		$color = ( $valid  == '1' ) ? '#009933' : '#cc0000';
  		set_transient( $Plugin->Slug.'_last_check', $valid, 0 );
  		?>

  		<style type="text/css">.licence-key{border:2px solid <?php echo $color; ?>}</style>
  		<?php
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


  function getApiSettings(){
    foreach ( $this->loadApiOptions() as $key => $option){
      $this->showOption($option);
    }
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


  /* general options */

  function loadGeneralOptions(){
    // _log($this->TransportServices);
    // $this->TransportAgreements;
    // $this->SelectedTransportAgreement;
    // $this->TransportServices;

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
          'name'    => 'cargonizer-delivery-company-id',
          'label'   => __('Default delivery company'),
          'desc'    => __('Api settings required to load delivery companies'),
          'type'    => 'select',
          'value'   => $this->TransportCompanyId,
          'options' => self::getCompanyList(),
        ),

        array(
          'name'    => 'cargonizer-delivery-services',
          'label'   => __('Products'),
          'desc'    => __('If empty, select delivery company and update'),
          'type'    => 'multiple_checkbox',
          'value'   => $this->TransportServices,
          'options' => ( $this->SelectedTransportAgreement ) ? $this->SelectedTransportAgreement['products'] : array(),
        ),
      );

     //_log($options);

    return $options;
  }


  function getGeneralSettings(){
    foreach ( $this->loadGeneralOptions() as $key => $option): ?>
      <div class="mb-field-row"><?php $this->showOption( $option ); ?></div>
    <?php endforeach;
  }


  /* parcel options */
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
    );
  }


  function getParcelOptions(){
    foreach ( $this->loadParcelOptions() as $key => $option){
      $this->showOption($option);
    }
  }

   /* address options */

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


  function getNotificationOptions(){
    foreach ( $this->loadNotificationOptions() as $key => $option){
      $this->showOption($option);
    }
  }



   /* address options */

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


  function getAddressOptions(){
    foreach ( self::loadAddressOptions() as $key => $option){
      $this->showOption($option);
    }
  }


  function showOption( $option, $default_value=null ){?>
    <div class="mb-field-row">

      <?php if( $option['type'] == 'text'): ?>
        <label class="mb-admin-label inline" for="<?php echo $option['name']; ?>"><?php echo $option['label']; ?></label>
        <?php if (isset($option['desc']) && trim($option['desc']) ): ?>
          <div><span class="mb-field-desc"><?php echo $option['desc']; ?></span></div>
        <?php endif ;?>
        <input type="<?php echo $option['type']; ?>" name="<?php echo $option['name']; ?>" id="<?php echo $option['name']; ?>" value="<?php echo $option['value']; ?>" size="50" >
      <?php endif; ?>

       <?php if( $option['type'] == 'checkbox'): ?>
        <label class="mb-admin-label inline" for="<?php echo $option['name']; ?>"><?php echo $option['label']; ?></label>
        <?php if (isset($option['desc']) && trim($option['desc']) ): ?>
          <div><span class="mb-field-desc"><?php echo $option['desc']; ?></span></div>
        <?php endif ;?>
        <input type="<?php echo $option['type']; ?>" name="<?php echo $option['name']; ?>" id="<?php echo $option['name']; ?>" value="<?php echo $option['option']; ?>" <?php  checked( $option['option'], $option['value'] ); ?> >
      <?php endif; ?>


      <?php if( $option['type'] == 'multiple_checkbox'): ?>
        <div class>
          <label class="mb-admin-label" for="<?php echo $option['name']; ?>"><?php echo $option['label']; ?></label>

          <?php if ( isset($option['desc'])): ?>
            <div><span class="mb-field-desc"><?php echo $option['desc']; ?></span></div>
          <?php endif; ?>

          <?php
          // _log( $option['options'] );
          foreach ( $option['options'] as $key => $o) {
            if ( isset($o['types']) && is_array($o['types']) && !empty($o['types']) ){
              foreach ($o['types'] as $type_key => $type) {

                $id = uniqid();
                $checked  = null;
                $type_value = $o['identifier']."|".$type_key;

                if ( is_numeric(array_search($type_value, $option['value'])) ){
                  $checked = ' checked="checked" ';
                }

                printf(
                  '<div class="mb-option-row"><input type="checkbox" id="%s" name="%s[]" value="%s" %s /><label for="%s">%s</label></div>',
                  $id, $option['name'], $type_value, $checked, $id, $o['name']." (".$type.")"
                  );
              }
            }
          }
          ?>
        </div>
      <?php endif; ?>

      <?php if( $option['type'] == 'textarea'): ?>
      <label class="mb-admin-label" for="<?php echo $option['name']; ?>"><?php echo $option['label']; ?></label>
        <textarea name="<?php echo $option['name']; ?>" id="<?php echo $option['name']; ?>" class="wcc-textarea"><?php echo $option['value']; ?></textarea>
      <?php endif; ?>

      <?php if( $option['type'] == 'select'): ?>
        <label class="mb-admin-label" for="<?php echo $option['name']; ?>"><?php echo $option['label']; ?></label>
        <?php if ( isset($option['desc']) ): ?>
          <div><span class="mb-field-desc"><?php echo $option['desc']; ?></span></div>
        <?php endif; ?>
        <select name="<?php echo $option['name'] ?>" id="<?php echo $option['name'] ?>">
            <?php foreach ($option['options'] as $value => $title) {
              printf('<option value="%s" %s>%s</option>',  $value, selected( $value, $option['value'], $echo=false ), $title );
            }
          ?>
        </select>
      <?php endif; ?>

    </div>
    <?php
  }


  public static function updateLicenceSettings(){
    global $licence_settings;

    foreach ($licence_settings as $key => $value) {
      if ( isset($_POST[$key]) ){
        update_option( $key, trim($_POST[$key])  );
      }
    }
  }


} // end of class
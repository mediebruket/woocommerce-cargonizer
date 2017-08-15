<?php

class AdminShopOptions{
  public $CargonizerOptions;

  function __construct(){
    $this->CargonizerOptions = new CargonizerOptions();
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


  // function updateOptions( $type ){
  //   $method = 'load'.$type."Options";

  //   foreach ( $this->$method() as $key => $option){
  //     if ( isset($_POST[ $option['name'] ]) ){
  //       $post_value = $_POST[ $option['name'] ];

  //       if ( is_string($post_value) ){
  //         $post_value = trim( $post_value );
  //       }

  //       update_option( $option['name'], $post_value );
  //     }
  //     else{
  //       update_option( $option['name'], null );
  //     }
  //   }

  //   $this->init();
  // }


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


  function getNavItems(){
    return array(
      'parcel' => __('Parcel', 'wc-cargonizer'),
      'confirmation' => __('Confirmation', 'wc-cargonizer'),
      'recurring' => __('Recurring', 'wc-cargonizer'),
      );
  }


  function loadParcelOptions(){

    return array(
      array(
        'name'    => 'parcel_is_recurring',
        'label'   => __('Recurring consignment', 'wc-cargonizer'),
        'type'    => 'checkbox',
        'value'   => false,
        'option'  => 'on'
      ),
      array(
        'name'    => 'parcel_printer',
        'label'   => __('Printer', 'wc-cargonizer'),
        'desc'    => __('If empty, setup api settings', 'wc-cargonizer'),
        'type'    => 'select',
        'value'   => null,
        'options'  => CargonizerOptions::getPrinterList(),
      ),
      array(
        'name'    => 'parcel_auto_transfer',
        'label'   => __('Export to carrier', 'wc-cargonizer'),
        'desc'    => __('Saves the consignment as "sent"', 'wc-cargonizer'),
        'type'    => 'checkbox',
        'value'   => false,
        'option'  => 'on'
      ),
      array(
        'name'    => 'parcel_print_on_post',
        'label'   => __('Print on export', 'wc-cargonizer'),
        'desc'    => __('Prints the consignment automatically', 'wc-cargonizer'),
        'type'    => 'checkbox',
        'value'   => false,
        'option'  => 'on'
      ),
      array(
        'name'    => 'parcel_carrier_id',
        'label'   => __('Carrier', 'wc-cargonizer'),
        'desc'    => __('If empty, setup api settings', 'wc-cargonizer'),
        'type'    => 'select',
        'value'   => null,
        'options' => $this->CargonizerOptions->getCompanyList(),
      ),
      array(
        'name'    => 'parcel_carrier_product',
        'label'   => __('Carrier product', 'wc-cargonizer'),
        'desc'    => __('If empty, setup api settings', 'wc-cargonizer'),
        'type'    => 'vue',
        'container' => 'select',
        'attr'    => 'id="@name@" name="@name@"',
        'value'   => '',
        'options' => '<option v-for="product in products" :selected="product.selected==true">{{ product.name }}</option>'
      ),
      array(
        'name' => 'parcel_message_consignee',
        'label' => __('Message', 'wc-cargonizer'),
        'type' => 'textarea',
        'value' => '',
      ),
      array(
        'name' => 'parcel_shipping_date',
        'label' => __('Shipping date', 'wc-cargonizer'),
        'desc' => __('Leave empty if the consignment is to be created today'),
        'type' => 'text',
        'value' => '',
      ),
      array(
        'name'    => 'parcel_auto_transfer',
        'label'   => __('Create consignment now', 'wc-cargonizer'),
        'desc'    => __('To create a consignment on cargonizer.no:<br/>- Will only work if shipping date is empty<br/>- Enable this checkox if carrier is selected and the parcel(s) added.', 'wc-cargonizer'),
        'type'    => 'checkbox',
        'value'   => false,
        'option'  => 'on'
      ),


    );
  }


  function loadConfirmationOptions(){
    return array(
       array(
        'name' => 'consignment_created_at',
        'label' => __('Date', 'wc-cargonizer'),
        'type' => 'text',
        'value' => '',
      ),
      array(
        'name' => 'consignment_id',
        'label' => __('Consignment id', 'wc-cargonizer'),
        'type' => 'text',
        'value' => '',
      ),
      array(
        'name' => 'consignment_tracking_code',
        'label' => __('Tracking code', 'wc-cargonizer'),
        'type' => 'text',
        'value' => '',
      ),
      array(
        'name' => 'consignment_tracking_url',
        'label' => __('Tracking url', 'wc-cargonizer'),
        'type' => 'text',
        'value' => '',
      ),
      array(
        'name' => 'consignment_pdf',
        'label' => __('Consignment pdf', 'wc-cargonizer'),
        'type' => 'text',
        'value' => '',
      ),


    );
  }



  function loadRecurringOptions(){
    return array(
       array(
        'name'  => 'copy_consignment',
        'label' => __('Copy from parcels', 'wc-cargonizer'),
        'desc'  => __('Copies carrier, type, services and message from the main parcel', 'wc-cargonizer'),
        'type'  => 'checkbox',
        'value' => '',
        'option'=> 'on'
      ),
      array(
        'name'  => 'recurring_consignment_interval',
        'label' => __('Interval', 'wc-cargonizer'),
        'type'  => 'select',
        'value' => '',
        'options' => CargonizerCommonController::getRecurringConsignmentInterval()
      ),
      array(
        'name'  => 'recurring_consignment_start_date',
        'label' => __('Startdate', 'wc-cargonizer'),
        'type'  => 'text',
        'value' => '',
      ),

      array(
        'name'  => 'recurring_consignment_carrier_id',
        'label' => __('Carrier', 'wc-cargonizer'),
        'type'  => 'select',
        'value'   => null,
        'options' => $this->CargonizerOptions->getCompanyList(),
      ),
       array(
        'name' => 'recurring_consignment_message_consignee',
        'label' => __('Message', 'wc-cargonizer'),
        'type' => 'textarea',
        'value' => '',
      ),

    );
  }








} // end of class
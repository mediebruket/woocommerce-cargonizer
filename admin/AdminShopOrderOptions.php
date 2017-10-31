<?php

class AdminShopOptions{
  public $CargonizerOptions;
  public $Id;
  public $ParcelPackages;
  public $ShopOrder;


  function __construct(){
    $this->Id = gi($_GET, 'post');
    if ( !$this->Id ){
      $this->Id = gi($_REQUEST, 'post_ID');
    }

    if ( is_numeric($this->Id) ){
      $post = get_post($this->Id);
      if ( is_object($post) && $post->post_type == 'shop_order' ){
        $this->CargonizerOptions = new CargonizerOptions();
        $this->ShopOrder = new ShopOrder($this->Id);
      }
    //_log($this->ShopOrder);
    }
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


  function getOptions( $type ){
    $method = 'load'.$type.'Options';
    ob_start();
    foreach ( $this->$method() as $key => $option){
      CargonizerHtmlBuilder::buildOption( $option );
    }

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
      // array(
      //   'name'    => 'parcel_is_recurring',
      //   'label'   => __('Recurring consignment', 'wc-cargonizer'),
      //   'type'    => 'checkbox',
      //   'value'   => false,
      //   'option'  => 'on'
      // ),
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
        'value'   => $this->ShopOrder->AutoTransfer,
        'option'  => '1'
      ),
      array(
        'name'    => 'parcel_print_on_post',
        'label'   => __('Print on export', 'wc-cargonizer'),
        'desc'    => __('Prints the consignment automatically', 'wc-cargonizer'),
        'type'    => 'checkbox',
        'value'   => $this->ShopOrder->PrintOnExport,
        'option'  => '1'
      ),
      array(
        'name'    => 'parcel_carrier_id',
        'label'   => __('Carrier', 'wc-cargonizer'),
        'desc'    => __('If empty, setup api settings', 'wc-cargonizer'),
        'type'    => 'select',
        'value'   => null,
        'attr'   => ' @change="updateProducts" v-model="carrier_id" ',
        'options' => $this->CargonizerOptions->getCompanyList(),
      ),
      array(
        'name'    => 'parcel_carrier_product',
        'label'   => __('Carrier product', 'wc-cargonizer'),
        'desc'    => __('If empty, setup api settings', 'wc-cargonizer'),
        'type'    => 'vue_select',
        'container' => 'select',
        'attr'    => 'id="@name@" name="@name@" v-model="@name@" @change="updateProductTypes" ',
        'value'   => '',
        'options' => '<option v-for="product in products" :value="product.identifier" :selected="product.selected==true">{{ product.name }}</option>'
      ),
      array(
        'name'    => 'parcel_carrier_product_type',
        'label'   => __('Product type', 'wc-cargonizer'),
        'type'    => 'vue_select',
        'container' => 'select',
        'attr'    => 'id="@name@" name="@name@" v-model="product_type" ',
        'value'   => '',
        'options' => '<option v-for="pt in product_types" :value="pt.value" :selected="pt.selected==true">{{ pt.name }}</option>'
      ),
      array(
        'name'    => 'parcel_carrier_product_services',
        'label'   => __('Product services', 'wc-cargonizer'),
        'type'    => 'vue_checkboxes',
        'container' => 'ul',
        //'attr'    => 'id="@name@" name="@name@" ',
        'value'   => '',
        'options' => '<li v-for="service in product_services">
                      <input type="checkbox" class="form-check-input"
                      name="parcel_carrier_product_services[]"
                      v-model="active_product_services"
                      :id="service.value"
                      :value="service.value"
                      :checked="service.checked==true"
                      >
                      <label class="form-check-label" :for="service.value" >{{ service.name }}</label>
                      </li>'
      ),
      array(
        'name' => 'parcel_message_consignee',
        'label' => __('Message', 'wc-cargonizer'),
        'type' => 'text',
        'value' => $this->ShopOrder->ParcelMessage,
      ),
      array(
        'name'  => 'parcel_packages',
        'label' => __('Packages', 'wc-cargonizer'),
        'type'  => 'table',
        'value'   => $this->ShopOrder->ParcelPackages,
        'options' => array('Id', 'Count', 'Description', 'Weight (kg)', 'Height (cm)', 'Length (cm)', 'Width (cm)'),
        'save_post' => false
      ),
      array(
        'name' => 'parcel_shipping_date',
        'label' => __('Shipping date', 'wc-cargonizer'),
        'desc' => __('Leave empty if the consignment is to be created today'),
        'type' => 'date',
        'value' => $this->ShopOrder->ShippingDate,
      ),
      array(
        'name'    => 'parcel_create_consignment_now',
        'label'   => __('Create consignment now', 'wc-cargonizer'),
        'desc'    => __('To create a consignment on cargonizer.no:<br/>- Will only work if shipping date is empty<br/>- Enable this checkox if carrier is selected and the parcel(s) added.', 'wc-cargonizer'),
        'type'    => 'checkbox',
        'value'   => null,
        'option'  => '1'
      ),


    );
  }


  function loadConfirmationOptions(){
    return array(
       array(
        'name'      => 'consignment_created_at',
        'label'     => __('Date', 'wc-cargonizer'),
        'type'      => 'text',
        'value'     => $this->ShopOrder->ConsignmentCreatedAt,
        'readonly'  => true
      ),
      array(
        'name'      => 'consignment_id',
        'label'     => __('Consignment id', 'wc-cargonizer'),
        'type'      => 'text',
        'value'     => $this->ShopOrder->ConsignmentId,
        'readonly'  => true
      ),
      array(
        'name'      => 'consignment_tracking_code',
        'label'     => __('Tracking code', 'wc-cargonizer'),
        'type'      => 'text',
        'value'     => $this->ShopOrder->ConsignmentTrackingCode,
        'readonly'  => true
      ),
      array(
        'name'      => 'consignment_tracking_url',
        'label'     => __('Tracking url', 'wc-cargonizer'),
        'type'      => 'text',
        'value'     => $this->ShopOrder->ConsignmentTrackingUrl,
        'readonly'  => true
      ),
      array(
        'name'      => 'consignment_pdf',
        'label'     => __('Consignment pdf', 'wc-cargonizer'),
        'type'      => 'text',
        'value'     => $this->ShopOrder->ConsignmentPdf,
        'readonly'  => true
      )
    );
  }



  function loadRecurringOptions(){
    return array(
      array(
        'name'  => 'is_recurring',
        'label' => __('Create recurring consignment', 'wc-cargonizer'),
        'type'  => 'checkbox',
        'value' => $this->ShopOrder->IsRecurring,
        'option'=> '1',
        'save_post' => false
      ),
      array(
        'name'  => 'recurring_consignment_interval',
        'label' => __('Interval', 'wc-cargonizer'),
        'type'  => 'select',
        'value' => $this->ShopOrder->RecurringInterval,
        'options' => CargonizerCommonController::getRecurringConsignmentInterval( __('every', 'wc-cargonizer' ) ),
        'wrap' => 'is-recurring'
      ),
      array(
        'name'  => 'recurring_consignment_start_date',
        'label' => __('Startdate', 'wc-cargonizer'),
        'type'  => 'date',
        'value' => $this->ShopOrder->RecurringStartDate,
        'wrap' => 'is-recurring'
      ),

      array(
        'name'  => 'recurring_consignment_carrier_id',
        'label' => __('Carrier', 'wc-cargonizer'),
        'type'  => 'select',
        'value'   => $this->ShopOrder->RecurringCarrierId,
        'attr'   => ' @change="updateRecurringProducts" v-model="recurring_consignment_carrier_id" ',
        'options' => $this->CargonizerOptions->getCompanyList(),
        'wrap' => 'is-recurring'
      ),

      array(
        'name'    => 'recurring_consignment_carrier_product',
        'label'   => __('Carrier product', 'wc-cargonizer'),
        'desc'    => __('If empty, setup api settings', 'wc-cargonizer'),
        'type'    => 'vue_select',
        'container' => 'select',
        'attr'    => 'id="@name@" name="@name@" v-model="@name@" @change="updateRecurringProductTypes" ',
        'value'   => '',
        'options' => '<option v-for="product in recurring_consignment_products" :value="product.identifier" :selected="product.selected==true">{{ product.name }}</option>',
        'wrap' => 'is-recurring'
      ),
      array(
        'name'    => 'recurring_consignment_product_type',
        'label'   => __('Product type', 'wc-cargonizer'),
        'type'    => 'vue_select',
        'container' => 'select',
        'attr'    => 'id="@name@" name="@name@" v-model="recurring_consignment_product_type" ',
        'value'   => '',
        'options' => '<option v-for="pt in recurring_consignment_product_types" :value="pt.value" :selected="pt.selected==true">{{ pt.name }}</option>',
        'wrap' => 'is-recurring'
      ),
      array(
        'name'    => 'recurring_consignment_product_services',
        'label'   => __('Product services', 'wc-cargonizer'),
        'type'    => 'vue_checkboxes',
        'container' => 'ul',
        //'attr'    => 'id="@name@" name="@name@" ',
        'value'   => '',
        'options' => '<li v-for="service in recurring_consignment_product_services">
                      <input type="checkbox" class="form-check-input"
                      name="recurring_consignment_product_services[]"
                      v-model="recurring_consignment_active_product_services"'.

                      // :id="service.value"

                      ' :value="service.value"
                      :checked="service.checked==true"
                      >
                      <label class="form-check-label" >{{ service.name }}</label>
                      </li>',
        'wrap' => 'is-recurring'
      ),
      array(
        'name' => 'recurring_consignment_message_consignee',
        'label' => __('Message', 'wc-cargonizer'),
        'type' => 'textarea',
        'value' => $this->ShopOrder->RecurringConsignmentMessage,
        'wrap' => 'is-recurring'
      ),
      array(
        'name'  => 'recurring_consignment_packages',
        'label' => __('Packages', 'wc-cargonizer'),
        'type'  => 'table',
        'value'   => $this->ShopOrder->RecurringConsignmentItems,
        'options' => array('Id', 'Count', 'Description', 'Weight (kg)', 'Height (cm)', 'Length (cm)', 'Width (cm)'),
        'save_post' => false,
        'wrap' => 'is-recurring'
      ),

    );
  }


} // end of class
<?php

class AdminConsignmentOptions{
  public $CargonizerOptions;
  public $Id;
  public $ParcelPackages;
  public $Consignment;

  function __construct(){
    $this->Id = gi($_GET, 'post');
    if ( !$this->Id ){
      $this->Id = gi($_REQUEST, 'post_ID');
    }

    if ( is_numeric($this->Id) ){
      $post = get_post($this->Id);
      if ( is_object($post) && $post->post_type == 'consignment' ){

        $this->CargonizerOptions = new CargonizerOptions();
        $this->Consignment = new Consignment($this->Id);
        //_log($this->Consignment);
      }
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
    foreach ( $this->$method() as $key => $option):
    ?>
    <?php CargonizerHtmlBuilder::buildOption( $option ); ?>
    <?php

    endforeach;
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
  }


  function getNavItems(){
    return array(
      'parcel'      => __('Parcel', 'wc-cargonizer'),
      'consignee'   => __('Consignee', 'wc-cargonizer'),
      'history'     => __('History', 'wc-cargonizer'),
      'products'    => __('Products', 'wc-cargonizer'),
      );
  }


  function loadHistoryOptions(){
    return array(
        array(
          'name'    => 'consignment_history',
          'label'   => __('History', 'wc-cargonizer'),
          'type'    => 'history',
          'value'   => $this->Consignment->History,
          'options' => array('Created at', 'Consignment Id', 'Tracking code', 'Tracking url', 'PDF'),
          'save_post' => false
        )
      );
  }


  function loadProductsOptions(){
    return array(
        array(
          'name'    => 'consignment_order_products',
          'label'   => __('Products', 'wc-cargonizer'),
          'type'    => 'products',
          'value'   => (!$this->Consignment->IsRecurring) ? $this->Consignment->OrderProducts: $this->Consignment->SubscriptionProducts,
          'options' => array('Id', 'Product', 'Qty'),
          'save_post' => false
        )
      );
  }


  function loadConsigneeOptions(){
     return array(
      array(
        'name'    => 'customer_id',
        'label'   => __('Customer id', 'wc-cargonizer'),
        'type'    => 'text',
        'value'   => $this->Consignment->CustomerId
      ),
      array(
        'name'    => '_shipping_first_name',
        'label'   => __('First name', 'wc-cargonizer'),
        'type'    => 'text',
        'value'   => $this->Consignment->ShippingFirstName,
      ),
      array(
        'name'    => '_shipping_last_name',
        'label'   => __('Last name', 'wc-cargonizer'),
        'type'    => 'text',
        'value'   => $this->Consignment->ShippingLastName,
      ),
      array(
        'name'    => '_shipping_address_1',
        'label'   => __('Shipping address 1', 'wc-cargonizer'),
        'type'    => 'text',
        'value'   => $this->Consignment->ShippingAddress1,
      ),
      array(
        'name'    => '_shipping_address_2',
        'label'   => __('Shipping address 2', 'wc-cargonizer'),
        'type'    => 'text',
        'value'   => $this->Consignment->ShippingAddress2,
      ),
      array(
        'name'    => '_shipping_postcode',
        'label'   => __('Postcode', 'wc-cargonizer'),
        'type'    => 'text',
        'value'   => $this->Consignment->ShippingPostCode,
      ),
      array(
        'name'    => '_shipping_city',
        'label'   => __('City', 'wc-cargonizer'),
        'type'    => 'text',
        'value'   => $this->Consignment->ShippingCity,
      ),
      array(
        'name'    => '_shipping_country',
        'label'   => __('Countyr', 'wc-cargonizer'),
        'type'    => 'text',
        'value'   => $this->Consignment->ShippingCountry,
      ),
      array(
        'name'    => 'email',
        'label'   => __('E-mail', 'wc-cargonizer'),
        'type'    => 'text',
        'value'   => $this->Consignment->CustomerEmail,
      ),
      array(
        'name'    => 'phone',
        'label'   => __('Phone', 'wc-cargonizer'),
        'type'    => 'text',
        'value'   => $this->Consignment->CustomerPhone,
      ),
    );
  }


  function loadParcelOptions(){

    return array(
      array(
        'name'    => 'consignment_is_recurring',
        'label'   => __('Is recurring', 'wc-cargonizer'),
        'type'    => 'checkbox',
        'value'   => $this->Consignment->IsRecurring,
        'option'  => '1'
      ),

      array(
        'name'      => 'consignment_start_date',
        'label'     => __('Start date', 'wc-cargonizer'),
        'desc'      => __(''),
        'type'      => 'date',
        'value'     => $this->Consignment->StartDate,
        'wrap'      => 'consignment-start-date'
      ),

      array(
        'name'      => 'consignment_next_shipping_date',
        'label'     => __('(Next) Shipping date', 'wc-cargonizer'),
        'desc'      => __('Leave empty if the consignment is to be created today'),
        'type'      => 'date',
        'value'     => $this->Consignment->NextShippingDate,
      ),
      array(
        'name'      => 'parcel_printer',
        'label'     => __('Printer', 'wc-cargonizer'),
        'desc'      => __('If empty, setup api settings', 'wc-cargonizer'),
        'type'      => 'select',
        'value'     => $this->Consignment->Printer,
        'options'   => CargonizerOptions::getPrinterList(),
      ),
      array(
        'name'      => 'consignment_print_on_export',
        'label'     => __('Export to carrier', 'wc-cargonizer'),
        'desc'      => __('Saves the consignment as "sent"', 'wc-cargonizer'),
        'type'      => 'checkbox',
        'value'     => $this->Consignment->PrintOnExport,
        'option'    => '1'
      ),
      array(
        'name'      => 'consignment_auto_transfer',
        'label'     => __('Print on export', 'wc-cargonizer'),
        'desc'      => __('Prints the consignment automatically', 'wc-cargonizer'),
        'type'      => 'checkbox',
        'value'     => $this->Consignment->AutoTransfer,
        'option'    => '1'
      ),
      array(
        'name'      => 'consignment_carrier_id',
        'label'     => __('Carrier', 'wc-cargonizer'),
        'desc'      => __('If empty, setup api settings', 'wc-cargonizer'),
        'type'      => 'select',
        'value'     => $this->Consignment->CarrierId,
        'attr'      => ' @change="updateProducts" v-model="carrier_id" ',
        'options'   => $this->CargonizerOptions->getCompanyList(),
      ),
      array(
        'name'      => 'consignment_product',
        'label'     => __('Carrier product', 'wc-cargonizer'),
        'desc'      => __('If empty, setup api settings', 'wc-cargonizer'),
        'type'      => 'vue_select',
        'container' => 'select',
        'attr'      => 'id="@name@" name="@name@" v-model="parcel_carrier_product" @change="updateProductTypes" ',
        'value'     => '',
        'options'   => '<option v-for="product in products" :value="product.identifier" :selected="product.selected==true">{{ product.name }}</option>'
      ),
      array(
        'name'      => 'consignment_product_type',
        'label'     => __('Product type', 'wc-cargonizer'),
        'type'      => 'vue_select',
        'container' => 'select',
        'attr'      => 'id="@name@" name="@name@" v-model="product_type" ',
        'value'     => '',
        'options'   => '<option v-for="pt in product_types" :value="pt.value" :selected="pt.selected==true">{{ pt.name }}</option>'
      ),
      array(
        'name'      => 'consignment_services',
        'label'     => __('Product services', 'wc-cargonizer'),
        'type'      => 'vue_checkboxes',
        'container' => 'ul',
        //'attr'    => 'id="@name@" name="@name@" ',
        'value'     => '',
        'options'   => '<li v-for="service in product_services">
                      <input type="checkbox" class="form-check-input" name="consignment_services[]" nav_menu_description="@name@[]" v-model="active_product_services" :id="service.value" :value="service.value" :checked="service.checked==true">
                      <label class="form-check-label" :for="service.value" >{{ service.name }}</label>
                      </li>'
      ),
      array(
        'name'      => 'consignment_message',
        'label'     => __('Message', 'wc-cargonizer'),
        'type'      => 'textarea',
        'value'     => $this->Consignment->Message,
      ),

      array(
        'name'      => 'consignment_packages',
        'label'     => __('Packages', 'wc-cargonizer'),
        'type'      => 'table',
        'value'     => $this->Consignment->Items,
        'options'   => array('Id', 'Count', 'Parcel type', 'Description', 'Weight (kg)', 'Height (cm)', 'Length (cm)', 'Width (cm)'),
        'save_post' => false
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
<?php

/**
 class AdminConsignment
 - extends class AdminConsignmentOptions
 - adds metaboxes to custom post type consignment
**/

class AdminConsignment extends AdminConsignmentOptions{

  function __construct(){
    parent::__construct();
    add_action( 'add_meta_boxes', array($this, 'registerOrderMetaBox') );
  }


  function registerOrderMetaBox() {
    add_meta_box(
      'consignment-meta-box-id',
      esc_html__( 'Logistra Cargonizer', 'wc-cargonizer' ),
      array( $this, 'makeOrderMetaBox' ),
      'consignment',
      'advanced',
      'high'
    );
  }


  function makeOrderMetaBox( $meta_id ) {
    $html = $navigation = null;

    if ( $nav_items = $this->getNavItems() ){
      $i = 0;
      foreach ($nav_items as $tab => $text) {
        $navigation .= CargonizerHtmlBuilder::buildNavItem($text, $tab, $active=(($i==0) ? true: false) );
        $i++;
      }

      if ( $navigation ){
        $html .= CargonizerHtmlBuilder::buildNavigation( $navigation );
      }
    }

    // all carriers
    $data['carriers'] = $this->CargonizerOptions->getCompanyList();
    // active carrier

    $data['carrier_id'] =  ( $this->Consignment->CarrierId ) ?  $this->Consignment->CarrierId : $this->CargonizerOptions->get('CarrierId');

    // active product
    $data['parcel_carrier_product'] = ( $this->Consignment->CarrierProduct ) ? $this->Consignment->CarrierProduct : $this->CargonizerOptions->get('DefaultCarrierProduct');
    // all products
    $data['products'] = CargonizerCommonController::getProductsByCarrierId( $data['carrier_id'] );


    // all product types, handled by vue
    $data['product_types'] = array();
    // active product type
    $data['product_type'] = ( $this->Consignment->CarrierProductType ) ? $this->Consignment->CarrierProductType : $this->CargonizerOptions->get('DefaultProductType');


    // all product services, handled by vue
    $data['product_services'] = array();
    // active product services
    $data['active_product_services'] = array();

    if ( $this->Consignment->CarrierProductServices ){
      $data['active_product_services'] = $this->Consignment->CarrierProductServices;
    }
    else if ( $default_product_services = $this->CargonizerOptions->get('DefaultProductServices') ){
      $data['active_product_services'] = $default_product_services;
    }

    $html .= '<script>var data = '.json_encode($data).'</script>';

    $html .='<div class="tab-content vue-consignment" id="admin_consignment">'.
              CargonizerHtmlBuilder::buildTab( $id="parcel", $this->getOptions('Parcel'), $class='show active' ).
                CargonizerHtmlBuilder::buildTab( $id="consignee", $this->getOptions('Consignee') ).
                  CargonizerHtmlBuilder::buildTab( $id="history", $this->getOptions('History') ).
                  CargonizerHtmlBuilder::buildTab( $id="products", $this->getOptions('Products') ).
                  '</div>';

    echo $html;
  }


} // end of class


add_action( 'init', 'init_admin_consignment', 10 );
function init_admin_consignment(){
  if ( gi($_GET, 'post') && gi($_GET, 'action') == 'edit' ){
    new AdminConsignment();
  }
}

<?php

class AdminShopOrder extends AdminShopOptions{
  
  function __construct(){
    parent::__construct();
  
    add_action( 'add_meta_boxes', array($this, 'registerOrderMetaBox') );
  }


  function registerOrderMetaBox() {
    add_meta_box(
      'rm-meta-box-id',
      esc_html__( 'Logistra Cargonizer', 'wc-cargonizer' ),
      array( $this, 'makeOrderMetaBox' ),
      'shop_order',
      'advanced',
      'high'
    );
  }


  function getCarrierProducts(){
    $products = array();

    $ta = $this->CargonizerOptions->get('SelectedTransportAgreement');
    $ts = $this->CargonizerOptions->get('DefaultCarrierProduct');

    if ( is_array($ta) && isset($ta['products']) && is_array($ta['products']) ){
      $has_selected = false;
      foreach( $ta['products'] as $key => $p ) {
        $p['selected'] = false;
        if ( is_string($ts) && $p['name'] == $ts ){
          $p['selected'] = $has_selected = true;
        }

        $products[] = $p;
      }

      if ( !$has_selected ){
        $products[0]['selected'] = true;
      }
    }


    return $products;
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

    //_log($this->ShopOrder);
    $data['carrier_id'] =  ( $this->ShopOrder->CarrierId ) ?  $this->ShopOrder->CarrierId : $this->CargonizerOptions->get('CarrierId'); 
    

    // active product
    $data['parcel_carrier_product'] = ( $this->ShopOrder->CarrierProduct ) ? $this->ShopOrder->CarrierProduct : $this->CargonizerOptions->get('DefaultCarrierProduct');
    // all products
    $data['products'] = CargonizerCommonController::getProductsByCarrierId( $data['carrier_id'] ); 


  
    // all product types, handled by vue
    $data['product_types'] = array(); 
    // active product type
    $data['product_type'] = ( $this->ShopOrder->ParcelType ) ? $this->ShopOrder->ParcelType : $this->CargonizerOptions->get('DefaultProductType');


    // all product services, handled by vue
    $data['product_services'] = array();   
    // active product services
    $data['active_product_services'] = array();
    if ( $this->ShopOrder->ParcelServices ){
      $data['active_product_services'] = $this->ShopOrder->ParcelServices;
    }
    else if ( $default_product_services = $this->CargonizerOptions->get('DefaultProductServices') ){
      $data['active_product_services'] = $default_product_services;
    }


    $html .= '<script>var data = '.json_encode($data).'</script>';


    $html .='<div class="tab-content vue-consignment" id="admin_shop_order">'.
              CargonizerHtmlBuilder::buildTab( $id="parcel", $this->getOptions('Parcel'), $class='show active' ).
                CargonizerHtmlBuilder::buildTab( $id="confirmation", $this->getOptions('Confirmation') , null).
                  CargonizerHtmlBuilder::buildTab( $id="recurring", $this->getOptions('Recurring'), null ).
                    '</div>';

    echo $html;
  }


} // end of class


add_action( 'init', 'init_admin_shop_order', 10 );
function init_admin_shop_order(){  
  if ( gi($_GET, 'post') && gi($_GET, 'action') == 'edit' ){
    new AdminShopOrder();  
  }
}

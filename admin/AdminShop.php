<?php

class AdminShop extends AdminShopOptions{

  function __construct(){
    parent::__construct();
    add_action( 'add_meta_boxes', array($this, 'registerOrderMetaBox') );
    $this->CargonizerOptions = new CargonizerOptions();
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




    $data['parcel_carrier_product'] = $this->CargonizerOptions->get('DefaultCarrierProduct'); // active product
    $data['products'] = $this->getCarrierProducts(); // available products

    $data['carrier_id'] = $this->CargonizerOptions->get('CarrierId'); // active carrier
    $data['carriers'] = $this->CargonizerOptions->getCompanyList(); // availabe carriers

    $data['product_types'] = array(); // available product types
    $data['product_type'] = null; // active product type
    if ( $default_product_type = $this->CargonizerOptions->get('DefaultProductType') ){
      $data['product_type'] = $default_product_type;
    }

    $data['product_services'] = array(); // availabel product services
    if ( $default_product_services = $this->CargonizerOptions->get('DefaultProductServices') ){
      $data['active_product_services'] = $default_product_services;
    }

    $html .= '<script>var data = '.json_encode($data).'</script>';


    $html .='<div class="tab-content" id="admin_shop">'.
              CargonizerHtmlBuilder::buildTab( $id="parcel", $this->getOptions('Parcel'), $class='show active' ).
                CargonizerHtmlBuilder::buildTab( $id="confirmation", $this->getOptions('Confirmation') , null).
                  CargonizerHtmlBuilder::buildTab( $id="recurring", $this->getOptions('Recurring'), null ).
                    '</div>';

    echo $html;
  }


} // end of class

new AdminShop();
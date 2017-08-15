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
    $ts = $this->CargonizerOptions->get('TransportProduct');

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




    $data['products'] = $this->getCarrierProducts();

    $data['product_types'] =
      array(
        array(
          'value' => 'x',
          'name' => '123',
        ),
        array(
          'value' => 'y',
          'name' => '567',
        ),
      );
    $data['parcel_carrier_product'] = null;

    _log($data);
    // todo
    // data.products // get carrier products
    // product text, selected, services, value


    $html .= '<script>var data = '.json_encode($data).'</script>';

   /* $html .= "<script> data = {
    products: [
    {
      text: 'Bananas',
      selected: false
    },
    {
      text:  'Apples',
      selected: true
    }
    ,
    {
      text:  'Ananas',
      selected: false
    }
    ],
  };

 </script>";*/

    $html .='<div class="tab-content" id="admin_shop">'.
              CargonizerHtmlBuilder::buildTab( $id="parcel", $this->getOptions('Parcel'), $class='show active' ).
                CargonizerHtmlBuilder::buildTab( $id="confirmation", $this->getOptions('Confirmation') , null).
                  CargonizerHtmlBuilder::buildTab( $id="recurring", $this->getOptions('Recurring'), null ).
                    '</div>';

    echo $html;
  }


} // end of class

new AdminShop();
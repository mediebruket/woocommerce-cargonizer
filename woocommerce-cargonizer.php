<?php
/*woo
Plugin Name: Woocomerce Cargonizer
Description:
Version: 0.1.4
Author: Mediebruket AS
Author URI: http://mediebruket.no
*/

global $plugin_file;
$plugin_file = __FILE__;
include('conf/conf.php');
include('include/api/CargonizerApi.php');
include('admin/index.php');
include('include/index.php');



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

    $html .='<div class="tab-content">'.
              CargonizerHtmlBuilder::buildTab( $id="parcel", $this->getOptions('Parcel'), $class='show active' ).
                CargonizerHtmlBuilder::buildTab( $id="confirmation", $this->getOptions('Confirmation') , null).
                  CargonizerHtmlBuilder::buildTab( $id="recurring", $this->getOptions('Recurring'), null ).
                    '</div>';

    echo $html;
  }


} // end of class

new AdminShop();
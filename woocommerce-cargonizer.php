<?php
/*woo
Plugin Name: Woocommerce Cargonizer
Description:
Version: 0.4.2
Author: Mediebruket AS
Author URI: http://mediebruket.no
*/

global $plugin_file;
$plugin_file = __FILE__;
global $enable_logging;
$enable_logging = get_option('cargonizer-enable-logging');

$plugin_path = plugin_dir_path( __FILE__ );

require_once( $plugin_path . 'admin/CargonizerUpdater.php');
$CargonizerUpdater = new CargonizerUpdater( __FILE__ );

require_once( $plugin_path . 'conf/conf.php');

require_once( $plugin_path . 'include/util/utils.php');
require_once( $plugin_path . 'include/util/XmlConverter.php');

require_once( $plugin_path . 'include/api/CargonizerApi.php');


require_once( $plugin_path . 'admin/CargonizerIcons.php');
require_once( $plugin_path . 'admin/CargonizerOptions.php');
require_once( $plugin_path . 'admin/CargonizerAdmin.php');
require_once( $plugin_path . 'admin/AdminShopOrderOptions.php');
require_once( $plugin_path . 'admin/AdminShopOrder.php');
require_once( $plugin_path . 'admin/AdminConsignmentOptions.php');
require_once( $plugin_path . 'admin/AdminConsignment.php');
require_once( $plugin_path . 'admin/CargonizerHtmlBuilder.php');
require_once( $plugin_path . 'admin/CargonizerAjax.php');


require_once( $plugin_path . 'include/ShopOrder.php');
require_once( $plugin_path . 'include/CargonizeXml.php');
require_once( $plugin_path . 'include/controllers/CargonizerCommonController.php');
require_once( $plugin_path . 'include/controllers/ShopOrderController.php');
require_once( $plugin_path . 'include/controllers/ConsignmentController.php');
require_once( $plugin_path . 'include/Consignment.php');

require_once( $plugin_path . 'frontend/ServicePartners.php');


function wcc_textdomain() {
  load_plugin_textdomain( 'wc-cargonizer', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
}
add_action('plugins_loaded', 'wcc_textdomain');
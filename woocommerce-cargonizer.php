<?php
/*woo
Plugin Name: Woocomerce Cargonizer
Description:
Version: 0.2.3
Author: Mediebruket AS
Author URI: http://mediebruket.no
*/

global $plugin_file;
$plugin_file = __FILE__;
$plugin_path = plugin_dir_path( __FILE__ );

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
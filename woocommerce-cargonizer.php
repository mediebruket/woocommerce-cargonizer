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

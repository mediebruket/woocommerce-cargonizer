<?php

if( !class_exists('acf') ) {
  include('vendor/advanced-custom-fields-pro/acf.php');
}

include('util/utils.php');
include('util/acf.php');
include('util/XmlConverter.php');

include('Parcel.php');
include('CargonizeXml.php');
include('Cargonizer.php');
include('CargonizerUpdater.php');
include('Consignment.php');
include('CargonizerAjax.php');
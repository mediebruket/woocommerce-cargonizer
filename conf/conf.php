<?php

define('WCC_Admin', 'woocommerce-cargonizer-admin');

class CargonizerConfig{
  public static function getConfig($index){
    $configs = self::getConfigs();

    if ( $index ){
      if ( $config = _is($configs, $index) ){
        return $config;
      }
      else{
        return null;
      }
    }
    else{
      return $configs;
    }
  }

  public static function getConfigs(){
    return array(
      'consignment' => array(
        'post-id'  => __('ID', 'wc-cargonizer'),
        'consignment-receiver' => __('Receiver', 'wc-cargonizer'),
        'consignment-interval' => __('Interval', 'wc-cargonizer'),
        'consignment-next-shipping-date'  => __('Next shipping date', 'wc-cargonizer'),
        'consignment-actions'  => __('Actions', 'wc-cargonizer'),
      )
    );
  }
}
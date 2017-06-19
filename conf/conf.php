<?php

define('WCC_Admin', 'woocommerce-cargonizer-admin');

class CargonizerConfig{
  public static function getConfig($index){
    $configs = self::getConfigs();

    if ( $index ){
      if ( isset($configs[$index]) ){
        return $configs[$index];
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
        'consignment-post-id'  => __('ID', 'wc-cargonizer'),
        'consignment-receiver' => __('Receiver', 'wc-cargonizer'),
        'consignment-interval' => __('Interval', 'wc-cargonizer'),
        'consignment-next-shipping-date'  => __('Next shipping date', 'wc-cargonizer'),
        'consignment-actions'  => __('Actions', 'wc-cargonizer'),
        'consignment-status'  => __('Status', 'wc-cargonizer'),
        'consignment-last-shipping-date'  => __('Last shipping date', 'wc-cargonizer'),
      )
    );
  }
}
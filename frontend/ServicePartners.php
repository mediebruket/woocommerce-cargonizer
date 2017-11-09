<?php

add_action( 'wp_ajax_get_service_partners', array('ServicePartners', 'getServicePartners') );
add_action( 'wp_ajax_nopriv_get_service_partners', array('ServicePartners', 'getServicePartners') );
add_action('woocommerce_checkout_billing', array('ServicePartners', 'addVueApp' ), 100 );


class ServicePartners{

  public static function getServicePartners(){
    _log('ServicePartners::getServicePartners()');
    $Api = new CargonizerApi();
    $service_partners = array();
    $carrier = null;
    if ( $carrier_id = get_option( 'cargonizer-carrier-id' ) ){
      $Options = new CargonizerOptions();
      $carrier = $Options->getCarrierIdentifier();
    }

    $response = $Api->getServicePartners( gi($_POST,'postcode'), gi($_POST,'country'), $carrier );

    if ( is_object($response) ){
      $pickup_points = mbx($response, 'service-partners/service-partner', 'array');
      if ( is_array($pickup_points) ){
        foreach ( $pickup_points as $key => $Point) {
          $address = sprintf('%s, %s %s', mbx($Point, 'address1'), mbx($Point, 'postcode'), mbx($Point, 'city') );
          $opening_hours = array();
          $opening_days = mbx($Point, 'opening-hours/day', 'array' );

          if ( is_array($opening_days) ){
            foreach ($opening_days as $dk => $Day) {
              $from = mbx($Day, 'hours/@from');
              $to   = mbx($Day, 'hours/@to');
              $hours = ( $from && $to ) ? sprintf('%s-%s', $from, $to ) : __('closed', 'wc-cargonizer' );

              $opening_hours[] =
                array(
                  'name' => self::translateDay( mbx($Day, '@name') ),
                  'hours' => $hours
                );
            }
            // _log('$opening_hours');
            // _log($opening_hours);
          }
          // else{
          //   _log('missing opening hours');
          // }

          $partner =
            array(
              'number'        => mbx($Point, 'number'),
              'name'          => mbx($Point, 'name'),
              'opening_hours' => $opening_hours,
              'address1'      => mbx($Point, 'address1'),
              'postcode'      => mbx($Point, 'postcode'),
              'city'          => mbx($Point, 'city'),
              'country'       => mbx($Point, 'country'),
              'address_as_text' => $address,
              'checked'       => ($key==0) ? true: false
            );

          $service_partners[] = $partner;
        }
      }
    }


    // _log($service_partners);

    echo json_encode($service_partners);
    die();
  }


  public static function addVueApp(){
    // load vue markup if is checkout page and service partners is enabled
    if ( is_checkout() && get_option('cargonizer-use-service-partners') ){
      printf('<script>wcc_ajax_url="%s";</script>', admin_url( 'admin-ajax.php' ) );
      printf('<input type="hidden" name="wcc-service-partner-id" id="wcc-service-partner-id" />' );
      printf('<input type="hidden" name="wcc-service-partner-name" id="wcc-service-partner-name" />' );
      printf('<input type="hidden" name="wcc-service-partner-address" id="wcc-service-partner-address" />' );
      printf('<input type="hidden" name="wcc-service-partner-postcode" id="wcc-service-partner-postcode" />' );
      printf('<input type="hidden" name="wcc-service-partner-city" id="wcc-service-partner-city" />' );
      printf('<input type="hidden" name="wcc-service-partner-country" id="wcc-service-partner-country" />' );

      printf('<div class="wcc-service-partners" id="wcc-service-partners">');
      printf('  <h3 class="wcc-service-partners-title">%s</h3>', __('Choose pick-up point', 'wc-cargonizer') );
      printf('    <ul class="service-partner-list">' );
      printf('      <li v-for="partner in service_partners">');
      printf('        <input type="radio" name="wcc-service-partner" class="wcc-service-partner" :value="partner.number" :id="partner.number" :checked="partner.checked" @click="checked_service_partner()">');
      printf('        <label class="wcc-service-partner-label" :for="partner.number">{{partner.name}}</label><br/>{{partner.address_as_text}}');
      printf('        <a href="#" class="wcc-show-opening-hours" :data-id="partner.number" @click="toggle_opening_hours(partner.number , $event)">%s</a>', __('Show opening hours', 'wc-cargonizer' ) );
      printf(         '<div class="wcc-opening-hours" :id="\'opening-hours-\' + partner.number">');
      printf('        <span class="wcc-opening-day" v-for="day in partner.opening_hours">{{day.name}}: {{day.hours}} </span>');
      printf('        </div>');
      printf('      </li>');
      printf('   </ul>');
      printf('</div>');
    }
  }


  public static function translateDay($day){
    $day = strtolower($day);
    if ( $day == 'monday'){
      return 'Mandag';
    }
    elseif ( $day == 'tuesday'){
      return 'Tirsdag';
    }
    elseif ( $day == 'wednesday'){
      return 'Onsdag';
    }
    elseif ( $day == 'thursday'){
      return 'Torsdag';
    }
    elseif ( $day == 'friday'){
      return 'Fredag';
    }
    elseif ( $day == 'saturday'){
      return 'Lørdag';
    }
    elseif ( $day == 'sunday'){
      return 'Søndag';
    }
    else{
      return null;
    }
  }


} // end of class
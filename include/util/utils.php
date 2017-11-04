<?php


if ( !function_exists('gi') ){
  function gi( $array, $index, $default=null ){
    $value = $default;

    if ( is_array($array) && isset($array[$index]) ){
      if ( is_string($array[$index]) or is_numeric($array[$index]) ){
        $value = $array[$index];
      }
      elseif ( is_string($array[$index][0]) ){
        $value = $array[$index][0];
      }
    }
    else if ( is_object($array) && isset($array->$index) ){
      $value = $array->$index;
    }

    return $value;
  }
}

if ( !function_exists('_is') ){
  function _is( $array, $index ) {
    gi($array, $index);
  }
}

if(!function_exists('_log')){
  function _log( $message ) {
    global $enable_logging;
    if( WP_DEBUG === true && $enable_logging ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( print_r( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}


if ( !function_exists('cleanDate') ){
  function cleanDate( $date ){
    return str_replace('-', null, $date );
  }
}
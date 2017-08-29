<?php


if ( !function_exists('gi') ){
  function gi( $array, $index ){
    if ( is_array($array) && isset($array[$index]) ){
      if ( is_string($array[$index]) or is_numeric($array[$index]) ){
        return $array[$index];
      }
      elseif ( is_string($array[$index][0]) ){
        return $array[$index][0];
      }
    }
    else if ( is_object($array) && isset($array->$index) ){
      return $array->$index;
    }
    else{
      return null;
    }
  }
}

if ( !function_exists('_is') ){

  function _is( $array, $index ) {
    gi($array, $index);
  }
}

if(!function_exists('_log')){
  function _log( $message ) {
    if( WP_DEBUG === true ){
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
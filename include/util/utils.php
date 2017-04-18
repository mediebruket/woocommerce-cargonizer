<?php


if ( !function_exists('gi') ){
  function gi( $array, $index ){
    if ( isset($array[$index]) ){

      if ( is_string($array[$index]) ){
        return $array[$index];
      }
      elseif ( is_string($array[$index][0]) ){
        return $array[$index][0];
      }

    }
    else{
      return null;
    }
  }
}


?>

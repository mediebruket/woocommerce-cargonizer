<?php
if ( !function_exists('acf_getField') ){
  function acf_getField($field, $post_id =null){
    $value = null;

    if ( !$post_id ){
      $post_id = get_the_id();
    }

    if ( function_exists('get_field') ) {
      $value = get_field($field, $post_id);
    }

    return $value;
  }
}

if ( !function_exists('acf_updateField') ){
  function acf_updateField($field, $value, $post_id){
    // _log('acf_updateField');
    // _log($field);
    // _log($value);
    // _log($post_id);
    // _log('---------');

    if ( function_exists('update_field') ) {
      $value = update_field($field, $value, $post_id);
    }

    return $value;
  }
}


if ( !function_exists('acf_getFields') ){
  function acf_getFields( $post_id = null){
    $fields = null;

    if ( !$post_id ){
      $post_id = get_the_id();
    }

    if ( function_exists('get_fields') ) {
      $fields = get_fields($post_id);
    }

    return $fields;
  }
}


include('fields/shop-order.php');
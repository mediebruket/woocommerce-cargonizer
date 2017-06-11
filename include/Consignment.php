<?php
add_action( 'init', array('Consignment', '_registerPostType') );

class Consignment{

  public static function createOrUpdate( $order_id, $recurring=false ){
    _log('Consignment::createOrUpdate('.$order_id.')');
    
    $args = array( 
      'post_author' => get_current_user_id(),     
      'post_title' =>  sprintf( 'Related to order #%s ', $order_id, (($recurring) ? '| recurring ' : null) ),      
      'post_status' => 'publish',
      'post_type' => 'consignment',
      'post_parent' => 0,
    );

    if ( $cid = self::getConsignmentIdByOrderId( $order_id ) ){
      _log('update existing consignment: '.$cid);
      $args['ID'] = $cid;
    }
    else{
      _log('create new consignment');
    }

    if ( $post_id = wp_insert_post( $args ) ){
      _log('consignment: '.$post_id);
      update_post_meta( $post_id, 'consignment_order_id', $order_id );

      if ( !isset($args['ID']) ){
        self::addNote($order_id, $post_id);  
      }
      
      /*
        Products: name, sku, count 
        Shipping address:
        logistra settings
        intervall ? 
        shipping date / neste shipping
        is recurring
      */
    }
  }


  public static function addNote( $order_id, $consignment_id ){
    _log('Consignment::addNote('.$order_id.')');

    $data = array(
      'comment_post_ID'       => $order_id,
      'comment_author'        => 'WooCommerce Cargonizer',
      'comment_author_email'  => get_option('admin_email' ),
      'comment_content'       => sprintf( __('Consignment created: <a href="%s">edit</a>', 'wc-cargonizer'), get_edit_post_link($consignment_id) ),
      'comment_agent'         => 'WooCommerce Cargonizer',
      'comment_type'          => 'order_note',
      'comment_parent'        => 0,
      'user_id'               => 1,
      'comment_author_IP'     => 'null',
      'comment_date'          => current_time('mysql'),
      'comment_approved'      => 1,
    );

    
    if ( wp_insert_comment($data) ){
      _log('new note added');
    }
    else{
      _log('error: wp_insert_comment');
      _log($data);
    }
  }


  public static function getConsignmentIdByOrderId( $order_id ){
    global $wpdb;
    $sql = sprintf("SELECT post_id FROM %s WHERE meta_key = 'consignment_order_id' and meta_value= '%s';", $wpdb->postmeta, $order_id );
    return $wpdb->get_var($sql);

  }
    
  public static function _registerPostType(){

    $single = __('consignment' );
    $multi  = __('consignments' );

    $labels = array(
        'name'              => ucfirst($single),
        'singular_name'     => ucfirst($single),
        'add_new'           => __('Add ', 'wc-cargonizer'),
        'all_items'         => 'all '.$multi ,
        'add_new_item'      => 'add '.$single,
        'edit_item'         => 'edit '.$single,
        'new_item'            => 'add '.$single,
        'view_item'           => 'show '.$single,
        'search_items'        => 'search '.$single,
        'not_found'           => 'No '.$single.' found',
        'not_found_in_trash'  => 'No '.$single.' in trash',
        'parent_item_colon'   => 'Parent Post:',
        'menu_name'           => ucfirst($multi)
    );

    $args = array(
      'labels'               => $labels,
      'description'          => "",
      'public'               => true,
      'exclude_from_search'  => false,
      'publicly_queryable'   => true,
      'show_ui'              => true,
      'show_in_nav_menus'    => true,
      'show_in_menu'         => true,
      'show_in_admin_bar'    => true,
      'menu_position'        => 42,
      'capability_type'      => 'post',
      'hierarchical'         => true,
      'supports'             => array('title' ),
      'has_archive'          => true,
      'rewrite'              => array('slug' => 'consignment', 'with_front' => FALSE),
      'query_var'            => true,
      'can_export'           => true,
      'taxonomies'           => array('post_tag')
    );
    register_post_type( 'consignment' ,$args) ;

  }

}
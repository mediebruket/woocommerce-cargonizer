<?php

add_filter( 'manage_edit-shop_order_columns', array('CargonizerAdmin', 'newCustomOrderColumn' ) );
add_action( 'manage_shop_order_posts_custom_column', array('CargonizerAdmin', 'setCustomOrderColumnValue' ), 1 );
add_action( 'woocommerce_admin_order_data_after_order_details', array('CargonizerAdmin', 'showResetLink' ), 1 );
add_filter( 'manage_edit-consignment_columns' , array('CargonizerAdmin', '_registerEditColumns') );
add_action( 'manage_consignment_posts_custom_column' , array('CargonizerAdmin', '_fillCustomColumns') , 10, 2 );
add_filter( 'manage_edit-consignment_sortable_columns', array('CargonizerAdmin', '_registerSortableColumns') );
add_action( 'manage_posts_extra_tablenav', array('CargonizerAdmin', '_addBatchButton') );
add_action( 'post_submitbox_misc_actions', array('CargonizerAdmin', 'addCargonizerActions') );
add_action( 'admin_notices', array('CargonizerAdmin', 'showAdminNotice' ) );



class CargonizerAdmin{
  public $Options;


  function __construct(){
    add_action( 'admin_menu', array($this, 'createSubmenu' ) );
    add_action( 'admin_footer', array($this, 'addTransportAgreements' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'registerScripts' ) );
  }


  function createSubmenu() {
    $page = add_submenu_page(
        'woocommerce',
        __( 'Cargonizer', 'wc-cargonizer' ),
        __( 'Cargonizer', 'wc-cargonizer' ),
        'read',
        WCC_Admin,
        array( $this, 'adminPage')
      );

    $page = add_submenu_page(
        'woocommerce',
        __( 'Consignments', 'wc-cargonizer' ),
        __( 'Consignments', 'wc-cargonizer' ),
        'read',
        'edit.php?post_type=consignment'
    );

  }


  function getTabs(){
    return array(
      'apiPage'           => __('API', 'wc-cargonizer'),
      'generalPage'       => __('Carrier', 'wc-cargonizer'),
      'parcelPage'        => __('Parcel', 'wc-cargonizer'),
      'notificationPage'  => __('Notification', 'wc-cargonizer'),
      'addressPage'       => __('Address', 'wc-cargonizer'),
      'licencePage'       => __('Licence', 'wc-cargonizer'),
      );
  }


  function adminPage() {
    global $woocommerce;
    $this->Options = new CargonizerOptions();

    $tab =  ( isset($_GET['tab'] ) && $_GET['tab'] )  ? $_GET['tab'] : 'apiPage';
    ?>
    <div class="wrap woocommerce">
      <div class="icon32" id="icon-woocommerce-importer"><br></div>
      <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
      <?php foreach ($this->getTabs() as $key => $text) {
        printf('<a href="%s" class="nav-tab %s">%s</a>', admin_url('admin.php?page='.WCC_Admin.'&tab='.$key) , (($tab == $key) ? 'nav-tab-active' : null), $text );
      }
      ?>
      </h2>
      <?php self::$tab(); ?>
    </div>
    <?php
  }


  function apiPage(){
    if ( isset($_POST['update']) ){
      $this->Options->updateOptions('Api');
      $this->showUpdateMessage ( __( 'API settings updated', 'wc-cargonizer' ) );
    }

    $this->showToolBox( __('API setttings', 'wc-cargonizer'),  $this->Options->getOptions('Api') );
  }


  function generalPage(){
    if ( isset($_POST['update']) ){
      $this->Options->updateOptions('General');
      $this->showUpdateMessage ( __( 'General settings updated', 'wc-cargonizer' ) );
    }
    else if ( isset($_GET['delete']) && $_GET['delete'] == '1' ){
      delete_option( 'cargonizer-carrier-id' );
      delete_option( 'cargonizer-carrier-products' );
      delete_option( 'cargonizer-default-printer' );
      delete_transient( 'wcc_printer_list' );
      delete_transient( 'transport_agreements' );
      $this->Options->init();
    }

    $this->showToolBox( __('General setttings', 'wc-cargonizer'), $this->Options->getOptions('General'), true );
  }


  function parcelPage(){
    if ( isset($_POST['update']) ){
      $this->Options->updateOptions('Parcel');
      $this->showUpdateMessage ( __( 'Parcel settings updated', 'wc-cargonizer' ) );
    }

    $this->showToolBox( __('Package defaults', 'wc-cargonizer'), $this->Options->getOptions('Parcel') );
  }


  function addressPage(){
    if ( isset($_POST['update']) ){
      $this->Options->updateOptions( 'Address' );
      $this->showUpdateMessage ( __( 'API settings updated', 'wc-cargonizer' ) );
    }

    $this->showToolBox( __('Address settings', 'wc-cargonizer'), $this->Options->getOptions('Address') );
  }


  function notificationPage(){
      if ( isset($_POST['update']) ){
        $this->Options->updateOptions( 'Notification' );
        $this->showUpdateMessage ( __( 'Notification settings updated', 'wc-cargonizer' ) );
      }

      $this->showToolBox( __('Notifications', 'wc-cargonizer'), $this->Options->getOptions('Notification') );
    ?>

    <div class="wcc-instruction">
      <h4><?php _e('Placeholders', 'wc-cargonizer'); ?></h4>
      <?php echo implode('<br/>', Parcel::getPlaceholders() ) ?>
    </div>
    <?php
  }


  function licencePage(){
    if ( isset($_POST['update']) ){
      $this->Options->updateOptions( 'Licence' );
      $this->showUpdateMessage ( __( 'Licence updated', 'wc-cargonizer' ) );
    }

    $this->showToolBox( __('Licence settings', 'wc-cargonizer'), $this->Options->getOptions('Licence') );

    global $plugin_file;
    $Plugin = new CargonizerUpdater($plugin_file);
    $valid = $Plugin->checkLicenceKey();
    $color = ( $valid  == '1' ) ? '#009933' : '#cc0000';
    set_transient( $Plugin->Slug.'_last_check', $valid, 0 );
    printf('<style type="text/css">.licence-key{border:2px solid %s !important; }</style>', $color );
  }


  function showToolBox( $title, $options=null, $reset = false ){ ?>
    <div class="tool-box">
      <form action="" method="POST">
        <h3 class="title"><?php echo $title; ?></h3>
        <input type="hidden" name="update" value="1" />
        <?php echo $options; ?>
        <p>
          <input type="submit" class="wcc-button save" value="<?php _e('update', 'wc-cargonizer' ); ?>">
          <?php if  ( $reset ): ?>

            <a class="wcc-button  delete" href="<?php echo $_SERVER['REQUEST_URI']."&delete=1"; ?>"><?php _e('reset', 'wc-cargonizer' );  ?></a>
          <?php endif; ?>
        </p>
      </form>
    </div>
  <?php
  }


  function showUpdateMessage( $text ){ ?>
     <div id="message" class="updated woocommerce-message wc-connect">
      <div class="squeezer">
        <h4><strong><?php echo $text; ?></strong></h4>
      </div>
    </div>
    <?php
  }


  function registerScripts(){
    if ( is_admin() ) {
      $scripts = array();
      $styles = array();

      $plugin_path =  str_replace('admin/', null, plugin_dir_url(__FILE__) );
      $path = $plugin_path.'assets/';

      // $scripts = array( 'bootstrap-datepicker.js', 'bootstrap-datepicker.no.js', 'datepicker.js', 'forms.js',  'jquery.validate.min.js', 'messages_no.js' );
      $styles = array( 'wcc-admin.css', 'wcc-bootstrap.css' );

      // include stylesheets
      foreach ($styles as $s) {
        echo '<link rel="stylesheet" href="'.$path .'css/'.$s.'" type="text/css" />' . "\n";
      }
      echo '<link rel="stylesheet" href="https://opensource.keycdn.com/fontawesome/4.7.0/font-awesome.min.css" integrity="sha384-dNpIIXE8U05kAbPhy3G1cz+yZmTzA6CY8Vg/u2L9xRnHjJiAK76m2BIEaSEV+/aU" crossorigin="anonymous">';
      // echo '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" >';

      wp_register_script( 'wcc-admin', $path. '/js/wcc-admin.js', false, '1.0.0' );
      wp_register_script( 'wcc-admin-ajax', $path. '/js/wcc-admin-ajax.js', false, '1.0.0' );
      wp_register_script( 'wcc-admin-consignment', $path. '/js/wcc-admin-consignment.js', false, '1.0.1' );
      wp_register_script( 'wcc-admin-action', $path. '/js/wcc-admin-action.js', false, '1.0.1' );
      wp_register_script( 'wcc-admin-html', $path. '/js/wcc-admin-html.js', false, '1.0.0' );
      wp_enqueue_script( 'wcc-admin-ajax' );
      wp_enqueue_script( 'wcc-admin-html' );
      wp_enqueue_script( 'wcc-admin-action' );
      wp_enqueue_script( 'wcc-admin-consignment' );
      wp_enqueue_script( 'wcc-admin' );

    }
  }


  function addTransportAgreements(){
    // _log('addTransportAgreements');
    $this->Options = new CargonizerOptions();
    $agreements = array();
    $ta = $this->Options->get('TransportAgreements');
    if ( is_array($ta) ){
      foreach ($ta as $key => $agreement) {
        $agreements[ $agreement['id'] ] = $agreement;
      }
    }
    // _log();
    if ( isset($_GET['post']) && is_numeric($_GET['post']) ){

      $carrier_id = $_GET['post'];
      $post = get_post($carrier_id);

      if ( $post->post_type == 'shop_order' ){
        $Parcel = new Parcel($_GET['post']);
        // _log($Parcel->TransportAgreementId);
        // _log($Parcel->ParcelType);
        // _log($Parcel->ParcelServices);
        // _log($Parcel->IsCargonized);
        printf( '<script>var Parcel=%s;</script>', json_encode($Parcel) );

        // carrier id
        $carrier_id =  ( $Parcel->TransportAgreementId ) ? $Parcel->TransportAgreementId : get_option( 'cargonizer-carrier-id' );
        printf( '<script>var parcel_carrier_id=%s;</script>', json_encode($carrier_id) );

        // carrier product
        $carrier_product =$Parcel->ParcelType;
        if ( !$carrier_product ){
          $carrier_product = CargonizerOptions::getDefaultCarrierProduct();
        }


        printf( '<script>var parcel_carrier_product="%s"</script>', $carrier_product );



        printf( '<script>var parcel_carrier_product_services=%s</script>', json_encode($Parcel->ParcelServices) );
        printf( '<script>var parcel_is_cargonized=%s</script>', (( $Parcel->IsCargonized ) ? 'true' : 'false') ) ;
      }
      else if ( $post->post_type = 'consignment' ){
        Consignment::getJsonObject( $_GET['post'], $echo = true );
      }


    }
    elseif ( gi($_REQUEST, 'post_type') == 'consignment' ) {
      Consignment::getJsonObject( null, $echo = true );
    }

    printf( '<script>var transport_agreements=%s;</script>', json_encode($agreements) );
  }


  public static function newCustomOrderColumn($columns){
    $new_columns = (is_array($columns)) ? $columns : array();
    unset( $new_columns['order_actions'] );

    $new_columns['wcc_consignment_id'] = __('Consignment id');
    $new_columns['wcc_tracking_url'] = __('Tracking url');

    //stop editing

    $new_columns['order_actions'] = $columns['order_actions'];
    return $new_columns;
  }


  public static function setCustomOrderColumnValue($column){
    global $post;
    $data = get_post_meta( $post->ID );
    if ( $column == 'wcc_consignment_id' ) {
      echo (isset($data['consignment_id'][0]) ? $data['consignment_id'][0] : '');
    }

    if ( $column == 'wcc_tracking_url' ) {
      $tracking_url = null;
      if ( isset($data['consignment_tracking_url'][0]) && trim($data['consignment_tracking_url'][0]) ){
        $tracking_url = sprintf('<a href="%s" target="_blank">%s</a>', $data['consignment_tracking_url'][0], __('Tracking url') );
      }

      echo $tracking_url;

    }
  }


  public static function showResetLink( $Order ){

    if ( get_post_meta( $Order->id, 'is_cargonized', true ) ){
      if ( $edit_link = get_edit_post_link($Order->id) ){
        $delete_link = $edit_link.'&wcc_action=reset_consignment';
        $delete_text = __('Logistra Cargonizer: reset consignment', 'wc-cargonizer' );

        if ( $delete_link && $delete_text ){
          $desc = __('Does not delete the consignment on cargonizer.no', 'wc-cargonizer');
          printf('<p class="form-field form-field-wide wcc-delete-invoice"><span class="wcc-delete-icon">x</span> <a href="%s">%s</a><span class="wcc-delete-desc">%s</span></p>', $delete_link, $delete_text, $desc );
        }
      }
    }


    return $Order;
  }


  public static function _registerSortableColumns( $columns ) {
    $columns['consignment-next-shipping-date'] = 'consignment-next-shipping-date';
    return $columns;
  }


  public static function _registerEditColumns($columns) {
    unset($columns['tags']);
    unset($columns['date']);
    return array_merge( $columns, CargonizerConfig::getConfig('consignment') );
  }


  public static function _fillCustomColumns( $column, $post_id ) {
    if ( self::isCustomConsignmentColumn($column) ){
      $Consignment = new Consignment ( $post_id );
      $post_meta = get_post_custom( $post_id );
      // echo self::getField($colƒumn, $post_id );
      //
      // get products subscription products => $Consignment->Products;
      // $Consignment->UserId;
      // for each products
      // wcs_user_has_subscription( $Consignment->UserId, $product_id, $status );

      // has_term( array('subscription', 'variable-subscription' ),  $taxonomy='product_type', $product_id );

      if ( $column == 'consignment-post-id' ){
        echo $post_id;
      }
      elseif ( $column == 'consignment-receiver'){
        echo gi($post_meta, '_shipping_last_name').', <br/>'.gi($post_meta, '_shipping_first_name');
      }
      else if ( $column == 'consignment-interval' ){
        if ( $interval = gi($post_meta, 'recurring_consignment_interval') ){
          printf("every %sth", $interval) ;
        }
        else{
          echo 'n/a';
        }
      }
      else if ( $column == 'consignment-next-shipping-date' ){
        $next_sd = gi($post_meta, 'consignment_next_shipping_date');
        $start_sd = gi($post_meta, 'consignment_start_date');

        $date = null;
        if ( $next_sd ){
          $date = $next_sd;
        }
        if ( $start_sd && $start_sd > $next_sd ){
          $date = $start_sd;
        }

        if ( $date ){
          echo date('d.m.Y', strtotime($date) );
        }
      }
      else if ( $column == 'consignment-actions'){

        if ( $Consignment->hasSubscriptionWarning() ){
          printf('<a href="%s" class="consignment-action warning" target="_blank">'.CargonizerIcons::warning().'</a>', get_edit_post_link( $Consignment->Id) );
        }

        printf('<a href="#" class="consignment-action ajax-create-consignment" data-post_id="%s" title="create new consignment">'.CargonizerIcons::consignment().'</a>', $post_id  );

        if ( isset($Consignment->History[0]) ){
          printf('<a href="#" class="consignment-action ajax-print-consignment" data-post_id="%s" title="print the latest consignment">'.CargonizerIcons::printer().'</a>', $post_id);
        }

        if ( $Consignment->OrderId && is_numeric($Consignment->OrderId) ){
          printf('<a href="%s" class="consignment-action" target="_blank">'.CargonizerIcons::link().'</a>', get_edit_post_link( $Consignment->OrderId) );
        }

      }
      else if ( $column == 'consignment-status'){
        $next_shipping_date = gi($post_meta, 'consignment_next_shipping_date');
        // _log('$next_shipping_date');
        // _log($next_shipping_date);

        $wtd = self::isInTime( $next_shipping_date, $today=date('Ymd') );

        $ok = false;
        if ( !$Consignment->IsRecurring && $Consignment->LastShippingDate ){
          $ok = true;
        }

        if ( $Consignment->IsRecurring && !$wtd ){
          // _log('recurring');
          $wtd2 = self::isInTime( $next_shipping_date, date('Ymd', strtotime($Consignment->LastShippingDate)) );
          if ( !$wtd2 ){
            $ok = true;
          }
          // _log($wtd2);
        }

        // _log($wtd);
        if ( !$wtd && !$ok ){
          echo self::makeStatus( 'warning', __('Create consignment', 'wc-cargonizer') );
        }
        else if ( $wtd < 0 && !$ok ){
         echo self::makeStatus( 'danger', __('Delay in dispatch', 'wc-cargonizer') );
        }
        else{
          echo self::makeStatus('success', __('OK', 'wc-cargonizer') );
        }
      }
      else if( $column == 'consignment-last-shipping-date'){
        if ( $Consignment->LastShippingDate ){
          echo date('d.m.Y @ H:i', strtotime($Consignment->LastShippingDate) );
        }
        else{
          echo 'n/a';
        }
      }
      else{
        echo null;
      }

    }
  }


  public static function addCargonizerActions( $post ){

    if ( is_object($post) && $post->post_type == 'consignment' ){
      $Consignment = new Consignment($post->ID);
      echo '<div class="wcc-meta-box-consignment">';

      printf('<a href="#" class="consignment-action ajax-main-create-consignment" data-post_id="%s" title="create new consignment">'.CargonizerIcons::consignment().'</a>', $Consignment->Id );
      if ( isset($Consignment->History[0]) ){
        printf('<a href="#" class="consignment-action ajax-main-print-consignment" data-post_id="%s" title="print the latest consignment">'.CargonizerIcons::printer().'</a>', $Consignment->Id );
      }
      echo '</div>';
    }

  }


  public static function showAdminNotice(){
    printf('<div class="wcc-admin-message alert" id="wcc-admin-message"></div>');
  }

  public static function isInTime( $next_shipping_date, $check_date ){
    $warning_time = get_option( 'cargonizer-recurring-consignments-warning-time', '1' );
    $wtd = $next_shipping_date - $check_date;

    if ( $wtd == 0 or $wtd == $warning_time ){
      return false;
    }
    else{
      return $wtd;
    }

  }


  public static function makeStatus( $status, $text ){
    return sprintf( '<div class="alert alert-%s" role="alert">%s</div>', $status, $text );
  }


  public static function isCustomConsignmentColumn($column){
    $custom_columns = CargonizerConfig::getConfig('consignment');

    if ( isset($custom_columns[$column]) ){
      return true;
    }
    else{
      return false;
    }
  }


  public static function _addBatchButton( $x=null ){
    if ( gi($_GET, 'post_type') == 'consignment' ){
      echo '<div class="alignleft actions"><a href="#" id="ajax-create-consignments" class="button">'.CargonizerIcons::consignment().' '. __('Create new consignment', 'wc-cargonizer'). '</a></div>';
    }
  }





} // end of class


new CargonizerAdmin();
<?php

add_filter( 'manage_edit-shop_order_columns', array('CargonizerAdmin', 'newCustomOrderColumn' ) );
add_action( 'manage_shop_order_posts_custom_column', array('CargonizerAdmin', 'setCustomOrderColumnValue' ), 1 );


class CargonizerAdmin{
  public $Options;


  function __construct(){
    add_action( 'admin_menu', array($this, 'createSubmenu' ) );
    add_action( 'admin_footer', array($this, 'addTransportAgreements' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'registerScripts' ) );
  }


  function createSubmenu() {
    $page = add_submenu_page('woocommerce', __( 'Cargonizer', 'wc-cargonizer' ), __( 'Cargonizer', 'wc-cargonizer' ), apply_filters( 'woocommerce_csv_product_role', 'manage_woocommerce' ), WCC_Admin, array( $this, 'adminPage') );
  }

  function getTabs(){
    return array(
      'apiPage'           => __('API', 'wc-cargonizer'),
      'generalPage'       => __('General', 'wc-cargonizer'),
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

    $this->showToolBox( __('General setttings', 'wc-cargonizer'), $this->Options->getOptions('General') );
  }


  function parcelPage(){
    if ( isset($_POST['update']) ){
      $this->Options->updateOptions('Parcel');
      $this->showUpdateMessage ( __( 'Parcel settings updated', 'wc-cargonizer' ) );
    }

    $this->showToolBox( __('Default package size', 'wc-cargonizer'), $this->Options->getOptions('Parcel') );
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


  function showToolBox( $title, $options=null ){ ?>
    <div class="tool-box">
      <form action="" method="POST">
        <h3 class="title"><?php echo $title; ?></h3>
        <input type="hidden" name="update" value="1" />
        <?php echo $options; ?>
        <p><input type="submit" class="button" value="<?php _e('update', 'wc-cargonizer' ); ?>"></p>
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
      $styles = array( 'wcc-admin.css' );

      // include stylesheets
      foreach ($styles as $s) {
        echo '<link rel="stylesheet" href="'.$path .'css/'.$s.'" type="text/css" />' . "\n";
      }

      wp_register_script( 'po-admin', $path. '/js/wcc-admin.js', false, '1.0.0' );
      wp_enqueue_script( 'po-admin' );
      // include scripts
      //    foreach ($scripts as $s) {
      //  echo '<script src="'.$path.'js/'.$s.'" ></script>' . "\n";
      // }
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
      $Parcel = new Parcel($_GET['post']);
      // _log($Parcel);
      // _log($Parcel->TransportAgreementId);
      // _log($Parcel->ParcelType);
      // _log($Parcel->ParcelServices);
      // _log($Parcel->IsCargonized);
      printf( '<script>var parcel_carrier_id=%s;</script>', $Parcel->TransportAgreementId );
      printf( '<script>var parcel_carrier_product="%s"</script>', $Parcel->ParcelType );
      printf( '<script>var parcel_carrier_product_services=%s</script>', json_encode($Parcel->ParcelServices) );
      printf( '<script>var parcel_is_cargonized=%s</script>', (( $Parcel->IsCargonized ) ? 'true' : 'false') ) ;
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






} // end of class


new CargonizerAdmin();
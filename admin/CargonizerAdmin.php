<?php

class CargonizerAdmin{
  public $Options;


  function __construct(){
    add_action( 'admin_menu', array($this, 'createSubmenu' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'registerScripts' ) );
  }


  function createSubmenu() {
    $page = add_submenu_page('woocommerce', __( 'Cargonizer', 'wc-cargonizer' ), __( 'Cargonizer', 'wc-cargonizer' ), apply_filters( 'woocommerce_csv_product_role', 'manage_woocommerce' ), WCC_Admin, array( $this, 'adminPage') );
  }


  function adminPage() {
    global $woocommerce;
    $this->Options = new CargonizerOptions();

    $tab =  ( isset($_GET['tab'] ) && $_GET['tab'] )  ? $_GET['tab'] : 'apiPage';
    ?>
    <div class="wrap woocommerce">
      <div class="icon32" id="icon-woocommerce-importer"><br></div>
      <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
      <a href="<?php echo admin_url('admin.php?page='.WCC_Admin.'&tab=apiPage') ?>" class="nav-tab <?php echo ($tab == 'apiPage') ? 'nav-tab-active' : ''; ?>"><?php _e('API', 'wc-cargonizer'); ?></a>
      <a href="<?php echo admin_url('admin.php?page='.WCC_Admin.'&tab=generalPage') ?>" class="nav-tab <?php echo ($tab == 'generalPage') ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'wc-cargonizer'); ?></a>
      <a href="<?php echo admin_url('admin.php?page='.WCC_Admin.'&tab=parcelPage'); ?>" class="nav-tab <?php echo ($tab == 'parcelPage') ? 'nav-tab-active' : ''; ?>"><?php _e('Parcel', 'wc-cargonizer'); ?></a>
      <a href="<?php echo admin_url('admin.php?page='.WCC_Admin.'&tab=notificationPage'); ?>" class="nav-tab <?php echo ($tab == 'notificationPage') ? 'nav-tab-active' : ''; ?>"><?php _e('Notification', 'wc-cargonizer'); ?></a>
      <a href="<?php echo admin_url('admin.php?page='.WCC_Admin.'&tab=addressPage'); ?>" class="nav-tab <?php echo ($tab == 'addressPage') ? 'nav-tab-active' : ''; ?>"><?php _e('Address', 'wc-cargonizer'); ?></a>
      </h2>
      <?php self::$tab(); ?>
    </div>
    <?php
  }


	public static function apiPage(){

    if ( isset($_POST['update']) ){
      CargonizerOptions::updateApiSettings();
      $this->showUpdateMessage ( __( 'API settings updated', 'wc-cargonizer' ) );
    }
    ?>
    <div class="tool-box">
      <form action="" method="POST">
        <h3 class="title"><?php _e('API setttings', 'wc-cargonizer'); ?></h3>
        <input type="hidden" name="update" value="1">
        <?php CargonizerOptions::getApiSettings(); ?>
        <p><input type="submit" class="button" value="<?php _e('update', 'wc-cargonizer' ); ?>"></p>
      </form>
    </div>
    <?php
  }


  function generalPage(){?>
    <?php
      if ( isset($_POST['update']) ){
        CargonizerOptions::updateGeneralSettings();
        $this->Options->init();
        $this->showUpdateMessage ( __( 'API settings updated', 'wc-cargonizer' ) );
      }
    ?>

    <div class="tool-box">
      <form action="" method="POST">
        <h3 class="title"><?php _e('General setttings', 'wc-cargonizer'); ?></h3>
        <input type="hidden" name="update" value="1">
        <?php $this->Options->getGeneralSettings(); ?>
        <p><input type="submit" class="button" value="<?php _e('update', 'wc-cargonizer' ); ?>"></p>
      </form>
    </div>
    <?php
  }


  function parcelPage(){?>
    <?php
      if ( isset($_POST['update']) ){
        CargonizerOptions::updateOptions('Parcel');
        $this->Options->init();
        $this->showUpdateMessage ( __( 'Parcel settings updated', 'wc-cargonizer' ) );
      }
    ?>

    <div class="tool-box">
      <form action="" method="POST">
        <h3 class="title"><?php _e('Default package size', 'wc-cargonizer'); ?></h3>
        <input type="hidden" name="update" value="1">
        <?php $this->Options->getParcelOptions(); ?>
        <p><input type="submit" class="button" value="<?php _e('update', 'wc-cargonizer' ); ?>"></p>
      </form>
    </div>
    <?php
  }


  function addressPage(){ ?>
    <?php
      if ( isset($_POST['update']) ){
        CargonizerOptions::updateOptions( 'Address' );
        $this->Options->init();
        $this->showUpdateMessage ( __( 'API settings updated', 'wc-cargonizer' ) );
      }
    ?>

    <div class="tool-box">
      <form action="" method="POST">
        <h3 class="title"><?php _e('Return address', 'wc-cargonizer'); ?></h3>
        <input type="hidden" name="update" value="1" />
        <?php $this->Options->getAddressOptions(); ?>
        <p><input type="submit" class="button" value="<?php _e('update', 'wc-cargonizer' ); ?>"></p>
      </form>
    </div>
    <?php
  }


  function notificationPage(){ ?>
    <?php
      if ( isset($_POST['update']) ){
        CargonizerOptions::updateOptions( 'Notification' );
        $this->Options->init();
        $this->showUpdateMessage ( __( 'Notification settings updated', 'wc-cargonizer' ) );
      }
    ?>

    <div class="tool-box">
      <form action="" method="POST">
        <h3 class="title"><?php _e('Notifications', 'wc-cargonizer'); ?></h3>

        <div class="wcc-instruction">
          <h4><?php _e('Placeholders', 'wc-cargonizer'); ?></h4>
          <?php echo implode('<br/>', Parcel::getPlaceholders() ) ?>

        </div>

        <input type="hidden" name="update" value="1" />
        <?php $this->Options->getNotificationOptions(); ?>
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


  public static function licencePage(){ ?>
    <?php if ( isset($_POST['update']) ): ?>
      <div id="message" class="updated woocommerce-message wc-connect">
        <div class="squeezer">
          <?php CargonizerOptions::updateLicenceSettings(); ?>
          <h4><?php _e( '<strong>Licence key updated</strong> ', 'wc-cargonizer' ); ?></h4>
        </div>
      </div>
    <?php endif; ?>

    <div class="tool-box">
      <form action="" method="POST">
        <h3 class="title"><?php _e('Licence key', 'wc-cargonizer'); ?></h3>
        <input type="hidden" name="update" value="1">
        <?php CargonizerOptions::licenceSettings(); ?>
        <p><input type="submit" class="button" value="<?php _e('update', 'wc-cargonizer' ); ?>"></p>
      </form>
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
      $styles = array( 'admin.css' );

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



} // end of class


new CargonizerAdmin();
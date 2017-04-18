<?php


class CargonizerAdmin{
  public $Options;

  function __construct(){
    add_action( 'admin_menu', array($this, 'createSubmenu' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'registerScripts' ) );
  }


  function createSubmenu() {
    $page = add_submenu_page('woocommerce', __( 'Cargonizer', MB_LANG ), __( 'Cargonizer', MB_LANG ), apply_filters( 'woocommerce_csv_product_role', 'manage_woocommerce' ), WCC_Admin, array( $this, 'adminPage') );
  }


  function adminPage() {
    global $woocommerce;
    $this->Options = new CargonizerOptions();

    $tab =  ( isset($_GET['tab'] ) && $_GET['tab'] )  ? $_GET['tab'] : 'apiPage';
    ?>
    <div class="wrap woocommerce">
      <div class="icon32" id="icon-woocommerce-importer"><br></div>
      <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
      <a href="<?php echo admin_url('admin.php?page='.WCC_Admin.'&tab=apiPage') ?>" class="nav-tab <?php echo ($tab == 'import') ? 'nav-tab-active' : ''; ?>"><?php _e('API', MB_LANG); ?></a>
      <a href="<?php echo admin_url('admin.php?page='.WCC_Admin.'&tab=generalPage') ?>" class="nav-tab <?php echo ($tab == 'import') ? 'nav-tab-active' : ''; ?>"><?php _e('General', MB_LANG); ?></a>
      <a href="<?php echo admin_url('admin.php?page='.WCC_Admin.'&tab=parcelPage'); ?>" class="nav-tab <?php echo ($tab == 'licence') ? 'nav-tab-active' : ''; ?>"><?php _e('Parcel', MB_LANG); ?></a>
      </h2>
      <?php self::$tab(); ?>
    </div>
    <?php
  }


	public static function apiPage(){
    ?>
    <?php if ( isset($_POST['update']) ): ?>
      <div id="message" class="updated woocommerce-message wc-connect">
        <div class="squeezer">
          <?php CargonizerOptions::updateApiSettings(); ?>
          <h4><strong><?php _e( 'API settings updated', MB_LANG ); ?></strong></h4>
        </div>
      </div>
    <?php endif; ?>

    <div class="tool-box">
      <form action="" method="POST">
        <h3 class="title"><?php _e('API setttings', MB_LANG); ?></h3>
        <input type="hidden" name="update" value="1">
        <?php CargonizerOptions::getApiSettings(); ?>
        <p><input type="submit" class="button" value="<?php _e('update', MB_LANG ); ?>"></p>
      </form>
    </div>
    <?php
  }


  function generalPage(){?>
    <?php if ( isset($_POST['update']) ): ?>
    <div id="message" class="updated woocommerce-message wc-connect">
      <div class="squeezer">
        <?php CargonizerOptions::updateGeneralSettings(); ?>
        <?php $this->Options->init(); ?>
        <h4><?php _e( '<strong>API settings updated</strong> ', MB_LANG ); ?></h4>
      </div>
    </div>
    <?php endif; ?>

    <div class="tool-box">
      <form action="" method="POST">
        <h3 class="title"><?php _e('General setttings', MB_LANG); ?></h3>
        <input type="hidden" name="update" value="1">
        <?php $this->Options->getGeneralSettings(); ?>
        <p><input type="submit" class="button" value="<?php _e('update', MB_LANG ); ?>"></p>
      </form>
    </div>
    <?php
  }


  function parcelPage(){?>
    <?php if ( isset($_POST['update']) ): ?>
    <div id="message" class="updated woocommerce-message wc-connect">
      <div class="squeezer">
        <?php CargonizerOptions::updateOptions('Parcel'); ?>
        <?php $this->Options->init(); ?>
        <h4><strong><?php _e( 'API settings updated', MB_LANG ); ?></strong></h4>
      </div>
    </div>
    <?php endif; ?>

    <div class="tool-box">
      <form action="" method="POST">
        <h3 class="title"><?php _e('Default package size', MB_LANG); ?></h3>
        <input type="hidden" name="update" value="1">
        <?php $this->Options->getParcelOptions(); ?>
        <p><input type="submit" class="button" value="<?php _e('update', MB_LANG ); ?>"></p>
      </form>
    </div>
    <?php
  }


  public static function licencePage(){ ?>
    <?php if ( isset($_POST['update']) ): ?>
      <div id="message" class="updated woocommerce-message wc-connect">
        <div class="squeezer">
          <?php CargonizerOptions::updateLicenceSettings(); ?>
          <h4><?php _e( '<strong>Licence key updated</strong> ', MB_LANG ); ?></h4>
        </div>
      </div>
    <?php endif; ?>

    <div class="tool-box">
      <form action="" method="POST">
        <h3 class="title"><?php _e('Licence key', MB_LANG); ?></h3>
        <input type="hidden" name="update" value="1">
        <?php CargonizerOptions::licenceSettings(); ?>
        <p><input type="submit" class="button" value="<?php _e('update', MB_LANG ); ?>"></p>
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

      // include scripts
      //    foreach ($scripts as $s) {
      //  echo '<script src="'.$path.'js/'.$s.'" ></script>' . "\n";
      // }
    }
  }



} // end of class


new CargonizerAdmin();
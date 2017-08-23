<?php

class ConsignmentController extends CargonizerCommonController{
  protected $Order;
  protected $WC_Order;
  protected $Settings;

  function __construct( ){
    parent::__construct();
    add_action( 'save_post', array($this, 'preventRevision'), 1, 1 );
    add_action( 'save_post', array($this, 'save'), 10, 1 );
  }


  function save( $post_id ){
    global $post_id;

    if ( !isset($_REQUEST['post_ID']) or $_REQUEST['post_ID'] != $post_id ){
      return false;
    }

    if ( $Consignment = $this->isConsignment( $return_object=true ) ){
      _log('ConsignmentController::save()');
      $tabs = array('Parcel', 'Consignee');
      //_log($_REQUEST);
      $ConsignmentOptions = new AdminConsignmentOptions();

      foreach ($tabs as $key => $tab) {
        $method = 'load'.$tab.'Options';
        $options = $ConsignmentOptions->$method();

        foreach ($options as $oi => $o) {
           if ( !isset($o['save_post']) or (isset($o['save_post']) && $o['save_post']) ){
            $index = $o['name'];
            $meta_value = ( isset($_POST[$index]) ) ? $_POST[$index] : null;

            if ( $result = update_post_meta( $post_id, $index, $meta_value ) ){
              _log('updated: '.$index);
              _log($meta_value);
              _log(' ');
            }
          }
          // else{
          //  _log('exception');
          //  _log($o);
          // }
        }
      }
    }
  }



  public static function createConsignment( $post_id  ){
    _log('ConsignmentController::createConsignment('.$post_id.')');

    $response = false;
    if ( is_numeric($post_id) ){

      $Consignment = new Consignment( $post_id );
      $CargonizeXml = new CargonizeXml( $Consignment->prepareExport() );
      $CargonizerApi = new CargonizerApi();
      $result = null;
      _log('post consignment');
      $result = $CargonizerApi->postConsignment($CargonizeXml->Xml);
      if ( $result ){
        // _log($result);
        if ( is_array($result) && isset($result['consignments']['consignment']['errors']) ){
          _log('consignment: error');
          $response = $result['consignments']['consignment']['errors']['error'];
        }
        elseif ( is_array($result) && isset($result['consignments']['consignment']) ){
          _log('consignment: success');
          $response = $result;

          $Consignment->setNextShippingDate( $auto_inc=true );
          if ( $new_entry = $Consignment->updateHistory( $result['consignments']['consignment'] ) ){
            $Consignment->notifyCustomer( $new_entry );
          }

          // update order
          if ( $Consignment->OrderId ){
            _log('has order id');
            $Order = new ShopOrder( $Consignment->OrderId );
            $Order->setCargonized();
            $Order->saveConsignmentDetails( $consignment = $result['consignments']['consignment'] );
            $Order->addNote();
          }
          else{
            _log('no order id');
          }
        }
        else{
          _log('consignment: else error');
          _log($result);
        }
      }
      else{
        _log('no result');
      }
    }

    return $response;
  }


  function preventRevision($post_id){
    $post = get_post($post_id);

    if ( wp_is_post_revision( $post_id ) or !$post->post_title ){
      return;
    }
  }


  function acf_filterOrderProducts( $field ){
    global $post_id;

    if ( $post_id  ){
      $Consignment = new Consignment($post_id);

      $html = $rows = null;

      if ( is_array($Consignment->OrderProducts) && !empty($Consignment->OrderProducts) ){
        if ( isset($Consignment->Subscriptions) && $Consignment->Subscriptions ){
          foreach ( $Consignment->OrderProducts as $key => $product) {
            // _log('$product');
            // _log($product);
            // _log($log);
            $status = CargonizerIcons::ok();
            // _log($Consignment->Subscriptions);
            $post_status = str_replace('wc-', null, $Consignment->Subscriptions->post_status);

            if (
              // if has subscription product and subscription is not active
              $Consignment->isSubscriptionProduct($product['product_id']) && !$Consignment->isSubscriptionProductActive($product['product_id'])
              or
              $post_status != 'active' // if subscription has an end date

            ){
              $status = CargonizerIcons::warning();
            }

            $rows .= sprintf(
                '<tr><td>%s</td> <td>%s</td> <td>%s</td> <td>%s</td> <td >%s</td></tr>',
                $product['product_id'],
                $product['name'],
                $product['qty'],
                ( ( isset($product['is_subscription']) && $product['is_subscription'] ) ? 'yes' : 'no' ),
                $status. ' <a href="'.get_edit_post_link( $Consignment->Subscriptions->ID ).'" target="_blank">'.$post_status.'</a>'
            );
            // _log($log);
          }
        }
      }

      if ( $rows ){
        $th = '<tr> <th>%s</th> <th>%s</th> <th>%s</th> <th>%s</th> <th>%s</th>';
        $th = sprintf(
            $th,
            __('Id', 'wc-cargonizer'),
            __('Name', 'wc-cargonizer'),
            __('Count', 'wc-cargonizer'),
            __('Subscription', 'wc-cargonizer'),
            __('Status')
          );
        $html = '<table class="table">'. $th.$rows. '</table>';
      }

      $field['message'] = str_replace('@acf_consignment_products@', $html, $field['message'] );

    }


    return $field;
  }


} // end of class

new ConsignmentController();

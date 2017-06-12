var TransportAgreement = null;
var TransportProduct = null;

jQuery(document).ready(function(){
  initPrintBtn();
  initCarrier();
  initRecurringConsignment();
});


function initRecurringConsignment(){
  if ( jQuery('.post-type-shop_order').length ){
    jQuery('#acf-field_593e32538dbca-1').attr('checked', false);
    initCopyFromParcels();

    var carrier_id = jQuery('#acf-field_593e3056536ce').val();
    updateConsignmentCarrierProducts( carrier_id, false );
    updateConsignmentProductServices( false );
  }

  initRecurringCarrier();
}


function initRecurringCarrier(){
  jQuery('#acf-field_593e3056536ce').change(function(){
    var carrier_id = jQuery(this).val();
    updateConsignmentCarrier(carrier_id);
    updateConsignmentCarrierProducts( carrier_id, false );
    updateConsignmentProductServices(false );
  })
}


function initCopyFromParcels(){
  jQuery('#acf-field_593e32538dbca-1').change(
    function(){
      if ( jQuery('#acf-field_593e32538dbca-1:checked').length ){
        copyFromParcels();
      }
      else{
        _log('not checked');
      }
    }
  );
}


function copyFromParcels(){
  var carrier_id = jQuery('#acf-field_56cd64c524656').val();
  updateConsignmentCarrier( carrier_id );
  updateConsignmentCarrierProducts( carrier_id, true );
  updateConsignmentProductServices( true );
  updateConsignmentMessage();

  jQuery('#acf-field_593e32538dbca-1').attr('checked', false);
}


function updateConsignmentCarrier( carrier_id ){
  jQuery('#acf-field_593e3056536ce option[value='+carrier_id+']').attr('selected', true );
}


function updateConsignmentMessage(){
  // _log('updateConsignmentMessage');
  var message = jQuery('#acf-field_56cead8e7fd30').val();
  jQuery('#acf-field_593e3090536cf').val( message );
}


function updateConsignmentProductServices( copy ){
  // _log('updateConsignmentProductServices');
  product_services = getProductServices( '593e30a5536d0', '593e30319f667');
  // _log(product_services);
  if ( product_services ){
    jQuery('.acf-field.acf-field-593e30319f667 .acf-checkbox-list').html( product_services );

    if( copy ){
      checked_services = jQuery('.acf-field-59086fd6633fa input:checked');
      if ( typeof checked_services !== 'undefined' && checked_services.length ){
        jQuery.each(
          checked_services,
          function(index, field) {
            jQuery('.acf-field.acf-field-593e30319f667 .acf-checkbox-list input[value='+field.value+']').attr('checked', true );
          }
        );
      }
    }
    else{
      if ( typeof Parcel.RecurringConsignmentServices !== 'undefined' && Parcel.RecurringConsignmentServices.length ){
        jQuery.each( Parcel.RecurringConsignmentServices, function(index, service ){
          // _log(service);
          jQuery('.acf-field.acf-field-593e30319f667 .acf-checkbox-list input[value='+service+']').attr('checked', true );
        });
      }

    }

  }
}


function updateConsignmentCarrierProducts( carrier_id, copy ){
  // _log('updateConsignmentCarrierProducts');
  var options = getCarrierProducts( carrier_id );

  if ( options.length ){
    jQuery('#acf-field_593e30a5536d0').html(options);

    if ( copy ){
      var product_index = jQuery('#acf-field_56cec446a4498 option:checked').index();

      product_index = parseInt(product_index);
      if ( product_index != 'NaN' ){
        product_index += 1;
        jQuery('#acf-field_593e30a5536d0 option:nth-child('+product_index+')').attr('selected', true );
      }

    }
    else{

      // _log('set saved value');
      // _log(Parcel.RecurringConsignmentType);
      if ( typeof Parcel.RecurringConsignmentType !== 'undefined' ){
        var options = jQuery('#acf-field_593e30a5536d0 option');
        if ( typeof options == 'object' && options.length ){
          // _log(options);
          jQuery.each( options, function(index, option){

            if ( option.value == Parcel.RecurringConsignmentType ){
              // _log('found');
              // _log(index);
              // _log(option.text);

              var pattern = '#acf-field_593e30a5536d0 option:nth-child('+(option.index+1) +')';
              // _log( pattern );
              jQuery( pattern ).attr('selected', true);
            }
          });
        }
      }
    }
  }

}


function initPrintBtn(){
  if ( jQuery('#acf-field_56cee621c7cd1').length ){
    var consignment_id = jQuery('#acf-field_56cee621c7cd1').val();
    if ( consignment_id.length ){
      var print_button = '<div class="acf-field acf-field-text"><a href="#" id="js_print-order" title="Print order" class="button button-primary">Print</a><span id="wcc-print-response" class="wcc-instruction"></span></div>'
      // jQuery('.inside.acf-fields').append(print_button);
      jQuery(print_button).insertAfter('.inside.acf-fields .acf-field-56cee6476fb1d');

      jQuery('#js_print-order').click(function(){
        jQuery('#wcc-print-response').html();
        printOrder();
      });
    }
  }
}


function printOrder(){
  var post_id = jQuery('#post_ID').val();
  if ( post_id.length ){
    var data = {
      'action': 'wcc_print_order',
      'order_id': post_id
    };

    jQuery.post(ajaxurl, data, function(response) {
      console.log(response);

      if ( typeof response !== 'undefined' && response != '1' ){
        jQuery('#wcc-print-response').html( response );
      }
    });
  }
}


function checkIfCargonized(){
  if (typeof parcel_is_cargonized !== 'undefined' &&  parcel_is_cargonized == true ){
    // select
    jQuery('#acf-field_56cd64c524655, #acf-field_56cd64c524656, #acf-field_56cec446a4498').attr('disabled', true);
    // checkbox
    jQuery('.acf-field.acf-field-59086fd6633fa input').attr('disabled', true );

    // parcels
    jQuery('.acf-field.acf-field-56cc575e16e1c input, #acf-field_56cead8e7fd30').attr('readonly', true);
    jQuery('.acf-field.acf-field-56cc575e16e1c select').attr('disabled', true);

    // acf
    jQuery('.acf-field.acf-field-56cc575e16e1c .acf-repeater-add-row').hide();
    jQuery('.acf-field.acf-field-56cc575e16e1c .acf-repeater-remove-row').hide();
  }
}


function initCarrier(){
  // _log('initCarrier');

  // changed carrier
  jQuery('#acf-field_56cd64c524656').change(function(){
    var carrier_id = jQuery(this).val();
    updateCarrierProducts( carrier_id );
    updateProductServices();
  });

  // changed product
  jQuery('#acf-field_56cec446a4498').change(function(){
    updateProductServices();
    updateProductTypes();
  });


  if ( jQuery('#acf-field_56cd64c524656').length ){
    var carrier_id = jQuery('#acf-field_56cd64c524656').val();
    if ( carrier_id ){
      updateCarrierProducts( carrier_id );

      if ( parcel_carrier_product.length ){
        // _log('has carrier product');
        var query = '#acf-field_56cec446a4498 option[value="'+parcel_carrier_product+'"]';
        jQuery(query).attr('selected', true);
      }


      updateProductServices();
      if ( typeof parcel_carrier_product_services !== 'undefined' &&  parcel_carrier_product_services != null && parcel_carrier_product_services.length ){
        for (var i = 0; i < parcel_carrier_product_services.length; i++) {
          // _log( parcel_carrier_product_services[i] );
          jQuery('input[value="'+parcel_carrier_product_services[i]+'"]').attr('checked', true);
        };
      }

      updateProductTypes();
    }
  }

  checkIfCargonized();
}


function getCarrierProducts( carrier_id ){
  var options = null;

  if ( typeof transport_agreements[carrier_id] !== 'undefined' ){
    TransportAgreement = transport_agreements[carrier_id];

    options = '';
    for (var i = 0; i < TransportAgreement.products.length; i++) {
      //_log( TransportAgreement .products[i] );
      var product_name = TransportAgreement.products[i].name;
      var product_id = TransportAgreement.products[i].identifier;

      if ( typeof TransportAgreement.products[i].types !== 'undefined' ){
        for ( var type_index in TransportAgreement.products[i].types ){
          options += makeOption( product_name+" ("+TransportAgreement.products[i].types[type_index]+")" , product_id+"|"+type_index );
        }
      }
      else{
        options += makeOption( product_name, product_id );
      }
    };
  }

  return options;

}



function updateCarrierProducts( carrier_id ){
  var options = getCarrierProducts( carrier_id );

  if ( options.length ){
    jQuery('#acf-field_56cec446a4498').html(options);
  }
}


function getProductServices( product_field_id, services_field_id ){
  // _log('getProductServices');
  var product_services = null;
  var product_id = jQuery('#acf-field_'+product_field_id).val();

  // _log(product_id);
  if ( typeof product_id !== 'undefined' && product_id != 0 ){
    var identifier = null;
    // _log(product_id);
    var pid_tmp = product_id.split('|');

    // _log(pid_tmp);
    if ( typeof pid_tmp[0] !== 'undefined' ){
      identifier =  pid_tmp[0];
    }

    // _log( 'identifier '+ identifier );
    TransportProduct = null;

    if ( identifier ){
      for (var i = 0; i < TransportAgreement.products.length; i++) {
        var product = TransportAgreement.products[i];

        // _log('product');
        // _log(product.identifier);
        if ( product.identifier == identifier ){
          TransportProduct = product;
        }
      };
    }


    _log('TransportProduct');
    _log(TransportProduct);
    product_services = '';
    if ( typeof TransportProduct.services !== 'undefined' ){
      // _log( 'Services: '+TransportProduct.services.length);
      for (var i = 0; i < TransportProduct.services.length ; i++) {
        // _log ( TransportProduct.services[i] );
        var Service =  TransportProduct.services[i];
        // _log('Service');
        // _log(Service);
        product_services += makeCheckbox( services_field_id, Service.name,  Service.identifier );
      };
    }
  }

  return product_services;
}


function updateProductServices(){
  // _log('updateProductServices');
  // _log (TransportAgreement );
  if ( product_services = getProductServices( '56cec446a4498', '59086fd6633fa') ){
    jQuery('.acf-field.acf-field-59086fd6633fa .acf-checkbox-list').html( product_services );
  }

}


function updateProductTypes(){
  // _log('updateProductTypes');
  // _log(TransportProduct);
  if ( TransportProduct && typeof TransportProduct.types !== 'undefined' ){
    var options = makeOption( 'select parcel type', '' );
    for ( type_index in TransportProduct.types ){
      // _log( type_index );
      options += makeOption( TransportProduct.types[type_index], type_index );
    }

    jQuery('.acf-field.acf-field-56cc575e16e1c select').html(options);
  }
}


function makeCheckbox( id, title, value ){
  return '<li><label><input type="checkbox" id="acf-field_'+id+'-'+value+'" name="acf[field_'+id+'][]" value="'+value+'">'+title+'</label></li>';
}



function makeOption( title, value ){
  return '<option value="'+value+'">'+title+'</option>';
}



function _log( message ){
  console.log(message);
}
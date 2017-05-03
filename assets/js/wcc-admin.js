var TransportAgreement = null;
var TransportProduct = null;

jQuery(document).ready(function(){
  initPrintBtn();
  initCarrier();
});

function initPrintBtn(){
  if ( jQuery('#acf-field_56cee621c7cd1').length ){
    var consignment_id = jQuery('#acf-field_56cee621c7cd1').val();
    if ( consignment_id.length ){
      var print_button = '<div class="acf-field acf-field-text"><a href="#" id="js_print-order" title="Print order" class="button button-primary">Print</a></div>'
      // jQuery('.inside.acf-fields').append(print_button);
      jQuery(print_button).insertAfter('.inside.acf-fields .acf-field-56cee6476fb1d');

      jQuery('#js_print-order').click(function(){
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
    });
  }
}


function checkIfCargonized(){
  if ( parcel_is_cargonized == true ){
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
  jQuery('#acf-field_56cd64c524656').change(function(){
    var carrier_id = jQuery(this).val();
    updateCarrierProducts( carrier_id );
    updateProductServices();
  });

  jQuery('#acf-field_56cec446a4498').change(function(){
    updateProductServices();
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
      if ( parcel_carrier_product_services.length ){
        for (var i = 0; i < parcel_carrier_product_services.length; i++) {
          // _log( parcel_carrier_product_services[i] );
          jQuery('input[value="'+parcel_carrier_product_services[i]+'"]').attr('checked', true);
        };
      }
    }
  }

  checkIfCargonized();
}


function updateCarrierProducts( carrier_id ){
  // _log('updateCarrierProducts');
  if ( typeof transport_agreements[carrier_id] !== 'undefined' ){
    TransportAgreement = transport_agreements[carrier_id];

    var options = '';
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


    if ( options.length ){
      jQuery('#acf-field_56cec446a4498').html(options);
    }
  }
}


function updateProductServices(){
  // _log('updateProductServices');
  // _log (TransportAgreement );
  var product_id = jQuery('#acf-field_56cec446a4498').val();
  var identifier = null;
  // _log(product_id);
  var pid_tmp = product_id.split('|');

  if ( typeof pid_tmp[0] !== 'undefined' ){
    identifier =  pid_tmp[0];
  }

  //_log( 'identifier '+ identifier );
  TransportProduct = null;

  if ( identifier ){
    for (var i = 0; i < TransportAgreement.products.length; i++) {
      var product = TransportAgreement.products[i];

      //_log(product.identifier);
      if ( product.identifier == identifier ){
        TransportProduct = product;
      }
    };
  }


  // _log('TransportProduct');
  // _log(TransportProduct);
  var product_services = '';
  if ( typeof TransportProduct.services !== 'undefined' ){
    // _log( 'Services: '+TransportProduct.services.length);
    for (var i = 0; i < TransportProduct.services.length ; i++) {
      // _log ( TransportProduct.services[i] );
      var Service =  TransportProduct.services[i];
      // _log('Service');
      // _log(Service);
      product_services += makeCheckbox( Service.name,  Service.identifier );
    };
  }

  jQuery('.acf-field.acf-field-59086fd6633fa .acf-checkbox-list').html( product_services );
}




function makeCheckbox( title, value ){
  return '<li><label><input type="checkbox" id="acf-field_59086fd6633fa-'+value+'" name="acf[field_59086fd6633fa][]" value="'+value+'">'+title+'</label></li>';
}



function makeOption( title, value ){
  return '<option value="'+value+'">'+title+'</option>';
}



function _log( message ){
  console.log(message);
}
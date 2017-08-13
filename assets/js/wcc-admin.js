acf_carrier_id                      = '56cd64c524656';
acf_parcel_type                     = '56cec446a4498';
acf_parcel_services                 = '59086fd6633fa';
acf_consignment_items               = '56cc575e16e1c';
acf_recurring_carrier_id            = '593e3056536ce';
acf_recurring_consignment_type      = '593e30a5536d0';
acf_recurring_consignment_services  = '593e30319f667';
acf_recurring_consignment_items     = '593e2ee07bdd4';
acf_copy_from_parcel                = '593e32538dbca-1';
acf_consignment_carrier_id          = '593e706d3f51f';


var TransportAgreement = null;
var TransportProduct = null;

jQuery(document).ready(function(){
  initOrderConsignment();
  initRecurringConsignment();
  initWccCarrierProducts();
});


function initWccCarrierProducts(){
  jQuery('.wcc-carrier-products').click(
    function(){
      if ( jQuery(this).attr('checked') ){
        jQuery('.wcc-carrier-products').attr('checked', false);
        jQuery(this).attr('checked', true);
      }
    }
  );
}


function initRecurringConsignment(){
  if ( jQuery('.post-type-shop_order').length ){
    carrier_id = getRecurringCarrierId();
    if ( typeof carrier_id !== 'undefined' ){
      jQuery('#acf-field_'+acf_copy_from_parcel).attr('checked', false); // uncheck copy from parcel
      initCopyFromParcels();
      updateRecurringConsignmentCarrierProducts( carrier_id, false );
      updateRecurringConsignmentProductServices( false );
      updateRecurringConsignmentProductTypes();
    }
  }

  jQuery('#acf-field_'+acf_recurring_carrier_id).change(function(){
    var carrier_id = jQuery(this).val();
    updateRecurringConsignmentCarrier(carrier_id);
    updateRecurringConsignmentCarrierProducts( carrier_id, false );
    updateRecurringConsignmentProductServices(false );
    updateRecurringConsignmentProductTypes();
  });


  jQuery('#acf-field_'+acf_recurring_consignment_type).change(function(){
    _log('change');
    updateRecurringConsignmentProductServices(false );
    updateRecurringConsignmentProductTypes();
  });
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


function initOrderConsignment(){

  // changed carrier
  jQuery('#acf-field_'+acf_carrier_id).change(function(){
    var carrier_id = getCarrierId();
    updateCarrierProducts( carrier_id );
    updateProductServices();
    updateProductTypes();
  });

  // changed product
  jQuery('#acf-field_'+acf_parcel_type).change(function(){
    updateTransportAgreement( getCarrierId() );
    updateProductServices();
    updateProductTypes();
  });


  // if carrier exists
 if ( jQuery('#acf-field_'+acf_carrier_id).length && typeof ShopOrder !== 'undefined' ){
    var carrier_id = jQuery('#acf-field_'+acf_carrier_id).val();

    
    if ( carrier_id ){
      updateCarrierProducts( carrier_id );

      if ( typeof ShopOrder.ParcelType !== 'undefined' && ShopOrder.ParcelType.length ){
        var query = '#acf-field_'+acf_parcel_type+' option[value="'+ShopOrder.ParcelType+'"]';
        jQuery(query).attr('selected', true);
      }

      updateProductServices();
      
      if ( typeof ShopOrder.ParcelServices !== 'undefined' && ShopOrder.ParcelServices != null && ShopOrder.ParcelServices.length ){ 
        for (var i = 0; i < ShopOrder.ParcelServices.length; i++) {
          jQuery('input[value="'+ShopOrder.ParcelServices[i]+'"]').attr('checked', true);
        };
      }
          
    }
  
    //updateProductTypes();
     
  } 

  checkIfCargonized();
}



function checkIfCargonized(){

  if (typeof ShopOrder !== 'undefined' &&  ShopOrder.IsCargonized == true ){
    // select
    jQuery('#acf-field_56cd64c524655, #acf-field_56cd64c524656, #acf-field_56cec446a4498').attr('disabled', true);
    // checkbox
    jQuery('.acf-field.acf-field-59086fd6633fa input').attr('disabled', true );

    // date
    jQuery('div[data-name=parcel_shipping_date] input').attr('disabled', true );

    // parcels
    jQuery('.acf-field.acf-field-56cc575e16e1c input, #acf-field_56cead8e7fd30').attr('readonly', true);
    jQuery('.acf-field.acf-field-56cc575e16e1c select').attr('disabled', true);

    // acf
    jQuery('.acf-field.acf-field-56cc575e16e1c .acf-repeater-add-row').hide();
    jQuery('.acf-field.acf-field-56cc575e16e1c .acf-repeater-remove-row').hide();
  }
}


function getCarrierId(){
  return jQuery('#acf-field_'+acf_carrier_id).val();
}


function getRecurringCarrierId(){
  return jQuery('#acf-field_'+acf_recurring_carrier_id).val();
}


function getCarrierProducts( carrier_id ){
  var options = null;

  TransportAgreement = updateTransportAgreement(carrier_id);

  if ( TransportAgreement ){
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


function getProductServices( product_field_id, services_field_id ){
  //_log('getProductServices: '+ product_field_id);
  var product_services = null;
  var product_id = jQuery('#acf-field_'+product_field_id).val();

  // _log(product_id);
  if ( typeof product_id !== 'undefined' && product_id != 0 ){
    var identifier = null;
    // _log(product_id);
    var pid_tmp = product_id.split('|');

    // _log(pid_tmp);
    if ( typeof pid_tmp[0] !== 'undefined' ){
      identifier = pid_tmp[0];
    }

    //_log( 'identifier '+ identifier );
    TransportProduct = null;

    if ( identifier ){
      for (var i = 0; i < TransportAgreement.products.length; i++) {
        var product = TransportAgreement.products[i];

        if ( product.identifier == identifier ){
          TransportProduct = product;
        }
      };
    }


    // _log('TransportProduct');
    // _log(TransportProduct);
    product_services = '';
    if ( typeof TransportProduct.services !== 'undefined' ){
      //_log( 'Services: '+TransportProduct.services.length);
      for (var i = 0; i < TransportProduct.services.length ; i++) {
        // _log ( TransportProduct.services[i] );
        var Service = TransportProduct.services[i];
        // _log('Service');
        // _log(Service);
        product_services += makeCheckbox( services_field_id, Service.name,  Service.identifier );
      };
    }
  }


  return product_services;
}


function _log( message ){
  console.log(message);
}
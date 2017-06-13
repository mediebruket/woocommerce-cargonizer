var TransportAgreement = null;
var TransportProduct = null;
acf_consignment_product = '593e70ff3f520';
acf_consignment_product_services = '593e71193f521';

jQuery(document).ready(function(){
  if ( jQuery('body.post-type-consignment').length ){
    initConsignment();
  }
});


function initConsignment(){
  updateConsignmentCarrierProducts();
  updateConsignmentProductServices( false );
  //  updateRecurringConsignmentProductTypes();
}


function getConsignmentCarrierId(){
  return jQuery('#acf-field_'+acf_consignment_carrier_id).val();
}


function updateConsignmentCarrierProducts() {
  _log('updateConsignmentCarrierProducts');
  carrier_id = Consignment.CarrierId;
  _log(carrier_id);

  var options = getCarrierProducts( carrier_id );

  _log(options);

  if ( options.length ){
    jQuery('#acf-field_'+acf_consignment_product).html(options);


    _log('set saved value');
    // _log(Parcel.RecurringConsignmentType);
    if ( typeof Consignment.CarrierProduct !== 'undefined' ){
      var options = jQuery('#acf-field_'+acf_consignment_product+' option');
      if ( typeof options == 'object' && options.length ){
        // _log(options);
        jQuery.each( options, function(index, option){
          if ( option.value == Consignment.CarrierProduct ){
            var pattern = '#acf-field_'+acf_consignment_product+' option:nth-child('+(option.index+1) +')';
            // _log( pattern );
            jQuery( pattern ).attr('selected', true);
          }
        });
      }
    }
  }
}


function updateConsignmentProductServices(){

  product_services = getProductServices( acf_consignment_product, acf_consignment_product_services );
  // _log(product_services);
  if ( product_services ){
    jQuery('.acf-field.acf-field-'+acf_consignment_product_services+' .acf-checkbox-list').html( product_services );

    if ( typeof Consignment.CarrierProductServices !== 'undefined' && Consignment.CarrierProductServices.length ){
      jQuery.each( Consignment.CarrierProductServices, function(index, service ){
        // _log(service);
        jQuery('.acf-field.acf-field-'+acf_consignment_product_services+' .acf-checkbox-list input[value='+service+']').attr('checked', true );
      });
    }
  }
}


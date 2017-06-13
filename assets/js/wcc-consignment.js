var TransportAgreement = null;
var TransportProduct = null;

jQuery(document).ready(function(){
  if ( jQuery('body.post-type-consignment').length ){
    initConsignment();
  }
});


function initConsignment(){
  updateConsignmentCarrierProducts();
  //  updateRecurringConsignmentProductServices( false );
  //  updateRecurringConsignmentProductTypes();
}


function getConsignmentCarrierId(){
  return jQuery('#acf-field_'+acf_consignment_carrier_id).val();
}


function updateConsignmentCarrierProducts() {
  carrier_id = Consignment.Id;
  // _log('updateRecurringConsignmentCarrierProducts');
  var options = getCarrierProducts( carrier_id );

  var acf_consignment_product = '593e70ff3f520';
  if ( options.length ){
    jQuery('#acf-field_'+acf_consignment_product).html(options);


    _log('set saved value');
    _log(Parcel.RecurringConsignmentType);
    if ( typeof Consignment.CarrierProduct !== 'undefined' ){
      var options = jQuery('#acf-field_'+acf_consignment_product+' option');
      if ( typeof options == 'object' && options.length ){
        // _log(options);
        jQuery.each( options, function(index, option){
          if ( option.value == Consignment.RecurringConsignmentType ){
            var pattern = '#acf-field_'+acf_consignment_product+' option:nth-child('+(option.index+1) +')';
            // _log( pattern );
            jQuery( pattern ).attr('selected', true);
          }
        });
      }
    }

  }


}


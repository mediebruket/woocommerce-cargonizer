function copyFromParcels(){
  var carrier_id = jQuery('#acf-field_'+acf_carrier_id).val();
  updateRecurringConsignmentCarrier( carrier_id );
  updateRecurringConsignmentCarrierProducts( carrier_id, true );
  updateRecurringConsignmentProductServices( true );
  updateRecurringConsignmentMessage();

  jQuery('#acf-field_'+acf_copy_from_parcel).attr('checked', false);
}



function updateRecurringConsignmentCarrier( carrier_id ){
  jQuery('#acf-field_'+acf_recurring_carrier_id+' option[value='+carrier_id+']').attr('selected', true );
}


function updateRecurringConsignmentMessage(){
  // _log('updateConsignmentMessage');
  var message = jQuery('#acf-field_56cead8e7fd30').val();
  jQuery('#acf-field_593e3090536cf').val( message );
}


function updateRecurringConsignmentProductServices( copy ){
  _log('updateRecurringConsignmentProductServices');
  product_services = getProductServices( acf_recurring_consignment_type, acf_recurring_consignment_services );

  _log('product_services');
  _log(product_services);
  if ( product_services ){
    jQuery('.acf-field.acf-field-'+acf_recurring_consignment_services+' .acf-checkbox-list').html( product_services );

    if( copy ){
      checked_services = jQuery('.acf-field-'+acf_parcel_services+' input:checked');
      if ( typeof checked_services !== 'undefined' && checked_services.length ){
        jQuery.each(
          checked_services,
          function(index, field) {
            jQuery('.acf-field.acf-field-'+acf_recurring_consignment_services+' .acf-checkbox-list input[value='+field.value+']').attr('checked', true );
          }
        );
      }
    }
    else{
      if ( typeof Parcel.RecurringConsignmentServices !== 'undefined' && Parcel.RecurringConsignmentServices && Parcel.RecurringConsignmentServices.length ){
        jQuery.each( Parcel.RecurringConsignmentServices, function(index, service ){
          // _log(service);
          jQuery('.acf-field.acf-field-'+acf_recurring_consignment_services+' .acf-checkbox-list input[value='+service+']').attr('checked', true );
        });
      }
    }
  }
}


function updateRecurringConsignmentCarrierProducts( carrier_id, copy ){
  // _log('updateRecurringConsignmentCarrierProducts');
  var options = getCarrierProducts( carrier_id );

  if ( options.length ){
    jQuery('#acf-field_'+acf_recurring_consignment_type).html(options);

    if ( copy ){
      var product_index = jQuery('#acf-field_'+acf_parcel_type+' option:checked').index();

      product_index = parseInt(product_index);
      if ( product_index != 'NaN' ){
        product_index += 1;
        jQuery('#acf-field_'+acf_recurring_consignment_type+' option:nth-child('+product_index+')').attr('selected', true );
      }
    }
    else{
      // _log('set saved value');
      // _log(Parcel.RecurringConsignmentType);
      if ( typeof Parcel.RecurringConsignmentType !== 'undefined' ){
        var options = jQuery('#acf-field_'+acf_recurring_consignment_type+' option');
        if ( typeof options == 'object' && options.length ){
          // _log(options);
          jQuery.each( options, function(index, option){
            if ( option.value == Parcel.RecurringConsignmentType ){
              var pattern = '#acf-field_'+acf_recurring_consignment_type+' option:nth-child('+(option.index+1) +')';
              // _log( pattern );
              jQuery( pattern ).attr('selected', true);
            }
          });
        }
      }
    }
  }
}


function updateTransportAgreement( carrier_id ){
  // TransportAgreement = null;
  if ( typeof transport_agreements[carrier_id] !== 'undefined' ){
    TransportAgreement = transport_agreements[carrier_id];
  }

  return TransportAgreement;
}



function updateCarrierProducts( carrier_id ){
  var options = getCarrierProducts( carrier_id );

  if ( options.length ){
    jQuery('#acf-field_'+acf_parcel_type).html(options);
  }
}


function updateProductServices(){
  // _log('updateProductServices');
  // _log (TransportAgreement );
  if ( product_services = getProductServices( acf_parcel_type, acf_parcel_services) ){
    jQuery('.acf-field.acf-field-'+acf_parcel_services+' .acf-checkbox-list').html( product_services );
  }
}


function updateProductTypes(){
  _log('updateProductTypes');
  _log(TransportProduct);
  if ( TransportProduct && typeof TransportProduct.types !== 'undefined' ){
    var options = makeOption( 'select parcel type', '' );
    for ( type_index in TransportProduct.types ){
      options += makeOption( TransportProduct.types[type_index], type_index );
    }

    // _log('update parcel type list');
    // update select element
    jQuery('.acf-field.acf-field-'+acf_consignment_items+' select').html(options);

    // set select attribute
    if ( typeof Parcel.Items === 'object' && Parcel.Items.length ){
      updateItemTypes( Parcel.Items, acf_consignment_items );
    }
  }
}


function updateRecurringConsignmentProductTypes(){
  _log('updateRecurringConsignmentProductTypes');
  _log(TransportProduct);
  if ( TransportProduct && typeof TransportProduct.types !== 'undefined' ){
    var options = makeOption( 'select parcel type', '' );
    for ( type_index in TransportProduct.types ){
      // _log( type_index );
      options += makeOption( TransportProduct.types[type_index], type_index );
    }
    jQuery('.acf-field.acf-field-'+acf_recurring_consignment_items+' select').html(options);

    if ( typeof Parcel.RecurringConsignmentItems !== 'undefined' && Parcel.RecurringConsignmentItems && Parcel.RecurringConsignmentItems.length ){
      updateItemTypes( Parcel.RecurringConsignmentItems, acf_recurring_consignment_items );
    }
  }
}


function updateItemTypes( items, field_id ){
  // _log('updateItemTypes: '+field_id);
  // _log(items);
  if ( items && items.length ){
    jQuery.each( items, function(index, Item){
      if ( typeof Item.parcel_package_type !== 'undefined' && Item.parcel_package_type.length ){
        // _log(Item.parcel_package_type);
        jq = '.acf-field.acf-field-'+field_id+' .acf-table tbody tr:nth-child('+(index+1)+')';
        // _log(jq);
        // _log(Item.parcel_package_type);
        if ( jQuery(jq).length ){
          // _log('exists');
          jQuery(jq+ ' select option[value='+Item.parcel_package_type+']').attr('selected', true);
        }
      }
    });
  }
}


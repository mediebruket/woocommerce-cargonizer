is_recurring = false;

jQuery(document).ready(function(){
  shop_updateCarrierProduct();

  initTableEdit();
  initTableRepeater();
  initIsCargonized();
});


function initIsCargonized(){
  if ( typeof data.is_cargonized !== 'undefined' && data.is_cargonized ){
    jQuery("#admin_shop_order :input").attr('disabled', true);
  }
}

function initTableEdit(){
  jQuery('.parcel-items').Tabledit(
  {
    url: ajaxurl+"?post_id="+jQuery('#post_ID').val()+"&recurring=0",
    restoreButton: false,
    onSuccess: function(data, textStatus, jqXHR) {
      jQuery(".tabledit-deleted-row").remove();
      return;
    },
    onAjax: function(action, serialize){
      var recurring = ( is_recurring ) ? 'recurring=1' : 'recurring=0';
      this.url = this.url.replace(/recurring=\d{1}/i, recurring );
    },
    columns: {
        identifier: [0, 'id'],
        editable: [
          [1, 'parcel_amount'],
          [2, 'parcel_type'],
          [3, 'parcel_description'],
          [4, 'parcel_weight'],
          [5, 'parcel_height'],
          [6, 'parcel_length'],
          [7, 'parcel_width'],
      ]
    }
  });

  initTableButtons();
}


function initTableButtons(){
  _log('initTableButtons');
  jQuery('.tabledit-edit-button, .tabledit-delete-button').click(function(){
    _log('click a');
    jQuery('.tabledit-save-button, .tabledit-confirm-button').click(function(){
      _log('click b');
      table_id = jQuery(this).parents('table.parcel-items').attr('id');
      is_recurring = ( table_id == 'parcel_packages') ? false : true;
    });
  });
}


function initTableRepeater(){
  jQuery('.js-add-package-row').click(function(e){
    e.preventDefault();

    id = jQuery(this).attr('data-target');
    cc = jQuery("#"+id+" tr:first-child th").length;
    next_id = jQuery("#"+id+" tbody tr").length + 1;

    var index = [];
    index[0] = 'id';
    index[1] = 'package-amount';
    index[2] = 'package-type';
    index[3] = 'package-desc';
    index[4] = 'package-weight';
    index[5] = 'package-height';
    index[6] = 'package-length';
    index[7] = 'package-width';

    columns = '';

    for( i=0; i<cc; i++ ){
      value = (i==0) ? next_id : '';
      columns += '<td class="'+index[i]+'">'+value+'</td>';
    }

    jQuery("#"+id+" tbody").append('<tr>'+columns+'</tr>');
    jQuery("#"+id+" .package-width ~ td").remove();
    initTableEdit();
    return false;
  });
}


function shop_updateCarrierProduct(){
  agreement = data;
   new Vue({
    el: '.vue-consignment',
    data: data,

    methods: {
      updateProducts: function(){
        agreement = getCarrierById( this.carrier_id );
        data.products = agreement.products;
        data.parcel_carrier_product = agreement.products[0].identifier;
        this.updateProductTypes();
      },

      updateProductTypes: function(){
        var identifier = this.parcel_carrier_product;
        if ( product = getProductByIdentifier( identifier ) ){
          // update product types
          var types = new Array();
          jQuery.each(product.types, function(type_index, type_name){
            types.push( { 'name' : type_name, 'value' : type_index } );
          } );
          data.product_types = types;

          // update product services
          var services = new Array();
          if ( typeof product.services === 'object' && product.services.length ){
            jQuery.each(product.services, function(service_index, service){
              services.push(
                {
                  'name' : service.name,
                  'value' : service.identifier,
                  'checked' : false,
                }
              );
            });
          }
          data.product_services = services;
        }
      },


      updateRecurringProducts: function(){
        agreement = getCarrierById( this.recurring_consignment_carrier_id );
        data.recurring_consignment_products = agreement.products;
        data.recurring_consignment_carrier_product = agreement.products[0].identifier;
        this.updateRecurringProductTypes();
      },


      updateRecurringProductTypes: function(){
        var identifier = this.recurring_consignment_carrier_product;

        if ( product = getRecurringProductByIdentifier( identifier ) ){
          // update product types
          var types = new Array();
          jQuery.each(product.types, function(type_index, type_name){
            types.push( { 'name' : type_name, 'value' : type_index } );
          } );

          data.recurring_consignment_product_types = types;

          // update product services
          var services = new Array();

          if ( typeof product.services === 'object' && product.services.length ){
            jQuery.each(product.services, function(service_index, service){
              services.push(
                {
                  'name' : service.name,
                  'value' : service.identifier,
                  'checked' : false,
                }
              );
            });
          }

          _log('services');
          _log(services);
          data.recurring_consignment_product_services = services;
        }
      },


    },
    beforeMount(){
      this.updateProductTypes();
      this.updateRecurringProductTypes();
    },
  });
}


function getProductByIdentifier( identifier ){
  product = null;
  jQuery.each(data.products , function(index, p){
    if ( p.identifier == identifier ){
      product = p;
    }
  });

  return product;
}


function getRecurringProductByIdentifier( identifier ){
  product = null;
  jQuery.each(data.recurring_consignment_products , function(index, p){
    if ( p.identifier == identifier ){
      product = p;
    }
  });

  return product;
}


function getCarrierById ( carrier_id ){
  if ( typeof transport_agreements !== 'undefined' && transport_agreements[carrier_id] !== 'undefined' ){
    return transport_agreements[carrier_id];
  }
  else{
    return null;
  }
}
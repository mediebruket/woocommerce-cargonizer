jQuery(document).ready(function(){
  shop_updateCarrierProduct();

  initTableEdit();
  initTableRepeater();
});

function initTableEdit(){
  jQuery('#parcel_repeater').Tabledit(
  {
    url: ajaxurl,


    columns: {
        identifier: [0, 'id'],
        editable: [
          [1, 'parcel_amount'],
          [2, 'parcel_type'],
          [3, 'parcel_description'],
          [4, 'parcel_description'],
          [5, 'parcel_weight'],
          [5, 'parcel_height'],
          [6, 'parcel_length'],
          [7, 'parcel_width'],
      ]
    }
  });
}


function initTableRepeater(){
  jQuery('#add-package-row').click(function(e){
    e.preventDefault();

    cc = jQuery("#parcel_repeater tr:first-child td").length;
    columns = '';
    for( i=0; i<cc; i++ ){
      columns += '<td></td>';
    }

    jQuery("#parcel_repeater").append('<tr>'+columns+'</tr>');
    initTableEdit();
    return false;
  });
}


function shop_updateCarrierProduct(){
  agreement = data;
   new Vue({
    el: '#admin_shop',
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
      }
    },
    beforeMount(){
     this.updateProductTypes()
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


function getCarrierById ( carrier_id ){

 if ( typeof transport_agreements !== 'undefined' && transport_agreements[carrier_id] !== 'undefined' ){
    return transport_agreements[carrier_id];
  }
  else{
    return null;
  }
}

function _log( object ){
  console.log( object );
}
jQuery(document).ready(function(){
  shop_updateCarrierProduct();
});


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
          var services = types = new Array();
          // update product types
          jQuery.each(product.types, function(type_index, type_name){
            types.push( { 'name' : type_name, 'value' : type_index } );
          } );
          data.product_types = types;

          // update product services
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
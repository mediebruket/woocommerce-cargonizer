jQuery(document).ready(function(){
  shop_updateCarrierProduct();
});

function shop_updateCarrierProduct(){
  agreement = data;
   new Vue({
    el: '#admin_shop',
    data: data,
    methods: {
        updateProductTypes: function(){
          console.log('xxx');
          var identifier = this.parcel_carrier_product;
          console.log(identifier);
          if ( product = getProductByIdentifier( identifier ) ){
            var types = new Array();
            jQuery.each(product.types, function(type_index, type_name){
              _log(type_index);
              _log(type_name);

              this.product_types.push = { 'name' : type_name, 'value' : type_index };
            } );



          }
        }
      }

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
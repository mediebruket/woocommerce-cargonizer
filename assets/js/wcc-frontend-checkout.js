$(document).ready(function(){
  wcc_start_app();
  wcc_init_post_codes();
  wcc_init_different_addresses();
});
 

function wcc_start_app(){
  result = null;
  wcc_last_postcode = null;

  wcc_app = new Vue(
    {
      el: '#wcc-service-partners',
      data: {
        postcode : null,
        country : null,
        service_partners : [],
      },
      created: function () {
        this.update_service_partners();
      },
      methods: {
        update_service_partners: function(){
          this.set_postcode_and_country();
          if ( this.postcode && wcc_last_postcode != this.postcode ){
            wcc_last_postcode = this.postcode;
          
            var data = {
              'action': 'get_service_partners',
              'country': this.country,
              'postcode' : this.postcode
            };

            $.post( wcc_ajax_url, data, function(response) {
              if ( typeof response !== 'undefined' && response.length ){
                var sp = JSON.parse(response);
                wcc_app.service_partners = sp;
                
                $('.service-partner-list li:first-child input').attr('checked', true);
                $('.wcc-opening-hours').hide();
                setTimeout( trigger_update_service_partner,  1000);
              }
            });  
          }          
        },
        set_postcode_and_country : function(){
          if ( $('#ship-to-different-address-checkbox:checked').length ){
            this.postcode = $('#shipping_postcode').val();
            this.country = $('#shipping_country').val();
          }
          else{
            this.postcode = $('#billing_postcode').val();
            this.country = $('#billing_country').val(); 
          }
        },
        toggle_opening_hours : function(data_id, e){
          e.preventDefault();
          $('#opening-hours-'+data_id).toggle();
        },
        checked_service_partner: function(){ 
          number = $('.wcc-service-partner:checked').val();        
          service_partner = get_service_partner(number);
          if (typeof service_partner === 'object' ){
            /*console.log('update hidden fields');
            console.log(service_partner);*/
            $('#wcc-service-partner-id').val( service_partner.number );
            $('#wcc-service-partner-name').val( service_partner.name );
            $('#wcc-service-partner-address').val( service_partner.address1 );
            $('#wcc-service-partner-postcode').val( service_partner.postcode );
            $('#wcc-service-partner-city').val( service_partner.city );
            $('#wcc-service-partner-country').val( service_partner.country );
          }
        }
      }
    });
}     


function wcc_init_post_codes(){
  $('#billing_postcode, #shipping_postcode').change(function(){
    wcc_app.update_service_partners();
  });
}


function wcc_init_different_addresses(){
  $('#ship-to-different-address-checkbox').change( function(){
    wcc_app.update_service_partners();
  });
}


function get_service_partner(number){
  var service_partner = null;
  $.each(wcc_app.service_partners, function(index, partner){
    //console.log(partner);
    if ( partner.number == number ){
      service_partner = partner;
      return false;
    }
  } );

  return service_partner;
}
 

function trigger_update_service_partner(){
  wcc_app.checked_service_partner();
}
jQuery(document).ready(
  function(){
    initAjaxCreateConsignment();
  }
);

function initAjaxCreateConsignment(){
  jQuery('.ajax-create-consignment').click(
    function(e){
      e.preventDefault();
      _log('click');
      var cid = jQuery(this).attr('data-post_id');

      _log(cid);

      var parent =  jQuery(this).parents('.type-consignment');

      parent.addClass('consignment-active');


      var data = {
        'action': 'wcc_create_consignment',
        'order_id': cid
      };

      jQuery.post(ajaxurl, data, function(response) {
        console.log('response');
        console.log(response);
        response = jQuery.trim(response);

        if ( typeof response !== 'undefined' && response != '1' ){
          _log('error');
        }
        else if ( response == '1' ){
          _log('success');
          parent.removeClass('consignment-active');
          parent.addClass('consignment-created');
        }

      });


    }
  );
}


function initPrintBtn(){
  if ( jQuery('#acf-field_56cee621c7cd1').length ){
    var consignment_id = jQuery('#acf-field_56cee621c7cd1').val();
    if ( consignment_id.length ){
      var print_button = '<div class="acf-field acf-field-text"><a href="#" id="js_print-order" title="Print order" class="button button-primary">Print</a><span id="wcc-print-response" class="wcc-instruction"></span></div>'
      // jQuery('.inside.acf-fields').append(print_button);
      jQuery(print_button).insertAfter('.inside.acf-fields .acf-field-56cee6476fb1d');

      jQuery('#js_print-order').click(function(){
        jQuery('#wcc-print-response').html();
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

      if ( typeof response !== 'undefined' && response != '1' ){
        jQuery('#wcc-print-response').html( response );
      }
    });
  }
}
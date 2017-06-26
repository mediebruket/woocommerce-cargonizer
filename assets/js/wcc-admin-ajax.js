jQuery(document).ready(
  function(){
    initAjaxCreateConsignment(); // single consignment
    initAjaxPrintLatestConsignment(); // print the latest consignment one more time
    initAjaxCreateConsignments(); // multiple consignments
  }
);


// creates multiple consignments
function initAjaxCreateConsignments(){
  jQuery("#ajax-create-consignments").click(function(e){
    e.preventDefault();
    var posts = jQuery('#the-list input[name^=post]:checked').map(
                  function(idx, elem) {
                    return jQuery(elem).val();
                  }
                ).get();

    if ( posts.length ){
      createConsignments( posts, 0 );
    }
  });
}


// creates a consignment on click
function initAjaxCreateConsignment(){
  jQuery('.ajax-create-consignment').click(
    function(e){
      e.preventDefault();
      var cid = jQuery(this).attr('data-post_id');
      createConsignments( [cid], 0 );
    }
  );


  jQuery('.ajax-main-create-consignment').click(
    function(e){
      e.preventDefault();

      var status_box = jQuery('#wcc-admin-message');
      var status_class = status_box.attr('class'); // save class
      status_box.attr('class', 'wcc-admin-message alert alert-info active'); // reset class
      var cid = jQuery(this).attr('data-post_id'); // get post id

      if ( !isNaN(cid) ){ // post is numeric
        status_box.html( 'Creating new consignments' ); // create new consignment

        var data = {
        'action': 'wcc_create_consignment',
        'order_id': cid
        };

        jQuery.post( ajaxurl, data, function(response) {
          response = jQuery.parseJSON( jQuery.trim(response) );
          status_box.removeClass('alert-info');

          if ( typeof response !== 'undefined' && response.status == 'ok' ){
            _log('success');
            status_box.html(response.message);
            status_box.addClass('alert-success');
          }
          else if( typeof response !== 'undefined' && response.status == 'error' ){
            _log('error');
            status_box.addClass('alert-danger');
            status_box.text(response.message);
          }
          else{
            _log(response);
          }
        });


      }
    }
  );
}




// creates the consignment
function createConsignments( posts, index ){
  _log('createConsignments');

  if ( typeof index === 'undefined' ){
    index = 0;
  }

  if ( typeof posts[index] !== 'undefined' && posts[index] && !isNaN(posts[index] ) ){
    var cid = posts[index];
    var parent = jQuery('#post-'+cid );
    var status = parent.find('.consignment-status .alert');
    // _log(status);
    var status_class = status.attr('class');
    // _log(status_class);
    status.attr('class', 'alert');

    status.text('Creating new consignment');

    var data = {
      'action': 'wcc_create_consignment',
      'order_id': cid
    };


    jQuery.post( ajaxurl, data, function(response) {
      // console.log('response');
      // console.log(response);
      response = jQuery.parseJSON( jQuery.trim(response) );
      if ( typeof response !== 'undefined' && response.status == 'ok' ){
        _log('success');
        status.addClass('alert-success');
        status.text(response.message);
      }
      else if( typeof response !== 'undefined' && response.status == 'error' ){
        _log('error');
        status.addClass('alert-danger');
        status.text(response.message);
      }
      else{
        _log(response);
      }

      createConsignments(posts, ++index );
    });
  }
}


function initAjaxPrintLatestConsignment(){
  jQuery('.ajax-print-consignment').click(function(e){
    e.preventDefault();
    var cid = jQuery(this).attr('data-post_id');
    // _log(cid);

    var parent =  jQuery(this).parents('.type-consignment');
    var status =  parent.find('.consignment-status .alert');
      // _log(status);
    var status_class = status.attr('class');
    // _log(status_class);
    status.attr('class', 'alert');

    status.text('Printing latest consignment');

    var data = {
        'action': 'wcc_print_latest_consignment',
        'order_id': cid
      };

    jQuery.post(ajaxurl, data, function(response) {
      // _log('finished');
      // _log(response);
       response = jQuery.parseJSON( jQuery.trim(response) );

      if ( typeof response !== 'undefined' && response.status == 'ok' ){
        _log('success');
        status.addClass('alert-success');
        status.text(response.message);
      }
      else if ( typeof response !== 'undefined' && response.status == 'error' ){
        status.addClass('alert-danger');
        status.text(response.message);
      }
      else{
        _log(response);
      }
    }); // post-request

  }); // listener


  jQuery('.ajax-main-print-consignment').click(function(e){
    e.preventDefault();
    var status_box = jQuery('#wcc-admin-message');

    status_box.attr('class', 'wcc-admin-message alert alert-info active'); // reset class

    var cid = jQuery(this).attr('data-post_id'); // get post id

    if ( !isNaN(cid) ){ // post is numeric
      status_box.html( 'Printing latest consignment' ); // create new consignment

      var data = {
      'action': 'wcc_print_latest_consignment',
      'order_id': cid
      };

      jQuery.post( ajaxurl, data, function(response) {
        response = jQuery.parseJSON( jQuery.trim(response) );
        status_box.removeClass('alert-info');

        if ( typeof response !== 'undefined' && response.status == 'ok' ){
          _log('success');
          status_box.addClass('alert-success');
          status_box.html(response.message);
        }
        else if ( typeof response !== 'undefined' && response.status == 'error' ){
          _log('error');
          status_box.addClass('alert-danger');
          status_box.text(response.message);
        }
        else{
          _log(response);
        }
      });
    }

  }); // listener

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
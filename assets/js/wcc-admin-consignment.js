jQuery(document).ready(function(){
  initConsignmentIsRecurring();
});

function initConsignmentIsRecurring(){
  jQuery('#consignment_is_recurring').click(function(){
    consignmentToggleStartDate();
  })
}


function consignmentToggleStartDate(){
  jQuery('.consignment-start-date').hide();
  if ( jQuery('#consignment_is_recurring:checked').length ){
    jQuery('.consignment-start-date').show();
  }
}
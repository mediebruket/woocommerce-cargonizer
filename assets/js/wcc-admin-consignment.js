jQuery(document).ready(function(){
  initConsignmentIsRecurring();
});

function initConsignmentIsRecurring(){
  consignmentToggleStartDate();
  jQuery('#consignment_is_recurring').click(function(){
    consignmentToggleStartDate();
  })
}


function consignmentToggleStartDate(){
  jQuery('.recurring-field').hide();
  if ( jQuery('#consignment_is_recurring:checked').length ){
    jQuery('.recurring-field').show();
  }
}
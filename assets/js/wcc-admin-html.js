function makeCheckbox( id, title, value ){
  return '<li><label><input type="checkbox" id="acf-field_'+id+'-'+value+'" name="acf[field_'+id+'][]" value="'+value+'">'+title+'</label></li>';
}


function makeOption( title, value ){
  return '<option value="'+value+'">'+title+'</option>';
}

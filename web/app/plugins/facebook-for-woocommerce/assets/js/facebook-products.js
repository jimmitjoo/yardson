/*
 *  Ajax helper function.  
 *  Takes optional payload for POST and optional callback.
 */
function ajax(action, payload = null, cb = null, failcb = null) {
  var data = {
    'action': action,
  };
  if (payload){
    for (var attrname in payload) { data[attrname] = payload[attrname]; }
  }  

  // Since  Wordpress 2.8 ajaxurl is always defined in admin header and
  // points to admin-ajax.php
  jQuery.post(ajaxurl, data, function(response) {    
    if(cb) {      
      cb(response);
    }
  }).fail(function(errorResponse){    
    if(failcb) {      
      failcb(errorResponse);
    }
  });  
}

function fb_toggle_visibility(wp_id) {  
  var checkbox = document.querySelector("#viz_" + wp_id);
  var tooltip = document.querySelector("#tip_" + wp_id);

  var published = !!checkbox.checked; //Convert intent to explict bool

  if(published){
    tooltip.setAttribute('data-tip', 'Published');
  }else{
    tooltip.setAttribute('data-tip', 'Staging');
  }

  //Reset tooltip
  jQuery(function($) { 
    $('.tips').tipTip({
      'attribute': 'data-tip',
      'fadeIn': 50,
      'fadeOut': 50,
      'delay': 200
    });
  });

  return ajax(
    'ajax_fb_toggle_visibility', 
    {'wp_id': wp_id, 'published': published}
  );
}
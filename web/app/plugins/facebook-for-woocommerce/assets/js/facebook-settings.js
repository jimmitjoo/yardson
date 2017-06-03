function openPopup() {
  var width = 1153;
  var height = 808;
  var topPos = screen.height / 2 - height / 2;
  var leftPos = screen.width / 2 - width / 2;
  window.originParam = window.location.protocol + '//' + window.location.host;
  var popupUrl = window.facebookAdsToolboxConfig.popupOrigin;
  var path = '/ads/dia';
  var page = window.open(popupUrl + '/login.php?display=popup&next=' + encodeURIComponent(popupUrl + path + '?origin=' + window.originParam + ' &merchant_settings_id=' + window.facebookAdsToolboxConfig.diaSettingId), 'DiaWizard', ['toolbar=no', 'location=no', 'directories=no', 'status=no', 'menubar=no', 'scrollbars=no', 'resizable=no', 'copyhistory=no', 'width=' + width, 'height=' + height, 'top=' + topPos, 'left=' + leftPos].join(','));

  return function (type, params) {
    page.postMessage({
      type: type,
      params: params
    }, window.facebookAdsToolboxConfig.popupOrigin);
  };
}

function get_product_catalog_id_box() {
  return document.querySelector('#woocommerce_facebookcommerce_fb_product_catalog_id') || null;
}
function get_pixel_id_box() {
  return document.querySelector('#woocommerce_facebookcommerce_fb_pixel_id') || null;
}
function get_pixel_use_pii_id_box() {
  return document.querySelector('#woocommerce_facebookcommerce_fb_pixel_use_pii') || null;
}
function get_api_key_box() {
  return document.querySelector('#woocommerce_facebookcommerce_fb_api_key') || null;
}
function get_page_id_box() {
  return document.querySelector('#woocommerce_facebookcommerce_fb_page_id') || null;
}
function get_ems_id_box() {
  return document.querySelector('#woocommerce_facebookcommerce_fb_external_merchant_settings_id') || null;
}

/*
 *  Ajax helper function.
 *  Takes optional payload for POST and optional callback.
 */
function ajax(action, payload = null, callback = null, failcallback = null) {
  var data = {
    'action': action,
  };
  if (payload){
    for (var attrname in payload) { data[attrname] = payload[attrname]; }
  }

  // Since  Wordpress 2.8 ajaxurl is always defined in admin header and
  // points to admin-ajax.php
  jQuery.post(ajaxurl, data, function(response) {
    if(callback) {
      callback(response);
    }
  }).fail(function(errorResponse){
    if(failcallback) {
      failcallback(errorResponse);
    }
  });
}

var settings = {'facebook_for_woocommerce' : 1};

function facebookConfig() {
  window.sendToFacebook = openPopup();
  window.diaConfig = { 'clientSetup': window.facebookAdsToolboxConfig };
}

function fb_flush(){
  console.log("Removing all FBIDs from all products!");
  return ajax('ajax_reset_all_products');
}

function sync_confirm() {
  if(confirm('Facebook for WooCommerce automatically syncs your products on ' +
    'create/update. Are you sure you want to force product resync? ' +
    'This will query all published products and may take some time. ' + 
    'You only need to do this if your products are out of sync.')) {
    sync_all_products();
  }
}

function sync_all_products() {
  if (get_product_catalog_id_box() && !get_product_catalog_id_box().value){
    return;
  }
  if (get_api_key_box() && !get_api_key_box().value){
    return;
  }
  console.log('Syncing all products!');
  sync_in_progress();
  return ajax('ajax_sync_all_products');
}

// Reset all state
function delete_all_settings(callback = null, failcallback = null) {
  if (get_product_catalog_id_box()) {
    get_product_catalog_id_box().value = '';
  }
  if(get_pixel_id_box()) {
    get_pixel_id_box().value = '';
  }
  if(get_pixel_use_pii_id_box()) {
    get_pixel_use_pii_id_box().checked = false;
  }
  if(get_api_key_box()) {
    get_api_key_box().value = '';
  }
  if(get_page_id_box()) {
    get_page_id_box().value = '';
  }
  if(get_ems_id_box()) {
    get_ems_id_box().value = '';
  }

  window.facebookAdsToolboxConfig.pixel.pixelId = '';
  window.facebookAdsToolboxConfig.diaSettingId = '';

  reset_buttons();

  console.log('Deleting all settings and removing all FBIDs!');
  return ajax('ajax_delete_settings', null, callback, failcallback);
}

// save_settings and save_settings_and_sync should only be called once
// after all variables are set up in the settings global variable
// if called multiple times, race conditions might occur
function save_settings(message, callback = null, failcallback = null){
  ajax('ajax_save_settings', settings,
    function(response){
      if(callback) {
        callback(response);
      }
    },
    function(errorResponse){
      if(failcallback) {
        failcallback(errorResponse);
      }
    }
  );
}

// see comments in save_settings function above
function save_settings_and_sync(message) {
  if ('api_key' in settings && 'product_catalog_id' in settings){
    save_settings(message,
      function(response){
        if (response && response.includes('settings_saved')){
          console.log(response);
          //Final acks
          window.sendToFacebook('ack set page access token', message.params);
          window.sendToFacebook('ack set merchant settings', message.params);
          sync_all_products();
        }else{
          window.sendToFacebook('fail save_settings', response);
          console.log('Fail response on save_settings_and_sync');
        }
      },
      function(errorResponse){
        console.log('Ajax error while saving settings:' + JSON.stringify(errorResponse));
        window.sendToFacebook('fail save_settings_ajax', JSON.stringify(errorResponse));
      }
    );
  }
}

//Reset buttons to brand new setup state
function reset_buttons(){
  document.querySelector('#set_dia').text = 'Get Started';

  if(document.querySelector('#connection_status')){
    document.querySelector('#connection_status').style.display = 'none';
  }
  if(document.querySelector('#resync_products')) {
    document.querySelector('#resync_products').style.display = 'none';
  }
  if(document.querySelector('#sync_status')){
    document.querySelector('#sync_status').innerHTML = '';
  }
}

//Remove reset/settings buttons during product sync
function sync_in_progress(){

  //Get rid of all the buttons
  if(document.querySelector('#set_dia')){
    document.querySelector('#set_dia').style.display = 'none';
  }
  if(document.querySelector('#resync_products')) {
    document.querySelector('#resync_products').style.display = 'none';
  }
  //Set a product sync status
  if(document.querySelector('#sync_status')){
    document.querySelector('#sync_status').innerHTML =
      '<strong>Facebook product sync in progress, <br/> ' +
      'please refresh the page or check back later...</strong>';
  }

}

function addAnEventListener(obj,evt,func) {
  if ('addEventListener' in obj){
    obj.addEventListener(evt,func, false);
  } else if ('attachEvent' in obj){//IE
    obj.attachEvent('on'+evt,func);
  }
}

function setMerchantSettings(message) {
  if (!message.params.setting_id) {
    console.error('Facebook Extension Error: got no setting_id', message.params);
    window.sendToFacebook('fail set merchant settings', message.params);
    return;
  }
  if(get_ems_id_box()){
    get_ems_id_box().value = message.params.setting_id;
  }

  settings.external_merchant_settings_id = message.params.setting_id;

  //Immediately set in case button is clicked again
  window.facebookAdsToolboxConfig.diaSettingId = message.params.setting_id;
  //Ack merchant settings happens after settings are saved
}

function setCatalog(message) {
  if (!message.params.catalog_id) {
    console.error('Facebook Extension Error: got no catalog_id', message.params);
    window.sendToFacebook('fail set catalog', message.params);
    return;
  }
  if(get_api_key_box()){
    get_product_catalog_id_box().value = message.params.catalog_id;
  }

  settings.product_catalog_id = message.params.catalog_id;

  window.sendToFacebook('ack set catalog', message.params);
}


function setPixel(message) {
  if (!message.params.pixel_id) {
    console.error('Facebook Ads Extension Error: got no pixel_id', message.params);
    window.sendToFacebook('fail set pixel', message.params);
    return;
  }
  if(get_pixel_id_box()){
    get_pixel_id_box().value = message.params.pixel_id;
  }

  settings.pixel_id = message.params.pixel_id;
  if (message.params.pixel_use_pii !== undefined) {
    if(get_pixel_use_pii_id_box()){
      //!! will explicitly convert truthy/falsy values to a boolean
      get_pixel_use_pii_id_box().checked = !!message.params.pixel_use_pii;
    }
    settings.pixel_use_pii = message.params.pixel_use_pii;
  }
  window.sendToFacebook('ack set pixel', message.params);
}

function genFeed(message) {
  //no-op
}

function setAccessTokenAndPageId(message) {
  if (!message.params.page_token) {
    console.error('Facebook Ads Extension Error: got no page_token',
      message.params);
    window.sendToFacebook('fail set page access token', message.params);
    return;
  }
  /*
    Set page_token here
  */

  if(get_api_key_box()){
    get_api_key_box().value = message.params.page_token;
  }

  if(get_page_id_box()){
    get_page_id_box().value = message.params.page_id;
  }

  settings.api_key = message.params.page_token;
  settings.page_id = message.params.page_id;
  //Ack token in "save_settings_and_sync" for final ack
}

function iFrameListener(event) {
  // Fix for web.facebook.com
  const origin = event.origin || event.originalEvent.origin;
  if (origin != window.facebookAdsToolboxConfig.popupOrigin && 
    urlFromSameDomain(origin, window.facebookAdsToolboxConfig.popupOrigin)) {
    window.facebookAdsToolboxConfig.popupOrigin = origin;
  }

  switch (event.data.type) {
    case 'reset':
      delete_all_settings(function(res){
        if(res && event.data.params) {
          if(res === 'Settings Deleted'){
            window.sendToFacebook('ack reset', event.data.params);
          }else{
            console.log(res);
            alert(res);
          }
        }else {
          console.log("Got no response from delete_all_settings");
        }
      },function(err){
          console.error(err);
      });
      break;
    case 'get dia settings':
      window.sendToFacebook('dia settings', window.diaConfig);
      break;
    case 'set merchant settings':
      setMerchantSettings(event.data);
      break;
    case 'set catalog':
      setCatalog(event.data);
      break;
    case 'set pixel':
      setPixel(event.data);
      break;
    case 'gen feed':
      genFeed();
      break;
    case 'set page access token':
      //Should be last message received
      setAccessTokenAndPageId(event.data);
      save_settings_and_sync(event.data);
      break;
  }
}

addAnEventListener(window,'message',iFrameListener);

function urlFromSameDomain(url1, url2) {
  if (!url1.startsWith('http') || !url2.startsWith('http')) {
    return false;
  }
  var u1 = parseURL(url1);
  var u2 = parseURL(url2);
  var u1host = u1.host.replace(/^\w+\./, 'www.');
  var u2host = u2.host.replace(/^\w+\./, 'www.');
  return u1.protocol === u2.protocol && u1host === u2host;
}

function parseURL(url) {
  var parser = document.createElement('a');
  parser.href = url;
  return parser;
}

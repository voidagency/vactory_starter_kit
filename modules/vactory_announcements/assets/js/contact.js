jQuery('#showPhone').click(function() {
  jQuery('#phone').toggle();
  var visible = jQuery('#phone').is(":visible");
  if (visible) {
    var encryptedPhone = jQuery('#phone').text();
    jQuery('#phone').text(atob(encryptedPhone));
    jQuery('#showPhone').hide();
    jQuery('#phone').show();
  }
  else {
    jQuery('#showPhone').show();
    jQuery('#phone').hide();
  }
});
jQuery('#showMail').click(function() {
  jQuery('#mail').toggle();
  var visible = jQuery('#mail').is(":visible");
  if (visible) {
    var encryptedMail = jQuery('#mail').text();
    jQuery('#mail').text(atob(encryptedMail));
    jQuery('#showMail').hide();
    jQuery('#mail').show();
  }
  else {
    jQuery('#showMail').show();
    jQuery('#mail').hide();
  }
});

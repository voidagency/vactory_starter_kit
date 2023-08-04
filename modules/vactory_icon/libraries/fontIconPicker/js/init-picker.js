(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.vactoryIcons = {
    attach: function attach(context) {
      var from_xml_svgs = false;
      if (drupalSettings.vactory_icon) {
        from_xml_svgs = drupalSettings.vactory_icon.from_xml_svgs;
      }
      if (!from_xml_svgs) {
        $(context).find('select.vactory--icon-picker').once('vactoryIconPicker').each(function (index, value) {
          $(value).fontIconPicker();
        });
      }
      if (from_xml_svgs) {
        $(context).find('select.vactory--icon-picker').once('vactoryIconPicker').each(function (index, value) {
          $(value).fontIconPicker({
            source: drupalSettings.vactory_icon.svg_ids,
            theme: 'fip-bootstrap',
            iconGenerator: function( item, flipBoxTitle, index ) {
            return '<i style="display: flex; align-items: center; justify-content: center; height: 100%;"><svg style="height: 32px; width: auto;" class="svg-icon ' + item + '">' +
              '<path d="' + drupalSettings.vactory_icon.svg_paths_d[item] + '"/>' +
              '</svg></i>';
            }
          });
        });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);

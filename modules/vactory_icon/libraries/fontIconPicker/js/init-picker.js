(function ($, Drupal, drupalSettings, once) {
  Drupal.behaviors.vactoryIcons = {
    attach: function attach(context) {
      var from_xml_svgs = false;
      if (drupalSettings.vactory_icon) {
        from_xml_svgs = drupalSettings.vactory_icon.from_xml_svgs;
      }
      if (!from_xml_svgs) {
        $(
          once("vactoryIconPicker", "select.vactory--icon-picker", context)
        ).each(function (index, value) {
          $(value).fontIconPicker();
        });
      }
      if (from_xml_svgs) {
        $(
          once("vactoryIconPicker", "select.vactory--icon-picker", context)
        ).each(function (index, value) {
          $(value).fontIconPicker({
            source: drupalSettings.vactory_icon.svg_ids,
            theme: "fip-bootstrap",
            iconGenerator: function (item, flipBoxTitle, index) {
              if (
                !Array.isArray(drupalSettings.vactory_icon.svg_paths_d[item])
              ) {
                return (
                  '<i style="display: flex; align-items: center; justify-content: center; height: 100%;"><svg style="height: 32px; width: auto;" class="svg-icon ' +
                  item +
                  '">' +
                  '<path d="' +
                  drupalSettings.vactory_icon.svg_paths_d[item] +
                  '"/>' +
                  "</svg></i>"
                );
              }

              var svg =
                '<i style="display: flex; align-items: center; justify-content: center; height: 100%;"><svg style="height: 32px; width: auto;" class="svg-icon ' +
                item +
                '">';

              for (
                var i = 0;
                i < drupalSettings.vactory_icon.svg_paths_d[item].length;
                i++
              ) {
                svg +=
                  '<path d="' +
                  drupalSettings.vactory_icon.svg_paths_d[item][i] +
                  '"/>';
              }

              return svg + "</svg></i>";
            },
          });
        });
      }
    },
  };
})(jQuery, Drupal, drupalSettings, once);

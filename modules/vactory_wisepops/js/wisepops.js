(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.myModuleBehavior = {
    attach: function (context, settings) {
      (function(W,i,s,e,P,o,p)
      {W['WisePopsObject']=P;W[P]=W[P]||function()
      {(W[P].q=W[P].q||[]).push(arguments)},
        W[P].l=1*new Date();
      o=i.createElement(s),
        p=i.getElementsByTagName(s)[0];
      o.defer=1;o.src=e;p.parentNode.insertBefore(o,p)})(
        window,
        document,'script','//loader.wisepops.com/get-loader.js?v=1&site=' + drupalSettings['key'],'wisepops'
      );
      if (drupalSettings['properties'] !== null) {
        wisepops('properties', drupalSettings['properties']);
      }
    }
  };
})(jQuery, Drupal, drupalSettings);

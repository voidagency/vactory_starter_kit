(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.vactoryParagraphClipboard = {
    attach: function (context, settings) {

      // Function to create a toast message
      function showToast(message) {
        // Remove any existing toasts
        $('.paragraph-clipboard-toast').remove();

        // Create toast element
        const toast = $('<div>', {
          'class': 'paragraph-clipboard-toast',
          'style': 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background-color: #4CAF50; color: white; padding: 15px; border-radius: 5px; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; max-width: 300px;'
        }).text(message);

        // Append to body
        $('body').append(toast);

        // Remove toast after 3 seconds
        setTimeout(function() {
          toast.fadeOut(300, function() {
            $(this).remove();
          });
        }, 2000);
      }

      const paragraphToCopy = localStorage.getItem('paragraphToCopy');
      if (paragraphToCopy) {
        $('.paragraph-clipboard-paste').show();
      }

      // Bouton Copier
      $('.paragraph-clipboard-copy', context).on('click', function(e) {
        e.preventDefault();
        const paragraphId = $(this).attr('data-paragraph-id');
        const paragraphTitle = $(this).attr('data-paragraph-title');
        localStorage.setItem('paragraphToCopy', paragraphId);
        $('.paragraph-clipboard-paste').show();
        // Show toast message
        showToast('Paragraph ('+ paragraphTitle +') copied successfully');
      });

      // Bouton Coller
      $('.paragraph-clipboard-paste', context).on('click', function(e) {
        e.preventDefault();
        const nodeId = $(this).attr('data-node-id');
        const paragraphToCopy = localStorage.getItem('paragraphToCopy');

        // Appel Ajax pour coller le paragraphe
        $.ajax({
          url: '/ajax/paragraph-clipboard/paste',
          method: 'POST',
          data: JSON.stringify({
            paragraph_id: paragraphToCopy,
            node_id: nodeId
          }),
          contentType: 'application/json',
          success: function(response) {
            localStorage.removeItem('paragraphToCopy')
            $(this).hide()
            window.location.reload();
          },
          error: function(xhr) {
            console.error('Error pasting paragraph:', xhr.responseText);
          }
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings); 
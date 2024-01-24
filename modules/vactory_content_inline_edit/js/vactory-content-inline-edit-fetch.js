(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.vactoryContentInlineEditFetch = {
        saveData: function (
            nodeId,
            paragraphId,
            updatedData,
            onSuccess,
            onError
        ) {
            $.ajax({
                url: "/vactory-content-inline-edit/save",
                method: "POST",
                contentType: "application/json", // Set the content type to JSON
                dataType: "json",
                data: JSON.stringify({
                    nodeId: nodeId,
                    paragraphId: paragraphId,
                    updatedData: updatedData,
                }),
                success: onSuccess,
                error: onError,
            });
        },
    };
})(jQuery, Drupal, drupalSettings);

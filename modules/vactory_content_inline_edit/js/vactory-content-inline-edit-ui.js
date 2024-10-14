(function ($, Drupal) {
    Drupal.vactoryContentInlineEditUI = {
        // Event delegation for dynamically added fields
        // bindFieldEvents: function () {
        //     $(document).on(
        //         "input",
        //         ".paragraph-field, .paragraph-url-extended-field",
        //         function () {
        //             Drupal.vactoryContentInlineEditUI.showEditControls(
        //                 $(this).closest(".paragraph-wrapper")
        //             );
        //         }
        //     );
        // },
        // bindCkeditorEvents: function () {
        //     $("textarea[data-ckeditor5-id]").each(function () {
        //         let element = this;
        //         Drupal.editors.ckeditor5.onChange(element, function () {
        //             Drupal.vactoryContentInlineEditUI.showEditControls(
        //                 $(element).closest(".paragraph-wrapper")
        //             );
        //         });
        //     });
        // },

        showEditControls: function (container) {
            if (container.find(".edit-controls").length === 0) {
                const cancelButton = $("<span>")
                    .addClass("icon-cancel")
                    .text("x");
                const saveButton = $("<span>").addClass("icon-save").text("✓");
                const controlsDiv = $("<div>")
                    .addClass("edit-controls")
                    .css("display", "block");

                controlsDiv.append(cancelButton);
                controlsDiv.append(saveButton);
                container.append(controlsDiv);

                // Correctly reference the methods
                saveButton.on("click", () => this.saveChanges(container));
                cancelButton.on("click", () => this.cancelChanges(container));
            } else {
                // If edit controls already exist, make sure they are visible
                container.find(".edit-controls").css("display", "block");
            }
        },

        saveChanges: function (container) {
            let _this = this; // Preserve the reference to Drupal.vactoryContentInlineEditUI

            let updatedData = {
                extra_fields: {},
                components: [],
            };

            container.find(".paragraph-field").each(function () {
                const fieldInput = $(this);
                // Find the textarea within fieldInput
                const textarea = fieldInput.find("textarea");

                const fieldName = fieldInput.data("field-name");
                const fieldFormat = fieldInput.data("field-format");
                let fieldValue = fieldInput.val();
                const isExtraField = fieldInput.data("is-extra-field");
                const group = fieldInput.data("group");
                const isMedia = fieldInput.data("is-media");

                if (isMedia) {
                    const old_value = fieldValue;
                    fieldValue = {};
                    fieldValue[Date.now()] = {
                        'selection': [
                            {
                                'target_id': old_value
                            }
                        ]
                    };
                }

                if (isExtraField) {
                    if (group) {
                        if (updatedData.extra_fields[group] === undefined) {
                            updatedData.extra_fields[group] = {};
                        }
                        if (fieldFormat) {
                            const editor = Drupal.CKEditor5Instances.get(
                                textarea.attr("data-ckeditor5-id")
                            );
                            if (editor) {
                                updatedData.extra_fields[group][fieldName] = {
                                    value: editor.getData(),
                                    format: fieldFormat,
                                };
                            }
                        } else {
                            updatedData.extra_fields[group][fieldName] = fieldValue;
                        }
                    }
                    else {
                        if (fieldFormat) {
                            const editor = Drupal.CKEditor5Instances.get(
                                textarea.attr("data-ckeditor5-id")
                            );
                            if (editor) {
                                updatedData.extra_fields[fieldName] = {
                                    value: editor.getData(),
                                    format: fieldFormat,
                                };
                            }
                        } else {
                            updatedData.extra_fields[fieldName] = fieldValue;
                        }
                    }
                } else {
                    // Assuming component index is available as a data attribute
                    const componentIndex = fieldInput.data("component-index");
                    if (updatedData.components[componentIndex] === undefined) {
                        updatedData.components[componentIndex] = {};
                    }
                    if (group) {
                        if (updatedData.components[componentIndex][group] === undefined) {
                            updatedData.components[componentIndex][group] = {};
                        }
                        if (fieldFormat) {
                            const editor = Drupal.CKEditor5Instances.get(
                                textarea.attr("data-ckeditor5-id")
                            );
                            if (editor) {
                                updatedData.components[componentIndex][group][fieldName] =
                                    {
                                        value: editor.getData(),
                                        format: fieldFormat,
                                    };
                            }
                        } else {
                            updatedData.components[componentIndex][group][fieldName] =
                                fieldValue;
                        }
                    }
                    else {
                        if (fieldFormat) {
                            const editor = Drupal.CKEditor5Instances.get(
                                textarea.attr("data-ckeditor5-id")
                            );
                            if (editor) {
                                updatedData.components[componentIndex][fieldName] =
                                    {
                                        value: editor.getData(),
                                        format: fieldFormat,
                                    };
                            }
                        } else {
                            updatedData.components[componentIndex][fieldName] =
                                fieldValue;
                        }
                    }
                }
            });

            container.find(".paragraph-url-extended-field").each(function () {
                const $fieldset = $(this);
                const fieldName = $fieldset.data("field-name");
                const isExtraField = $fieldset.data("is-extra-field");
                const group = $fieldset.data("group");

                let urlData = {};
                $fieldset.find('input[type="text"]').each(function () {
                    const $input = $(this);
                    const inputName = $input.attr("name");

                    // Determine which original value to use based on the field name
                    if (inputName.includes("title")) {
                        urlData.title = $input.val();
                    } else if (inputName.includes("url")) {
                        urlData.url = $input.val();
                    }
                    if (isExtraField) {
                        if (group) {
                            if (updatedData.extra_fields[group] === undefined ) {
                                updatedData.extra_fields[group] = {};
                            }
                            updatedData.extra_fields[group][fieldName] = urlData;
                        }
                        else {
                            updatedData.extra_fields[fieldName] = urlData;
                        }
                    } else {
                        const componentIndex = $fieldset.data("component-index");
                        if (updatedData.components[componentIndex] === undefined ) {
                            updatedData.components[componentIndex] = {};
                        }
                        if (group) {
                            if (updatedData.components[componentIndex][group] === undefined) {
                                updatedData.components[componentIndex][group] = {};
                            }
                            updatedData.components[componentIndex][group][fieldName] = urlData;
                        }
                        else {
                            updatedData.components[componentIndex][fieldName] = urlData;
                        }
                    }
                });
            });

            const nodeId = container
                .find(".paragraph-field")
                .first()
                .data("node-id");

            const paragraphId = container
                .find(".paragraph-field")
                .first()
                .data("paragraph-id");

            _this.showLoader(container); // Show loader before saving

            // _this.triggerFormValidation();

            // Call the saveData function from vactory-content-inline-fetch.js
            Drupal.behaviors.vactoryContentInlineEditFetch.saveData(
                nodeId,
                paragraphId,
                updatedData,
                function (response) {
                    // Success callback
                    _this.hideLoader(container);
                    // container.find(".edit-controls").remove();
                },
                function (error) {
                    // Error callback
                    console.error("Error updating data:", error);
                    _this.hideLoader(container);
                }
            );
        },

        cancelChanges: function (container) {
            container.find(".paragraph-field").each(function () {
                const input = $(this);
                const textarea = input.find("textarea");
                const originalValue = input.data("original-value");
                const originalFormat = input.data("field-format");
                if (originalValue !== undefined) {
                    if (originalFormat) {
                        const editor = Drupal.CKEditor5Instances.get(
                            textarea.attr("data-ckeditor5-id")
                        );
                        if (editor) {
                            editor.setData(originalValue);
                        }
                    } else {
                        input.val(originalValue);
                    }
                }
            });
            container.find(".paragraph-url-extended-field").each(function () {
                const $fieldset = $(this);
                $fieldset.find('input[type="text"]').each(function () {
                    const $input = $(this);
                    const fieldName = $input.attr("name");

                    // Determine which original value to use based on the field name
                    if (fieldName.includes("title")) {
                        $input.val($fieldset.data("original-title"));
                    } else if (fieldName.includes("url")) {
                        $input.val($fieldset.data("original-url"));
                    }
                });
            });
            // container.find(".edit-controls").remove();
        },

        showLoader: function (container) {
            // Add loader to the widget container
            const loader = $("<div>").addClass("loader");
            container.append(loader);
            // container.find(".edit-controls").hide(); // Hide edit controls
            container.find(".loader").show(); // Show loader
        },

        hideLoader: function (container) {
            container.find(".loader").remove(); // Hide loader
        },
        // triggerFormValidation: function () {
        //     jQuery('input[type="submit"].ajax-submit-button').click();
        // },
    };

    // Initialize the event binding
    $(document).ready(function () {

        $('.paragraph-wrapper').each(function() {
            var no_control = $(this).data('no-control');
            if (!no_control) {
                Drupal.vactoryContentInlineEditUI.showEditControls($(this));
            }
        });
        // Drupal.vactoryContentInlineEditUI.bindFieldEvents();
        // Drupal.vactoryContentInlineEditUI.bindCkeditorEvents();
    });
})(jQuery, Drupal);

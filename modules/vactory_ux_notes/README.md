Vactory UX Notes Module Installation and Setup Guide

Follow these steps to enable and configure the Vactory UX Notes module:
Step 1: Enable the Module

    Open a terminal or command prompt.
    Use the drush en command to enable the Vactory UX Notes module. Type the following command and press Enter:

    drush en vactory_ux_notes

Step 2: Display the Webform

    Once the module is enabled, you should be able to see the webform with the ID "ux_note_form." This webform will allow users to submit their feedback notes.

Step 3: Add Custom Block

    Go to "Block Layout" in the Drupal administration panel.
    Click on "Add custom block" to create a new custom block.
    Fill in the title and machine name for the block.

Step 4: Choose a Template

    Select the template "Ux Note" in category "Decoupled".

Step 5: Configure Block Display

    Specify where you want to display the block by filling in the block configuration. You can control this by selecting the page route or content type where the block should appear.

Step 6: Fill in Text Groups

    Fill in the groups of texts for the block content.
    Save the block settings.

Step 7: Verify Block Display

    Go to the front page or the specific page where you inserted the custom block.
    You should now see the "Ux Note" block with the configured content.

Step 8: Check User Feedback

    In the Backoffice, navigate to '/admin/user-experience/dashboard'.
    Here, you can check the results, notes, and average ratings of the users' feedback.

Congratulations! You have successfully installed and configured the Vactory UX Notes module. Users can now provide their feedback through the webform, and you can review their ratings and notes in the Backoffice dashboard.
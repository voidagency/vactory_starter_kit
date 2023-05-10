Documentation

This module allows users to add votes to entities of type Node using the fivestar format.
Installation

    1.Install the module in your Drupal instance.
    2.Go to the permissions section and enable the following permissions for Authenticated users:
        - "User can vote"
        - "User can undo vote" (if needed)

Adding Rating for Specific Content Type

If you want to add a rating feature for a specific content type, follow these steps:

    1.Locate the file profiles/contrib/vactory_starter_kit/modules/vactory_rate/src/Field/VoteEntityFieldItemList.php.
    2.Open the file and go to line 46.
    3.Add the machine name of the desired content type at line 46.
       - This is a temporary solution and will be improved in future releases.

JSONAPI Integration

After completing the installation and configuration steps, you will be able to see the "vote" field in the JSONAPI responses. This field represents the user's vote for a specific entity.

Note: Make sure you have appropriate permissions set for the user roles to access and interact with the voting feature.
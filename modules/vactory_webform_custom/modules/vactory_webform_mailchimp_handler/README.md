# Vactory Webform Mailchimp Handler

Provides a new Webform Handler plugin to send submission data to Mailchimp
via their API.


### Installation

`drush en vactory_webform_mailchimp_handler -y`

### Configuration

1. Add a new handler of type "Vactory MailChimp Handler" to the webform.
2. Add your Mailchimp API-key and server prefix.
3. Click the button to update the Lists. Select your list and map your
Mailchimp-fields and interests (if exists) to your webform fields.

Note: For mailchimp interest group each option (checkbox) should be
an independent webform element.

### Maintainers
Brahim KHOUY <b.khouy@void.fr>
# Vactory Form Autosave
Allow enabling drafts on forms by roles and by specifying the concerned forms.

### Installation
`drush en vactory_form_autosave -y`

### Configuration
Module setting form page on: `/admin/config/system/vactory-form-autosave`
* Policy: Define the policy (does the listed form ids are concerned or 
excluded)
* Form ids: Enter the concerned or excluded form ids (one form ID per line)
* Access by roles: selected concerned roles
* lifetime: specify the draft lifetime in days

### Cron
The module provides a cron job to purge expired drafts.

### Maintainers
Brahim KHOUY <b.khouy@void.fr>

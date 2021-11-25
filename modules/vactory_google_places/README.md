# Vactory Google Places
Provides a new form element and a new field type `Google Places`, also
provides a new views filter plugin which make it possible to have google places
in views exposed form.

## Installation

`drush en vactory_google_places -y`

## Settings

The module settings form is reachable on:
`/admin/config/system/vactory-google-places`

On the module settings form you could add the google places API key and you
could also select the concerned countries.

## Form element example
    $form['test'] = [
      '#type' => 'vactory_google_places',
      '#title' => t('Place'),
      '#placeholser' => t('Enter a place here'),
    ];

## Module dependencies
* views_autocomplete_filters

## Watch demo video
https://www.loom.com/share/481d8d2e3b2f45be85686640284ce305

## Maintainers
* Brahim KHOUY <b.khouy@void.fr>

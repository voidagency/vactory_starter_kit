

# Vactory Extended SEO
This module is created as an initiative to alter SEO metadata provided by Drupal.  
As for now it only supports hreflang manual setting ✌️.
## Table of Contents

* [Requirements](#requirements)
* [Installation](#installation)
* [Configuration](#configuration)
* [Extend](#extend)
* [Api](#api)
* [Troubleshooting & FAQ](#troubleshooting-faq)
* [Maintainers](#maintainers)

## Requirements



## Installation

drush en vactory_extended_seo

## Configuration
Module config is super straightforward 💯 :
To get started you only need to activate the module and it will create some custom fields in the node form page where you can   manually set the link to each language to be generated as [hreflang metadata](https://ahrefs.com/blog/hreflang-tags/).
Furthermore if you want to import a csv with all the hreflang data you can do it by checking this path : **/admin/config/import-hreflang** .
ℹ️ Instructions + csv example are available on the import page (with the possibility of **partial** or **total** purge) .


## Extends

#### TODO ADD MORE attributes to the entity.

## API
For Decoupled projects a custom field is created , exposed and consumed by next_app (you can modify it as it suits you ✅ ) :

    $fields['extended_seo']
For monolithic projects hook_page_attachments_alter is already implemented for you.

## Theming

#### Aucun

## Troubleshooting & FAQ

#### Aucun

## Maintainers

⚒️ [Team Vactory](http://void.fr/fr)
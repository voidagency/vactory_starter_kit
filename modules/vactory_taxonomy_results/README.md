# Vactory Taxonomy Results

Enhances Vactory project with a Term result count custom entity.


## Table of Contents
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Maintainers](#maintainers)

## Installation
Enable the module with the following drush command:

    drush en vactory_taxonomy_results -y

## Configuration

The module configurtion form is accessible via:

    /admin/config/system/taxonomy-results-count

You need to enable for each entity type the concerned bundle that results count should
be calculated for related taxonomy term entity reference fields (whenever exists).

Then you need to check "Calculate taxonomy term results after saving configuration"
checkbox to start counting each term results process after saving configuration

Then try to access results count entity list through '/admin/content/term-result-count'

also check the relationship using a dump on concerned terms and then check results_count field.

## Plugin TermResultCountManager
The module by default provide a default term result count plugin,
you could add you're own calculation login by creating a new plugin,
the plugin class should be defined in 
`my_module/src/Plugin/TermResultCounter` and implements the method `termResultCount`

See example on:
`vactory_taxonomy_results/src/Plugin/TermResultCounter/DefaultTermResultCounter.php`

## Maintainers
Brahim KHOUY
<b.khouy@void.fr>

# Vactory Points
Provides a system of user points incrementation/decrementation depending
on defined rules under module settings form.

## Install
`drush en vactory_points`

## Module settings page
`http://exemple.com/admin/config/vactory_points`

## Trigger user points update programmatically

`\Drupal::service('vactory_points.manager')->triggerUserPointsUpdate($action_id [, $concerned_entity, $concerned_user]);`

## Watch demo vidéo
https://www.loom.com/share/8998d18cebe64b458e461965e74d5e18

## Dependencies
 - Vactory Espace Prive (vactory_espace_prive)

## Maintainers
 * Brahim KHOUY <b.khouy@void.fr>

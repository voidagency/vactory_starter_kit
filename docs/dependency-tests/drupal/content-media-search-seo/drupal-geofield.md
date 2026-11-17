# drupal/geofield

| | |
|---|---|
| Contrainte composer.json | `^1.20` |
| Version lockée | `1.57.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — widget custom qui étend une classe du module + service WKT dans un `presave` du store locator. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_content_localizer | `modules/vactory_content_localizer/src/Plugin/Field/FieldWidget/FieldMapsWidget.php:20` | `extends GeofieldLatLonWidget` (héritage d'une classe interne) |
| vactory_locator_decoupled | `modules/vactory_locator/modules/vactory_locator_decoupled/vactory_locator_decoupled.module:21` | `geofield.wkt_generator->wktBuildPoint()` dans `hook_entity_presave` |
| vactory_locator | `modules/vactory_locator/config/install/field.storage.locator_entity.field_vactory_locator_geofield.yml` | Champs `geofield` (+ `field_geofield_city`), vues |
| info.yml | `modules/vactory_locator/vactory_locator.info.yml:38` | Dépendance ; aussi vactory_locator_decoupled:8, vactory_content_localizer:5 |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `modules/vactory_locator/modules/vactory_locator_decoupled/tests/src/Kernel/LocatorGeofieldPresaveTest.php`** — sauver un `locator_entity` avec `field_locator_info` lat/lon → champ geofield = `POINT (lon lat)`, `lat`/`lon` renseignés.
- **[Kernel] `modules/vactory_content_localizer/tests/src/Kernel/FieldMapsWidgetTest.php`** — instancier le widget via `plugin.manager.field.widget` → pas d'erreur de signature (`formElement`).

## Notes
Contrainte `^1.20` vs lock 1.57.0 : écart énorme, la contrainte ne protège rien.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2021-11-15 `4a6c6b0e` ajout `^1.20` — « Update: Add geofield contrib module. »

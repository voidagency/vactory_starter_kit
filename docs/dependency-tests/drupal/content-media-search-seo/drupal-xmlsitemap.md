# drupal/xmlsitemap

| | |
|---|---|
| Contrainte composer.json | `^1.2` |
| Version lockée | `1.5.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — hook d'altération des liens, config modifiée à l'install du profil, propriété `xmlsitemap` posée sur les nodes générés. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_core | `modules/vactory_core/vactory_core.module:423` | `hook_xmlsitemap_link_alter` (exclut la home) |
| profil | `vactory_starter_kit.install:67` | `xmlsitemap.settings` → `disable_cron_regeneration = TRUE` |
| profil | `config/install/xmlsitemap.settings.yml` | Settings |
| vactory_dynamic_field_dummy | `modules/vactory_dynamic_field/modules/vactory_dynamic_field_dummy/src/Form/GeneratePageForm.php:181` | `$node->xmlsitemap = [status, status_override, priority…]` |
| info.yml | `vactory_starter_kit.info.yml:133` | Profil |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `modules/vactory_core/tests/src/Kernel/XmlSitemapLinkAlterTest.php`** — appeler `vactory_core_xmlsitemap_link_alter()` avec `loc` = page front → `status === 0` et accès refusé. Échoue aujourd'hui (voir notes).

## Notes
Bug : `vactory_core.module:428` écrit `$link['acccess']` (3 « c ») — la clé `access` n'est jamais mise à FALSE ; seul `status = 0` agit.
`vactory_checklist/src/Plugin/Checklist/SitemapHostCheck.php:43` vérifie `/{lang}/sitemap.xml` côté front (pas l'API du module).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.0-rc1`
- 2021-01-31 `1be86d53` épinglage exact `^1.0-rc1` → `1.0` — « Update: Add v2.1.0 Factory release changes. »
- 2022-06-09 `02645c51` montée `1.0` → `^1.2` — « Update: update not supported contrib modules. »

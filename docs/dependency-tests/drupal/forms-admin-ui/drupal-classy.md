# drupal/classy

| | |
|---|---|
| Contrainte composer.json | `^1.0` |
| Version lockée | `1.0.2` |
| Patches | aucun |
| Statut | Indirect (librairie de thème attachée depuis des templates, thème non installé) |
| Risque à la MAJ | Moyen — 16 templates `full` font `attach_library('classy/node')` ; retrait du paquet = erreur de librairie inconnue au rendu (comportement exact à vérifier). |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_news | `templates/full.html.twig:66` | `{{ attach_library('classy/node') }}` |
| vactory_job_ads | `templates/full.html.twig:79` | idem |
| vactory_governance | `templates/full.html.twig:75`, `card-inline.html.twig:79`, `masonry.html.twig:78` | idem |
| vactory_event, vactory_blog, vactory_partner, vactory_forums, vactory_annual_report | `templates/full.html.twig:75` | idem |
| vactory_publication, vactory_publication_prive | `templates/full.html.twig:77` | idem |
| vactory_mediatheque | `templates/full.html.twig:86` | idem |
| vactory_quiz | `templates/full.html.twig:66` | idem |
| vactory_dynamic_field_unitegallery | `templates/full.html.twig:75` | idem |
| vactory_social_network | `templates/post-full.html.twig:18` | idem |

Thème jamais installé (`themes:` du profil = `vactory`, `claro`) ; la librairie `node` existe bien dans `vendor/drupal/classy/classy.libraries.yml:83`.

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `vactory_news/tests/src/ExistingSite/NewsFullRenderTest.php`** — créer un node `vactory_news`, GET de sa page canonique (thème front) → 200 et présence d'un CSS `classy/…/node.css` dans le HTML (ou absence d'erreur dans `watchdog`). Attrape : suppression du paquet/librairie. Mutualisable avec le test de rendu twig_tweak.

## Notes
- Classy est retiré du core en D10 ; dépendance purement legacy. Recommandé : supprimer les `attach_library('classy/node')` puis le paquet.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2023-07-17 `e238b528` ajout `^1.0` — « Fix: #44188 - Drupal 10 compatibility: Add rdf, seven, classy and quickedit contrib extensions »

# drupal/linkit

| | |
|---|---|
| Contrainte composer.json | `7.0.12` (épinglée) |
| Version lockée | `7.0.12` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — élément de formulaire `#type => 'linkit'` + route d'autocomplete utilisés dans le champ URL des Dynamic Fields (saisie de tous les liens des composants). |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : downgrade 7.0.16 → 7.0.12 (`776f9ae0`, #69237) et épinglage après incident (`dc656dbf`, #64112 autocomplete DF cassé en 7.0.13) (avant : P2) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:139` | Installé |
| profil | `config/install/filter.format.{basic_html,full_html}.yml` (l.6, 44-46 / 58-60) | Filtre `linkit` |
| profil | `config/install/editor.editor.{basic_html,full_html}.yml:67-71` | Plugin CKEditor5 `linkit_extension`, profil `default` |
| vactory_dynamic_field | `vactory_dynamic_field.info.yml:15` ; `src/Element/UrlExtendedElement.php:66-73` | `'#type' => 'linkit'`, `'#autocomplete_route_name' => 'linkit.autocomplete'`, paramètre `linkit_profile_id: default` |
| vactory_decoupled | `config/install/jsonapi_extras.jsonapi_resource_config.linkit_profile--linkit_profile.yml` | Ressource JSON:API configurée |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `vactory_dynamic_field/tests/src/ExistingSite/LinkitAutocompleteTest.php`** — créer un node publié « Alpha », en admin GET `/linkit/autocomplete/default?q=Alph` → JSON contenant le node. Puis construire l'élément `url_extended` via `form_builder` → sous-élément `#type linkit` présent. Attrape : renommage de route/élément/profil, casse du filtre.

## Notes
- Épinglée en dur `7.0.12` : aucune MAJ automatique, mais la montée core peut la rendre incompatible.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^5.0-beta10`
- 2021-01-31 `1be86d53` réécriture contrainte `^5.0-beta10` → `^5.0-beta11` — « Update: Add v2.1.0 Factory release changes. »
- 2023-06-15 `6e1c1a44` montée `^5.0-beta11` → `^6.0@RC` — « Update: #41230 - Update linkit contrib module »
- 2023-07-18 `93ab3a6e` montée `^6.0@RC` → `^6.1` — « Fix: #44188 - Drupal 10 compatibility: Drupal ^10.1 compatibility »
- 2024-04-24 `23f826e9` ajout dans le profil (install list) #50957 « Feat: #50957 add Linkit to fix DF wysiwyg entity path »
- 2025-02-04 `29ee97a7` usage étendu #55421 « Url Extend / Autcomplete comme linkit » (élément `#type linkit` dans `UrlExtendedElement`)
- 2025-08-28 `8b362e2c` montée `^6.1` → `^7.0` — « fix: #60305 - Upgrade Linkit »
- 2026-04-20 `dc656dbf` épinglage exact `^7.0` → `7.0.12` — « fix: update linkit dependency (fix 7.0.12) »
- 2026-09-17 `f337dbc2` montée (épinglée) `7.0.12` → `7.0.16` — « Update: upgrade profile dependencies and support Metatag 2 »
- 2026-10-05 `776f9ae0` downgrade `7.0.16` → `7.0.12` — « refactor: #69237 - downgrade linkit version to 7.0.12 »

### Incident 7.0.12 → 7.0.16 → 7.0.12
- **Premier incident (7.0.13)** — #64112 (créé 2026-03-05, projets RMA) : avec `^7.0`, la MAJ du profil 3.1.5 installe `7.0.13` → erreur « Linkit Autocomplete » dans les Dynamic Fields (captures dans le ticket). Contournement côté projet : retour à `7.0.8` (version du lock du profil). Correctif profil : `dc656dbf` (2026-04-20, PR #874 `fix/64112`) épingle `7.0.12` ; ticket fermé le 2026-11-02 « Linkit figé à 7.0.12 ».
- **Re-montée** — `f337dbc2` (2026-09-17, PR #880 `feat/update_62859`, ticket #62859 « MAJ Core et modules ») passe à `7.0.16`. Le lock de 7.0.16 exige `drupal/core ^10.2 || ^11` (7.0.12 : `^10.1 || ^11`).
- **Downgrade** — `776f9ae0` (2026-10-05, PR #887, tag `3.2.1`) revient à `7.0.12`, ticket #69237 « Downgrade linkit version » : sans description ni commentaire (source Slack).
- **Cause : inconnue.** Pas de CHANGELOG ni de sources 7.0.13–7.0.16 en local (cache composer : 6.1.6–6.1.8, 7.0.8, 7.0.11 ; `vendor/drupal/linkit` = **7.0.8**, pas la version lockée 7.0.12). Hypothèse non prouvée : même régression que #64112 (autocomplete dans `UrlExtendedElement`, `#type linkit` + `linkit.autocomplete`), présente dès 7.0.13.
- Toujours ouvert après le downgrade : #70121 (Bug, Nouveau, 2026-10-22) « Wysiwyg & linkit / erreur lors de la modification d'un numéro de téléphone » (capture seule).
- Antécédents : #52782 (2024) « Issue Linkit vs ckeditor » (linkit ne remonte pas, `/node/ID` non converti en alias), rouvert une fois après « fix non poussé ».
- Conséquence test : le test `LinkitAutocompleteTest` (ci-dessus) doit passer sur 7.0.16+ avant tout dé-épinglage ; ajouter le cas `/node/ID` → alias (#52782).

## Suivi (2026-11-19)
- **Cause de #64112 trouvée** : linkit ≥ 7.0.13 exige un `data-drupal-selector` finissant par `-uri`. Sans ça, dès que le formulaire contient au moins deux liens, seul le premier reçoit l'autocomplete (et sa sélection ne remplit pas le champ), et les suivants n'ont aucune suggestion.
- Correctif et tests : PR #912 (`UrlExtendedElement::afterBuildLinkitSelector()`, `LinkitIntegrationTest`, `DynamicFieldLinksNormalizerTest`). Vérifié à la main dans Chrome sur 7.0.12 et 7.0.16, avec et sans le correctif (Dynamic Fields multiples, groupes, CKEditor, JSON:API).
- Suite : relâcher la contrainte à `^7.0.16` après le merge de #912 (#71222).

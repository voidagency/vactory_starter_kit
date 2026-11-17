# Brief recon — dépendances Composer → usage → tests (ticket Redmine #71097)

## But
Chaque montée de version d'une dépendance casse des choses découvertes tard. On veut savoir, pour chaque paquet de `composer.json` (`require`) : est-il utilisé par le code du profil, où, via quelle API, est-ce testé, et quel test écrire pour valider **notre usage** (pas la lib elle-même).

## Repo
- Racine : `/Users/user/dev/vactory_starter_kit` (profil Drupal, branche `3.x`). Code : `modules/**`, `src/`, `vactory_starter_kit.*`, `config/`.
- Ignorer : `vendor/`, `node_modules/`, `modules/mae_group_poc/` (POC non suivi), `.agents/`, fichiers `_explore*.sh`.
- Versions installées : `composer.lock`. Patches : `composer.json` → `extra.patches`.
- Drupal core 10.5.x. PHP 8.3.

## Tests existants (15 fichiers) et CI
- Tests : `find modules -name '*Test.php' -not -path '*/vendor/*'` (Functional dans `vactory_decoupled/**`, 2 Kernel).
- CI Drone (`.drone.yml` → image `vactory/vactory-ci`, source `~/dev/vactory-test-ci/entrypoint.sh`) : `drush si vactory_starter_kit` puis `phpunit` + drupal-test-traits (DTT) sur tout le profil. Testsuites prévues : `*/tests/src/{Unit,Kernel,ExistingSite,ExistingSiteJavascript}`.
- Type de test à proposer : **Unit** (logique pure, lib PHP isolable), **Kernel** (services, entités, plugins sans HTTP), **ExistingSite DTT** (site installé avec le profil : routes, JSON:API, formulaires, rendu). Préférer le plus léger qui attrape la régression.

## Comment détecter l'usage d'un paquet
Un paquet peut être utilisé sans `use` PHP. Chercher (grep quoté, shell zsh : toujours quoter les globs `--include='*.php'`) :
- Namespace PHP (`Drupal\<module>\`, `Vendor\Lib\`), classes, services (`\Drupal::service('…')`, `*.services.yml` arguments `@…`).
- Dépendances de module : `*.info.yml` (`dependencies: - <projet>:<module>`), y compris sous-modules du paquet.
- Plugins/annotations/attributs du paquet (`@FieldType`, `@SearchApiProcessor`, `@ResourceType`, field widgets/formatters, views plugins id…), hooks implémentés (`hook_<module>_*_alter`), events.
- Config installée : `config/install`, `config/optional`, `config/sync` du profil et modules (`module: <x>`, `dependencies.module`, types de champs, plugin ids). Config = usage indirect.
- Twig (`*.html.twig` : filtres/fonctions de twig_tweak, twig_field_value…), JS/libraries.
- `vactory_starter_kit.info.yml` (install list du profil).
Un paquet trouvé **uniquement** comme dépendance dans `composer.json` sans aucune trace = **non utilisé**.

## Livrable (écrire UNIQUEMENT tes fichiers)
1. Une fiche par paquet : `docs/dependency-tests/<dossier-du-lot>/<vendor>-<package>.md` (ex. `drupal-search_api.md`). Gabarit :

```markdown
# <vendor/package>

| | |
|---|---|
| Contrainte composer.json | `^x` |
| Version lockée | `x.y.z` |
| Patches | aucun / liste |
| Statut | Utilisé · Indirect (config/dépendance seulement) · Non utilisé |
| Risque à la MAJ | Élevé · Moyen · Faible — 1 phrase pourquoi |
| Couverture actuelle | Aucune · Partielle · Bonne |
| Priorité test | P1 · P2 · P3 · — |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|

## Tests existants qui l'exercent
- `chemin/Test.php` — ce qui est couvert. Ou « aucun ».

## Tests à écrire
- **[Kernel|Unit|ExistingSite] `module/tests/src/<Type>/<Nom>Test.php`** — scénario précis (entrée → assertion). Ce qu'il attraperait.

## Notes
Contraintes de version incohérentes avec l'API utilisée, API dépréciée, patch fragile, etc. Faits seulement ; marquer « à vérifier » ce qui n'est pas prouvé.
```

Paquets non utilisés : pas de fiche complète, une ligne dans ta matrice partielle suffit (avec la preuve : « 0 occurrence de X, Y, Z »).

2. Une matrice partielle : `docs/dependency-tests/_partials/<lot>.md` — un tableau, une ligne par paquet de ton lot :
`| Paquet | Statut | Nb usages | Modules | Couverture | Risque | Priorité | Fiche |`

## Règles
- Lecture seule sur le code : ne modifie RIEN hors `docs/dependency-tests/`. Pas de git, pas de composer, pas de drush.
- Toujours `fichier:ligne` vérifiés. Pas d'invention ; si incertain, écrire « à vérifier ».
- Priorité : P1 = utilisé dans du code métier critique (auth, API JSON, import/export, paiement/formulaires) sans test ; P2 = utilisé, impact moyen ; P3 = usage cosmétique/admin ; — = non utilisé.
- Français, concis.
- Fin : réponds avec un résumé ≤ 15 lignes (nb paquets utilisés/indirects/non utilisés, top 5 P1, constats surprenants).

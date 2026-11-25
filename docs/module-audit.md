# Audit des modules : installation, désinstallation, dépendances

`scripts/module-audit/module-audit` mesure, module par module, ce que fait l'installation seule, la désinstallation puis la réinstallation de chaque module du profil, et vérifie par exécution les dépendances que le code laisse soupçonner. Il se lance avant chaque release. Première exécution : Redmine #71479 (215 modules ; constats dans les tickets enfants #71506 à #71513).

Rien n'est déduit du code : une ligne du rapport vient toujours d'une exécution. La lecture du code (grep) sert seulement à choisir les paires de dépendances à mesurer.

## Prérequis
- La commande `stack` (`scripts/stack/stack`, PR #976, voir `docs/dev-stack.md`) configurée (`stack init`) avec sa base de référence (`stack baseline`). Tant que #976 n'est pas fusionnée : extraire sa branche `feat/71475-dev-stack` et donner le chemin de la commande dans `VACTORY_STACK_BIN`.
- `python3`, avec PyYAML ou `uv` (utilisé alors pour PyYAML), `curl`, `git`.
- Mémoire : les conteneurs d'une instance (web et base) ont occupé de 0,5 à 0,9 Go pendant la première exécution, sans compter les autres projets ddev ouverts. **Deux instances au plus** (limite de la commande, `MODULE_AUDIT_MAX_INSTANCES` pour la changer) : avec trois instances, la VM Docker (9 Go) a lâché une fois pendant la première exécution.

## Lancer l'audit avant une release

```
scripts/module-audit/module-audit run --instances 2 --ref origin/3.x
```

Une seule commande : elle crée les instances (`stack up audit-1 --ref …`), mesure tous les modules, prouve les dépendances, puis écrit `RESULT.md`. Les résultats vont dans `~/dev/.vactory-module-audit/<date>/` (`MODULE_AUDIT_HOME` pour changer le dossier, `--out` pour un dossier précis), jamais dans le dépôt. Le lien `latest` pointe vers le dernier audit.

| Commande | Effet |
|---|---|
| `run [--instances N] [--modules all\|m1,m2] [--ref <commit\|branche>] [--prefix <nom>] [--no-deps] [--out <dossier>]` | Audit complet, ou limité à quelques modules. `--ref` : code du profil audité (défaut `origin/3.x`, un commit : les modifications non commitées ne sont pas auditées). Instances `audit-1`… (`--prefix`). |
| `resume [--out <dossier>]` | Reprend un audit interrompu (arrêt, panne de Docker) avec les mêmes réglages : ce qui est mesuré est passé. |
| `deps [--only M:X,…] [--out <dossier>]` | Preuves de dépendances seules, pour les modules de l'audit, ou pour quelques paires. |
| `report [--out <dossier>]` | Réécrit `RESULT.md`. |
| `status [--out <dossier>]` | Avancement. |
| `clean [--out <dossier>]` | Supprime les instances de l'audit (`stack rm`) ; les résultats restent. |

Durée mesurée (première exécution, deux instances) :
- création d'une instance : environ 4 min ; préparation (snapshot, passage témoin) : environ 2 min ;
- modules : environ 150 s par module, référence des restes comprise (mesure seule : 121 s en moyenne, de 51 s à 400 s) : 215 modules sur deux instances ≈ 5 h ;
- dépendances : 26 paires « déclarée inutile » en 13 min, 298 paires « utilisée non déclarée » en 72 min, sur deux instances.

Un audit complet prend donc environ 7 h : le lancer le soir, `status` le lendemain, puis `clean`. Pour refaire un seul module : supprimer `raw/<module>/` dans le dossier de l'audit, puis `resume`.

## Ce qui est mesuré, et comment

### Un module
Avant chaque module, l'instance revient au snapshot `audit-base` (base et fichiers ; pris après l'installation du profil, `dblog` activé). Puis :
1. **Installation seule** (`drush en <module> -y`), pour un module que le profil n'active pas : réussite, modules tirés, message d'erreur, erreurs du journal.
2. **Ajouts de l'installation** hors configuration : contenus, liens de menu, placements de blocs, page d'accueil, révisions, fichiers, état.
3. **Santé** : `drush cr`, `drush cron`, pages (accueil, connexion, routeur découplé et JSON:API de la page d'accueil, six pages d'administration, routes sans paramètre ajoutées par le module), nouvelles erreurs du journal. Le site est « cassé » sur un échec de `cr`/`cron` ou une page en erreur ; de nouvelles erreurs du journal seules ne le cassent pas, mais classent le module dans « New log errors (site still works) ».
4. **Désinstallation** (`drush pmu <module> -y`) : refus, modules emportés en cascade, puis santé comme en 3.
5. **Restes** : ce qui reste après la désinstallation (tables, configuration, état, `key_value`, permissions, contenus, liens de menu, fichiers), comparé à la base plus les seules dépendances que l'installation a tirées (elles restent légitimement).
6. **Réinstallation** (`drush en`, puis les modules emportés en cascade) : réussite et doublons (contenus ou liens recréés une seconde fois).

Un **passage témoin** (les mêmes étapes sans module, à la préparation de chaque instance) donne le bruit : ce qui change sans module (cron, caches, files d'attente) n'est pas imputé au module.

Un module activé par le profil n'a pas d'étape 1 à 3 : seulement la désinstallation, les restes qui portent son nom et la réinstallation.

### Les dépendances
Deux listes de candidats sortent de la lecture du code (`lib/static.py`) :
- **utilisée non déclarée** : M utilise X (espace de noms `Drupal\X\`, ou dépendance d'une config de M), X n'est pas dans les dépendances de M, ni directes ni indirectes ;
- **déclarée inutile** : M déclare D, et le mot D n'apparaît dans aucun autre fichier de M.

Chaque paire est mesurée par une **sonde Kernel** (`php/DepProbeTest.php`, lancée par `lib/kprobe.py` dans l'instance) : M est installé par l'installateur de modules sur un site Kernel vierge (tables préfixées, sans le profil, seulement `system` et `user`), puis exercé (reconstruction des caches et des routes, chargement des classes de `src/`, services, plugins fournis par M et leur instanciation, définitions de champs, formulaires et contrôleurs de ses routes, entités de configuration installées, `hook_cron`).
- Utilisée non déclarée : passage A sans X, puis **passage témoin** B avec X. X est prouvé nécessaire seulement si A montre une erreur qui nomme X, qui vient de M (sa classe, son service, son plugin, sa route, sa config, un de ses fichiers) et qui disparaît dans B.
- Déclarée inutile : D est retiré de la copie du `.info.yml` de M dans l'instance pendant le passage A (remis ensuite), le passage témoin B garde le `.info.yml` d'origine. D est inutile seulement si A installe M sans aucune erreur que B n'a pas.

Pourquoi une sonde Kernel plutôt que le site : un module activé par le profil (views, comment, content_translation…) ne peut pas être désinstallé du site pour voir ce qui casse sans lui ; sur le site Kernel, il n'est jamais là.

## Lire `RESULT.md`
- Le tableau : une ligne par module, une colonne par étape ; chaque cellule renvoie à la preuve (`raw/<module>/summary.md`, détail dans `result.json` et les sorties brutes du même dossier ; `deps/…/<M>__<X>.json` pour une dépendance).
- « Modules that need a fix » : les modules regroupés par défaut (installation seule en échec, site cassé après la désinstallation, contenus laissés, doublons à la réinstallation, dépendance non déclarée prouvée, dépendance déclarée inutile…), chacun avec son lien de preuve.
- « Dependency candidates » : les paires non prouvées, classées par raison (non mesurable : masquée par une dépendance de M, X tiré par un autre chemin, M ne s'installe pas sur un site vierge ; non prouvée : même erreur avec X, chemin de code non atteint).
- « Clean » : modules sans aucun constat.

## Limites connues
- **Une page à 0 (pas de réponse)** peut venir d'une coupure du routeur ddev et non du module. Quand toutes les pages sont à 0, la mesure est marquée invalide et refaite par `resume`. Une page isolée à 0 doit être remesurée (supprimer `raw/<module>/`, puis `resume`) avant d'être imputée au module.
- **« not proven » ne veut pas dire « innocent »** : la sonde n'a pas atteint le chemin de code qui utilise X, ou une autre erreur l'a masqué. Seuls « NEEDED » et « no error observed » sont des résultats.
- Candidats non mesurés, par règle de sélection (`lib/static.py pairs`) : les modules du core toujours présents (`system`, `user`, `field`, `filter`, `text`, `node`, `path_alias`, `file`, `image`) ; une utilisation vue seulement dans `config/optional` (Drupal ignore cette config quand le module manque) ; une dépendance déclarée que le profil active, ou qu'une autre dépendance déclarée de M tire déjà ; `default_content` déclaré par un module qui a un dossier `content/`.
- La sonde exerce le code chargé à l'installation et au démarrage (classes, services, plugins, routes, config, cron), pas les requêtes du front ni les parcours utilisateur.
- Les mesures valent pour le site de la base de référence de `stack` (copie du site de développement) : un contenu absent de ce site ne révèle pas l'erreur qu'il provoquerait.
- Les modules de test (dossier `tests/`, paquet `Testing`) sont exclus.

## Fichiers
- `module-audit` : commande ; `lib/common.sh`, `lib/measure.sh` : mesure d'un module ;
- `lib/static.py` : inventaire et candidats ; `lib/diff.py` : comparaison des états et `result.json` ; `lib/kprobe.py` : preuves de dépendances ; `lib/report.py` : `RESULT.md` ;
- `php/snap.php` (état du site), `php/wd.php` (erreurs du journal), `php/DepProbeTest.php` (sonde Kernel). Ils tournent dans l'instance, jamais sur un site de projet ; la sonde est hors des dossiers `tests/` des modules, la CI ne la lance pas.

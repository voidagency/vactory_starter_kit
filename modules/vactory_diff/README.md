# Vactory Diff

Guide d’utilisation pour comparer et synchroniser la configuration
(et, en option, le contenu) entre deux instances Drupal.

## Modules inclus
- `vactory_diff` (parent)
- `vactory_diff_config_server` (à activer sur l’instance « serveur » qui
  expose l’export de config)
- `vactory_diff_config_client` (à activer sur l’instance « cliente » qui
  lance la comparaison)
- `vactory_diff_content` (optionnel, pour comparer/synchroniser du contenu via
  JSON:API)

## Prérequis
- Drupal 9/10 (client/serveur). Pour `vactory_diff_content`: Drupal 10/11 +
  module core `jsonapi`.
- Drush installé sur vos environnements.
- Optionnel mais recommandé côté serveur: un module d’authentification par
  clé API et la permission d’accès dédiée.

## Installation rapide
1) Sur l’instance SERVEUR (source des configurations)
   - Activer: `vactory_diff`, `vactory_diff_config_server`.
   - Créer une clé API et donner la permission: “access vactory diff config
     export”.
   - Noter l’URL de l’endpoint d’export: `/api/vactory-diff/config/export`.

2) Sur l’instance CLIENT (là où vous comparez)
   - Activer: `vactory_diff`, `vactory_diff_config_client`.
   - Optionnel (contenu): activer aussi `vactory_diff_content` et `jsonapi`.

Exemples Drush:
```bash
drush en vactory_diff vactory_diff_config_server -y   # côté serveur
drush en vactory_diff vactory_diff_config_client -y   # côté client
drush en vactory_diff_content jsonapi -y              # optionnel (contenu)
```

## Configuration côté CLIENT
Menu: Administration → Configuration → Développement → Vactory Diff

- **URL de l’instance distante**: `https://votre-serveur.exemple`
- **Clé API**: la clé générée côté serveur (avec la permission d’accès)
- **Timeout de connexion**: délai de requête (en secondes)
- **Chemins des modules custom**: chemins où scanner des features (un par ligne)
- **Modules à ignorer**: modules à exclure de la TODO (un par ligne).
  Par exemple:
  - `devel`
  - `devel_generate`
  - `features_ui`
  - `views_ui`
  - `devel_entity_updates`
  - `field_ui`
- **Types d’entités à comparer (contenu)**: cochez les entités à inclure pour
  le diff de contenu

Bouton “Tester la connexion” disponible pour valider l’URL/clés.

## Comparer les configurations
1. Aller sur: Administration → Configuration → Développement → Vactory Diff
   → Comparaison
   - URL directe: `/admin/config/development/vactory-diff/compare`
2. Lancer la comparaison (un bouton déclenche l’analyse)
3. Lire les résultats: éléments ajoutés, modifiés, supprimés; ouvrir les
   diffs détaillés (modales)

Les derniers résultats restent disponibles pour consultation.

## Générer et utiliser la TODO List
1. Aller sur: `/admin/config/development/vactory-diff/todo`
2. Vous y trouverez:
   - Un résumé (features à revert, configs traitées, correspondances)
   - Les commandes Drush prêtes à copier pour chaque feature:
     `drush fr <feature>`
   - Les modules à installer/désinstaller détectés (avec commandes `drush en`
     / `drush pmu`)
   - Les configurations non associées à une feature (à traiter manuellement)
   - Un bouton pour télécharger la TODO au format texte
3. Ajustez au besoin la liste “Modules à ignorer” dans les paramètres pour
   masquer vos modules de dev/outils.

## Travail sur le contenu (optionnel)
Activer `vactory_diff_content` et `jsonapi`.

Deux usages complémentaires:
- Dans Paramètres, sélectionnez les types d’entités à inclure.
- Générez un rapport CSV de diff via Drush côté client:
  ```bash
  drush vactory_diff_content_fetch   # alias: drush vcd-fetch
  ```
  Le rapport est sauvegardé (ex: `private://content-diff/report.csv`).

Sur la page TODO, une section “Content Sync” propose:
- Des commandes d’export (par type) à exécuter en PREPROD/INT
- Copiez ensuite les archives générées vers PROD
- Puis importez en PROD via:
  ```bash
  drush content:import [archive_path]
  ```

## Permissions
- “administer vactory diff” (accès aux pages d’admin)
- “access vactory diff config export” (accès API côté serveur)

## Dépannage
- Erreur de connexion: vérifiez l’URL distante, la clé API, le timeout et les
  en-têtes/cors si nécessaire.
- Résultats vides: lancez d’abord une comparaison côté client.
- Modules indésirables dans la TODO: ajoutez-les à “Modules à ignorer”.

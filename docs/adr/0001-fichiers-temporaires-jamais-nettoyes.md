---
status: accepted
---
# Les fichiers temporaires ne sont jamais nettoyés

Le profil livre `system.file:temporary_maximum_age: 0` : le cron de Drupal ne supprime aucun fichier temporaire. C'est voulu. Les images insérées dans un CKEditor des Dynamic Fields restent des fichiers temporaires (le HTML vit dans le JSON `widget_data`, que le module `editor` du core ne lit pas) et doivent rester disponibles sans enregistrement dans `file_usage`.

## Consequences
- Un projet qui revient à la valeur Drupal par défaut (6 h) perd ces images au cron suivant.
- Les fichiers ne sont pas libérés quand un bloc ou un paragraphe est supprimé, et le rapport d'usage des fichiers ne les compte pas.
- Rendre ces fichiers permanents avec suivi d'usage (PR #955, Redmine #71224) change ce comportement : ce n'est possible qu'après deux tests canary concluants.

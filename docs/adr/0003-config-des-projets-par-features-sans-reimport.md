---
status: accepted
---
# Config des projets par Features, sans réimport par défaut

Les projets gèrent leur configuration avec Features, pas avec la config sync du core. La config livrée par le profil sert à l'installation : une release ne demande pas de `drush fr` (réimport d'une feature), sauf pour une liste précise de modules, en disant ce qui sera écrasé. Un réimport remplace la config active par celle du module et efface les valeurs propres au site (clés API, identifiants, réglages).

## Considered Options
- Config sync (`drush cim`) à chaque déploiement : écartée, les projets partagent des features et gardent des réglages propres à chaque site.

## Consequences
- Un changement qui doit atteindre les sites existants passe par un update hook, pas par la config livrée.
- Les notes de mise à jour raisonnent sur ce qu'un `drush fr` réimporterait ; la config sync est traitée comme un cas rare.
- `drush updb` et `drush cr` sont lancés à la main après le déploiement (capistrano ou Docker).

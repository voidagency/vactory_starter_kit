---
status: accepted
---
# L'analyse Sonar tourne dans un pipeline arm64 séparé

La CI tourne sur un Mac arm64, avec une image de build x86_64 sous émulation. Dans cette image, le moteur de Sonar (une JVM x86_64) gelait par moments en lançant un sous-processus (`git`, `node`), ce qui bloquait le build jusqu'au délai maximal. L'analyse Sonar est donc sortie de l'étape de build : elle tourne dans un second pipeline Drone, « sonar », sur une image arm64 native. Un build est vert quand les deux pipelines, « default » et « sonar », le sont.

La cause n'est pas prouvée. Le gel a été observé en direct (JVM entièrement figée au lancement d'un sous-processus, sous émulation), mais pas reproduit hors d'un build : l'émulation est la cause très probable. À l'appui : 18 analyses en arm64 sans gel, dont 11 d'affilée sous charge. Limite : pendant la même série, 15 analyses sous émulation n'ont pas gelé non plus.

## Considered Options
- Limiter l'analyse au PHP : écartée, le gel arrive aussi au lancement de `git`.
- Un seul build à la fois sur le runner : non testée ; elle diviserait le débit par deux.
- Passer toute l'image de build en arm64 : pas écartée, reste à tester. L'image PHP de base n'est publiée qu'en x86_64 parce que la production est en x86_64, mais sa base (UBI9) existe en arm64. Inconvénients : la CI testerait sur une autre architecture que la production, et il y aurait deux variantes d'image à maintenir.
- Faire tourner le moteur Sonar sur la Java de l'image (`skipJreProvisioning`) : non testée.
- Relancer l'analyse quand elle dépasse son délai : retenue en complément, pour les pipelines qui gardent Sonar dans l'étape de build (vactory8). Elle ne corrige pas la cause.
- Mettre à jour macOS et OrbStack sur la machine de CI : non retenue pour l'instant, effet non vérifié.

## Consequences
- Seule l'analyse change d'architecture : les images de production et l'image de build restent en x86_64.
- L'image de build garde son scanner. L'étape « setup » du profil le saute avec `SKIP_SONAR: "true"` ; sans cette variable, le comportement est inchangé (vactory8 garde Sonar dans l'étape de build).
- L'image du pipeline, `vactory/sonar-node-plugin:arm64`, est poussée à la main sur Docker Hub : l'autobuild ne produit que le tag `latest` en amd64. Si le plugin change, il faut reconstruire et repousser le tag arm64 (procédure dans le README du dépôt de l'image CI).
- Le pipeline « sonar » utilise les secrets Drone `sonar_host` et `sonar_token`, distincts du `SONAR_TOKEN` de l'étape « setup ».
- Le pipeline « sonar » prend une place du runner. Avec deux places, il attend derrière les étapes « default » un build sur deux, environ 25 minutes : le verdict Sonar arrive alors avec la fin des tests (Redmine #71381).
- Gains : analyse en 1 à 2 minutes au lieu de 2 min 30 à 3, en parallèle des tests.
- Références : Redmine #71371 (diagnostic et solution), Redmine #71381 (capacité du runner).

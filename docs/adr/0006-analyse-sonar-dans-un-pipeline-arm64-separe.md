---
status: accepted
---
# L'analyse Sonar tourne dans un pipeline arm64 séparé

La CI tourne sur un Mac arm64, avec une image de build x86_64 sous émulation. Dans cette image, le moteur de Sonar (une JVM x86_64) gelait par moments en lançant un sous-processus (`git`, `node`), ce qui bloquait le build jusqu'au délai maximal. L'analyse Sonar est donc sortie de l'étape de build : elle tourne dans un second pipeline Drone, « sonar », sur une image arm64 native. Un build est vert quand les deux pipelines, « default » et « sonar », le sont.

## Considered Options
- Limiter l'analyse au PHP : écartée, le gel arrive aussi au lancement de `git`.
- Un seul build à la fois sur le runner : réduit la fréquence du gel sans le supprimer.
- Passer toute l'image de build en arm64 : plus lourd, l'image PHP de base n'existe qu'en x86_64.

## Consequences
- La configuration vit dans `.drone.yml` (pipeline « sonar ») et dans le dépôt de l'image CI, pas seulement ici (Redmine #71371).

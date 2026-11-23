---
status: accepted
---
# Le lien des Dynamic Fields garde la clé `url`

Dans les Dynamic Fields, un lien (`url_extended`) enregistre sa cible sous la clé `url`, et le front lit cette clé. Depuis linkit 7.0.13, le JavaScript de linkit exige un sélecteur qui finit par `-uri`. Plutôt que de renommer le champ en `uri` (données existantes et front à migrer) ou de rester figé sur linkit 7.0.12, le profil réécrit seulement l'attribut `data-drupal-selector` de l'élément à l'affichage.

## Considered Options
- Renommer `url` en `uri` : écartée, cela casse les contenus existants et le contrat avec le front.
- Figer linkit en 7.0.12 : choix d'avril 2026, abandonné, car il prive les projets des correctifs de linkit.
- Patcher linkit : écartée, le correctif amont n'est pas publié et le patch serait à maintenir.

## Consequences
- Si linkit change de nouveau ce contrat, les tests `LinkitIntegrationTest` échouent en CI avant la release.
- Un projet qui a son propre contournement (élément `uri` ajouté à `url_extended`) le retire.

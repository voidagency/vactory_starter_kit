# Passes complémentaires

Décidées et **réalisées le 2026-11-17** (go de Hamza). Résultats : README §« Ce que dit l'historique », §« Vérification indépendante », §« Recon mi-aveugle ».

## Passe A — Historique des incidents

Déclencheur : linkit (épinglé, remonté, rétrogradé #69237) et jquery_colorpicker (forké, supprimé puis maintenu #62381) étaient en P2 faute d'historique.

- Pour chacun des 175 paquets : `git log -L` sur sa ligne dans `composer.json` et `extra.patches`, commits liés, tickets Redmine liés.
- Ajouter une section « Historique » à chaque fiche et une colonne « Incidents » à `_matrix.md`.
- Règle : downgrade, épinglage, fork ou patch correctif ⇒ **P1**.
- Corriger tout de suite linkit et jquery_colorpicker en P1.

## Passe B — Vérification des findings par Fable

- Agent(s) sur le modèle Fable, à qui on donne le code et les findings, **sans** nos conclusions sur leur validité.
- Pour chaque finding (`bugs.md`, constats des fiches, P1) : CONFIRMÉ / INFIRMÉ / À EXÉCUTER, avec la preuve `fichier:ligne`.
- Mettre à jour `bugs.md` et les fiches ; ticket Redmine pour chaque bug confirmé (consigne de Hamza).

## Passe C — Recon mi-aveugle, 2 tours

- Tour 1 : un agent reçoit la liste des findings déjà connus (`bugs.md` + constats transverses du README + P1) et doit trouver **d'autres** problèmes de dépendances, sans refaire ceux de la liste.
- Mettre à jour la liste des findings avec ses résultats (vérifiés).
- Tour 2 : nouvel agent, reçoit la liste mise à jour, même consigne.
- Mettre à jour la liste à nouveau.

## Ordre proposé

A → B → C (C profite de l'historique de A ; B peut aussi vérifier les nouveaux findings de C en fin de parcours).

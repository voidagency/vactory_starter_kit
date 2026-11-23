# Décisions d'architecture (ADR)

Un fichier par décision, numéroté dans l'ordre : `0001-sujet.md`, `0002-sujet.md`… Pour en ajouter un, prendre le plus grand numéro du dossier et ajouter un.

## Quand en écrire un
Seulement si les trois conditions sont réunies :
1. la décision est difficile à défaire ;
2. elle surprend sans son contexte ;
3. elle résulte d'un vrai arbitrage entre plusieurs options.

Facile à défaire, évidente, ou sans alternative réelle : pas d'ADR.

## Format
- Un titre, puis un à trois paragraphes courts : le contexte, la décision, et pourquoi.
- En option, seulement si cela apporte quelque chose :
  - `status` en en-tête : `proposed`, `accepted`, `deprecated` ou `superseded by ADR-NNNN` ;
  - **Considered Options** : les options écartées qu'il ne faut pas reproposer ;
  - **Consequences** : les effets qui ne sont pas évidents.
- Le dépôt est public : ni nom de client, ni détail d'exploitation d'une faille.

Une décision qui change ne se réécrit pas : on ajoute un nouvel ADR et on passe l'ancien en `superseded by ADR-NNNN`.

# Décisions d'architecture (ADR)

Ce dossier couvre le profil : son code, sa config livrée, ses dépendances et sa façon d'être consommé par les projets. La CI n'en fait pas partie : ses décisions sont suivies dans Redmine et dans le dépôt de l'image CI.

Un fichier par décision, numéroté dans l'ordre : `0001-sujet.md`, `0002-sujet.md`… Pour en ajouter un, prendre le plus grand numéro du dossier et ajouter un. Un numéro retiré n'est pas réutilisé : le 0006, qui portait sur la CI, a été retiré, le prochain ADR est donc le 0007.

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

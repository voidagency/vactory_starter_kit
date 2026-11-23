---
status: accepted
---
# Les projets figent la version du profil

Un projet exige une version publiée et figée du profil (`voidagency/vactory_starter_kit:<version>`), jamais `3.x-dev` ni une autre branche de dev. Une branche de dev livre des changements sans changelog, sans version candidate et sans passage par les projets pilotes : un simple `composer update` peut alors casser un site en production.

## Consequences
- Une montée de version du profil est toujours un acte volontaire : `composer require voidagency/vactory_starter_kit:<version> -W`, en suivant « Mise à jour des projets » du `CHANGELOG.md`.
- Les campagnes de mise à jour signalent ou refusent une contrainte `-dev`.

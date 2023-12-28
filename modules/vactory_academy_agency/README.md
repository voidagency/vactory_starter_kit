# Vactory Academy Agency
Ce module permet de créer des formations en présentiel qui seront
organisé sur une agence locator.

### Activation de module:
`drush en vactory_academy_agency -y`

### Requirements

- vactory_locator
- vactory_user
- vactory_academy

### Description
Une formation en agence est associée à une agence locator, elle dispose d'un
nombre de place limité.
Le type de contenu formation en agence dispose aussi d'un champ durée de
la formation et un champ datetime qui determine la date de début de la
formation.
Un utilisateur peut reserver son place en cliquant le bouton s'inscrire sur
la page listing des formations (`/formations-en-presentiel`) en agence ce listing n'est accessible qu'aux
utilisateurs authentifiés.

### Maintainers

Brahim Khouy
<b.khouy@void.fr>

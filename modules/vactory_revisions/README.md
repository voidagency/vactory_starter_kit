# Vactory revisions
Provides a new Vactory revision bundless entity with an admin listing page.
Also add a new user reference field "Last contributor" to all entities, which 
serve to store the last user updated the entity.

### Installation
`drush en vactory_revisions`

### Permissions
The module expose a custom permission "View entity revisions", only roles
with that permission could access the entities revision listing page.

### Maintainer
Brahim KHOUY <b.khouy@void.fr>
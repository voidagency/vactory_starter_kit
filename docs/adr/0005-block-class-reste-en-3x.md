---
status: accepted
---
# block_class reste sur la ligne 3.x

Le profil exige `drupal/block_class` `^3.0` et ne passe pas à la 4.x. La 4.x n'est pas la suite de la 3.x : elle vient de la 2.x, le projet la présente comme expérimentale, elle ajoute des attributs et une vingtaine d'update hooks, et le retour en arrière est impossible. Le seul avis de sécurité publié (SA-CONTRIB-2025-043) ne concerne que la 4.0.x.

## Consequences
- La sortie découplée lit la classe d'un bloc dans `third_party_settings.block_class.classes`, clé commune aux deux lignes.
- Depuis la 3.1, le formulaire d'un bloc refuse une classe qui n'est pas un identifiant CSS valide (par exemple une variante `md:hidden`).

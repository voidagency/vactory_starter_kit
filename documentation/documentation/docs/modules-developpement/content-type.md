- Rajouter systématiquement une description en anglais pour chaque Type de contenu, Taxonomie…. (Ne pas le laisser vide)

![Modules description](/docs_factory/assets/img/modules-description.png "Modules description")

- Par default tous les types de contenu doivent avoir le champs `vactory_field_image` (Pour le paratage sur les réseaux sociaux)
- Vérifier la configuration pathauto sur "/admin/config/search/path/patterns"
- Le nom label doit contenir uniquement le nom du module, pas de prefix vactory, c’est uniquement les noms machines qui doivent avoir `vactory_xxx` > Example Academy (Label) > vactory_academy (Machine Name). Cela va de soi pour les types de contenu, Champs, Views… Etc.
- Sur Manage Display (Content Type), Vérifier que les champs non pas de balise HTML (FENCES) Field Tag > None + Field Item Tag > None + Label Tag > None
- Sur Manage Display > Tout les label doivent être en mode - Hidden -
- Sur Manage form display > Les champs reference term (référence à une taxonomie) doivent utilisé le widget select et non pas autocomplete.
- Pour le champs Body, toujours décocher Summary input est utiliser le champs `vactory_field_excerpt` avec le formatter "Excerpt".
- Sur Manage Display > Désactiver le lien Taxonomie s'il existe.




# Vactory Dynamic Field

Vactory Dynamic Field permet de créer des widgets qui vont contenir des champs dynamiquement remontés en se basant sur un fichier `settings.yml` lié à chaque widget.
Le module ne contient qu'un seul champ qui peut être lié à n'importe quelle entité Drupal.

Une fois le champ mis en place, le widget commence par scanner le répertoire `components` pour charger la configuration de chaque widget.
Cette configuration est présentée au contributeur sous forme de formulaire.

Une fois ce dernier remplit est validé. Le module récupère les données et les stocke tel que données brut sérialisé dans le champ.

## Table of Contents
 * [Requirements](#requirements)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Extend](#extend)
 * [Api](#api)
 * [Troubleshooting & FAQ](#troubleshooting-faq)
 * [Maintainers](#maintainers)

## Requirements

Ce module dépend du module contrib `twig_tweak` (8.x-2.0) pur formatter le résultat des widgets.
Puisque le module `vactory_dynamic_field` ne créer pas des champs réel mais stock des données bruts dans un seul champs.
Ce dernier à besoin d'un moyen de formatter les données via Twig.

Le module `twig_tweak` fournit une extension Twig avec quelques fonctions et filtres utiles qui peuvent améliorer l'expérience de développement.

Prenant l'exemple d'un champ dynamic `Image`; Le module `vactory_dynamic_field` stocke uniquement la valeur `fid` pur ce champ.

`twig_tweak` permet d'afficher l'image brut facilement.

```code
 {# The argument can be FID, UUID or URI. #}
  <dt>Image:</dt>
  <dd>{{ drupal_image('public://ocean.jpg') }}</dd>

  {# Check out 'admin/config/media/responsive-image-style' page for available responsive image styles. #}
  <dt>Responsive image:</dt>
  <dd>{{ drupal_image('5', 'wide', responsive=true) }}</dd>
```

```hint|directive
Référence des fonctions et filtres Twig
- [Twig Tweak Cheat sheet](https://www.drupal.org/docs/8/modules/twig-tweak/cheat-sheet-8x-2x)
- [Functions - In Twig Templates](https://www.drupal.org/docs/8/theming/twig/functions-in-twig-templates)
```

## Installation

    drush en vactory_dynamic_field

## Configuration

La configuration des `widgets` ce fait au niveau du répertoire `widgets`; Chaque composant est placer dans un dossier avec un identifiant numérique.

### Exemple de la structure générale.

![Structure Widgets](/modules/vactory/vactory_dynamic_field/_docs/widgets-folder-structure.png "Structure Widgets")

### Example du fichier settings.yml

```code
fields:
  name_1:
      type: text
      label: 'Name 1'
  role_1:
      type: text
      label: 'Role 1'
  image_1:
      type: image
      label: 'Image 1'
  name_2:
      type: text
      label: 'Name 2'
  role_2:
      type: text
      label: 'Role 2'
  image_2:
      type: image
      label: 'Image 2'
  name_3:
      type: text
      label: 'Name 3'
  role_3:
      type: text
      label: 'Role 3'
  image_3:
      type: image
      label: 'Image 3'
```

#### Props
* `name_1` définit le **nom machine** du champ (la valeur de champ va être accéssible depuis le fichier twig via ce nom).
* `type` définit le type de champ; Valeurs possible: `text`, `image`, `file`, `url`, `email`, `number`, `tel`,`range`.
* `label` définit l'étiquette du champ.

### Exemple du fichier template.html.twig

```code

<section class="fdb-block team-2">
    <div class="container">
        <div class="row text-center justify-content-center">
            <div class="col-8">
                <h1>Meet Our Team</h1>
            </div>
        </div>

        <div class="row-50"></div>

        <div class="row text-center justify-content-center">
            <div class="col-sm-3 m-sm-auto">
                {{ drupal_image(content.image_1.0, 'vactory_generator_three_cols', responsive=true) }}

                <h2>{{ content.name_1 }}</h2>
                <p>{{ content.role_1 }}</p>
            </div>

            <div class="col-sm-3 m-sm-auto">
                {{ drupal_image(content.image_2.0, 'vactory_generator_three_cols', responsive=true) }}

                <h2>{{ content.name_2 }}</h2>
                <p>{{ content.role_2 }}</p>
            </div>

            <div class="col-sm-3 m-sm-auto">
                {{ drupal_image(content.image_3.0, 'vactory_generator_three_cols', responsive=true) }}

                <h2>{{ content.name_3 }}</h2>

                <p>{{ content.role_3 }}</p>
            </div>
        </div>
    </div>
</section>
```

### Champs supplémentaires

Si on prend l'exemple suivant:

![Chiffres clés](/modules/vactory/vactory_dynamic_field/_docs/widget-extra-field-example.png "Chiffres clés")

```code
name: 'Ciffres clés'
multiple: TRUE
category: 'Content'
enabled: TRUE
limit: 4
fields:
  image:
    type: image
    label: 'Thumbnail'
  description:
      type: text
      label: 'Description'
extra_fields:
  cta:
    type: url
    label: 'Read more link'
```

```hint|directive
Prenez note de la séction **extra_fields**, les champs **image** et **description** sont des champs multiple à l'éxception de **cta**
```

Le champs **cta** est accessible via twig depuis la variable **extra_fields**

```code
<h1 class="text-center">{{ extra_fields.cta }}</h1>
```

### Liste des types de champs

#### text
Un champ de texte sur une seule ligne.

```code
fields:
  first_name:
      type: text
      label: 'Prénom'
  last_name:
        type: text
        label: 'Nom'
```

#### textarea
Un champ de texte à plusieurs lignes.

```code
fields:
  description:
      type: textarea
      label: 'Déscription'
  about:
        type: textarea
        label: 'À propros'
```

```hint|directive
Utiliser le filtre [nl2br](https://twig.symfony.com/doc/2.x/filters/nl2br.html) pour gérer le saut de ligne.
```

```code
{{ content.0.description|nl2br }}
```

#### image
Un champ upload pour les images **(jpg gif png jpeg svg)**.

```code
fields:
  profile:
      type: image
      label: 'Photo de profil'
  thumbnail:
        type: image
        label: 'Vignette'
```

```hint
Il faut absolument faire un test (condition) sur la validité de ce champs avant de le faire passer la valeur à la fonction get_image().
```
```code
{% set image_uri = (content.0.image.0 is defined) ? get_image(content.0.image.0) : '' %}
```


#### file
Un champ upload pour les fichiers **(pdf doc docx txt)**.

```code
fields:
  cv:
      type: file
      label: 'CV'
  cover_letter:
        type: image
        label: 'Lettre de motivation'
```

#### url_extended
Un champ lien avec support liens internes et externes **(validation URL)**.

```code
fields:
  cta:
      type: url_extended
      label: 'CTA'
  read_more:
        type: url_extended
        label: 'En savoir plus'

#### url
Un champ lien **(validation URL)** -- Support liens externes.

```code
fields:
  cta:
      type: url
      label: 'CTA'
  read_more:
        type: url
        label: 'En savoir plus'
```

#### checkbox
Un champ case à cocher.

```code
fields:
  mode:
      type: checkbox
      label: 'Activer le mode inversé'
```

#### checkboxes
Un champ case à cocher multiple.

```code
fields:
  mode:
      type: checkboxes
      label: 'Options par défaut'
```

#### Node reference
Un champ node reference.

```code
fields:
  node:
        type: entity_autocomplete
        label: 'Node'
        options:
          '#target_type': node
          '#selection_settings':
            'target_bundles':
              - vactory_news
```

#### block reference
Un champ bloc reference.

```code
fields:
  block:
          type: block_field
          label: 'Block'
```

## Extends
Aucun

##  API
Aucun

## Theming
Aucun

## Troubleshooting & FAQ
#### Aucun

## Maintainers

* Hamza Bahlaouane : <h.bahlaouane@void.fr>

# Vactory Dynamic Field Generator
The Dynamic Field Templates Generator module for Drupal 
empowers developers to dynamically generates the necessary 
Twig templates for your custom dynamic fields located 
at modules/custom.

### Installation:
`drush en vactory_dynamic_field_generator -y`

### How To Generate templates

You can use the following command to generate the required templates 
for your custom dynamic fields.

`drush templates-generator`

alias : `drush tg`

Only widgets under `modules/custom` are handled. A template that already
exists and is not empty is left untouched.

### Troubleshooting

`Command "templates-generator" is not defined.`: the module is not enabled
(see Installation). The command is discovered by Drush itself (Drush 12 or
later, class in `src/Drush/Commands`): no `drush.services.yml` is needed.

### Maintainers
Khalid BOUHOUCH <k.bouhouch@void.fr>

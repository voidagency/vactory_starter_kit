# Vactory Sondage
Provides new vactory_sondage custom block type which serve to create surveys.

### Module vactory dependencies
- vactory_reminder

### Module installation
You could instal the module using drush command:
`drush en vactory_sondage -y`

### How it works?
The idea is simple you need to create a custom block of type
vactory_sondage then set the sondage details:
* Description: Will be appear as sondage introduction.
* Sondage Question: The sondage related topic/question.
* Sondage Options: The sondage options.
* Sondage close date: the date when the sondage should be closed
* Sondage status: the sondage status (closed or opened)

Then instanciate that block in a region or render it wherever
you want in your code using vactory_render() twig extension.


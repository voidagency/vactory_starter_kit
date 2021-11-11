# Vactory Quiz
Vactory quiz custom module provides a new content type Quiz which serve 
to create Quiz content, Quiz content type has question field of type Quiz
question.

The module also provides a block Vactory Quiz Block, which serve to associate
a quiz to a given node, all what we need to do is to specify the concerned quiz
in the block settings quiz autocomplete field.

The module has one additional submodule Vactory Quiz History, it allow us to
store user results and answers by each quiz in the database.

## Installer Le Module
For quiz module: `drush en vactory_quiz -y`

For quiz history module: `drush en vactory_quiz_history -y`

## Module settings

You could configure the module under `/admin/config/vactory_quiz` path.

## Watch a demo video
https://www.loom.com/share/a2d186d5349b48c9a284182608a798ee

## Maintainers
* Brahim KHOUY: <b.khouy@void.fr>

## Captcha

Tout formulaire ouvert au public nécessite un captcha, Le développeur doit alerter le graphiste/chef de projet s’il a été oublié.
Le module : https://www.drupal.org/project/recaptcha

## Un code sécurisé

- Utilisez `t()` et `\Drupal::translation()->formatPlural()` avec @ ou% placeholders pour construire des chaînes sécurisées et traduisibles.
- Utilisez `Html::escape()` pour le texte brut.
- Utilisez `Xss::filter()` pour le texte qui devrait autoriser certaines balises HTML.
- Utilisez `Xss::filterAdmin()` pour le texte entré par les utilisateurs admin qui devraient autoriser la plupart du HTML.
- Utilisez `UrlHelper::stripDangerousProtocols()` ou `UrlHelper::filterBadProtocol()` pour vérifier les URL - le premier peut être utilisé conjointement avec `SafeMarkup::format()`.


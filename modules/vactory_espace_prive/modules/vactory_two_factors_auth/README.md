# Vactory Two Factors Auth

Il s'agit d'un module personnalisé qui permet l'authentification
 en deux facteurs via un code OTP transmit à l'utilisateur via SMS ou Mail

### Configuration
 - Page de configuration
  `/admin/config/people/vactory_espace_prive/two-factors-auth`
- Sujet Mail : Le sujet de mail OTP valeur par defaut est:
"Code de vérification d'identité"
- Corps Mail : Le corps de mail si vide ça sera celui de la
config de vactory_otp
- SMS message : message SMS si n'est pas renseigné ça sera celui
de la config de vactory_otp
- Roles concernés : Sélectionner les rôles concernés par l'authentification
à deux facteur.
- Durée de vie OTP : Pour determiner la durée de vie de code OTP

### Dépendances
- vactory_espace_prive
- vactory_otp

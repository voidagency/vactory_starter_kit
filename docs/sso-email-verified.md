# SSO : e-mail vérifié chez l'IdP (`vactory_sso`, oauth_login_oauth2 3.1.1)

Depuis la 3.1.1 (profil 3.3.0, #992), `drupal/oauth_login_oauth2` refuse la connexion SSO quand l'IdP ne renvoie pas `email_verified = true` pour le compte. Le module relie le compte de l'IdP au compte Drupal par l'adresse e-mail : sans ce contrôle, quelqu'un qui peut choisir son e-mail chez l'IdP se connecte au compte Drupal qui a cette adresse. C'est la moitié du correctif de l'avis de sécurité SA-CONTRIB-2026-284. Le profil garde ce contrôle.

Message vu par l'utilisateur refusé : « Your email address could not be verified with the identity provider. Please contact the site administrator. »

## La règle du module

| Ce que l'IdP renvoie | Réglage « Trust provider email without email_verified claim » | Connexion |
|---|---|---|
| `email_verified = true` | sans effet | acceptée |
| `email_verified = false` | sans effet | **refusée** |
| rien (l'information est absente) | décoché (par défaut) | **refusée** |
| rien | coché | acceptée |

Keycloak renvoie toujours `email_verified` (scope `email`), à `false` pour un compte dont l'e-mail n'est pas vérifié : pour Keycloak, le réglage ne change rien. Le réglage ne sert qu'aux IdP qui n'envoient pas du tout l'information ; le cocher, c'est faire confiance à l'IdP pour ne donner que des adresses vérifiées.

## Avant la mise à jour : le projet est-il concerné ?

Avec un accès administrateur au Keycloak du client, dans le realm du site :

```bash
kcadm.sh config credentials --server https://<keycloak> --realm master --user <admin>   # demande le mot de passe
kcadm.sh get users/count -r <realm> -q emailVerified=false
kcadm.sh get users -r <realm> -q emailVerified=false --fields username,email --limit 1000
```

Ou dans la console d'administration : *Users*, colonne *Email verified*.

- **0** : le projet n'est pas concerné.
- **Plus de 0** : ces comptes seront refusés au prochain login SSO. Ne compte que ceux qui ont aussi un compte sur le site. Choix, avec le client :
  1. **vérifier ces e-mails chez l'IdP** (recommandé) : action *Verify email* envoyée à l'utilisateur, ou *Email verified* activé par l'administrateur du realm quand l'adresse est sûre (annuaire de l'entreprise) ;
  2. accepter que ces comptes soient refusés jusqu'à vérification.

Sans accès au Keycloak : l'onglet *Test Configuration* du module, lancé avec un compte, affiche les attributs reçus, dont `email_verified`. Cela ne vérifie qu'un compte à la fois : s'appuyer ensuite sur le relevé ci-dessous.

## Après la mise à jour : qui a été refusé ?

`vactory_sso` relève chaque connexion SSO refusée pour cette raison (le module ne l'écrit dans ses logs que si son mode debug est actif) :

- **rapport d'état** (`/admin/reports/status`) : ligne « SSO: unverified emails », en avertissement dès qu'une adresse a été refusée ;
- **liste** : `drush vactory-sso:unverified-emails` (alias `vsso-ue`) : adresse, compte Drupal qui a cette adresse, valeur reçue de l'IdP (`false` ou `absent`), nombre de refus, premier et dernier ;
- **log** : un avertissement du canal `vactory_sso` par refus ;
- une fois les e-mails vérifiés chez l'IdP : `drush vactory-sso:unverified-emails --reset`.

Le relevé passe par la réponse userinfo que lit `vactory_sso` ; un *Test Configuration* avec un compte non vérifié y apparaît aussi. 500 adresses au plus sont gardées.

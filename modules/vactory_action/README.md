## Vactory Action
Le module permet d'exposer le hook:

    hook_vactory_action_execute($action_id, $action_params, $account)
à implémter par d'autres modules pour définir la logique d'une action juste après
l'authentification (une action qui s'est déclenchée depuis une composante react
sur Gatsby) .

#### Paramètres du hook:
**$action_id**: L'identifiant de l'action (à définir côté React)

**$action_params**: Un tableau contenant les paramètres de l'action (à définir
côté React)

**$account**: les informations du compte de l'utilisateur authentifié.

#### Exemple de définition d'action côté React:

    ...
    // Prepare action infos query params.
    const actionInfos = {
      id: 'my_action_id', // The action ID here.
      params: { // The action params list here.
        param1: param1Value,
        param2: param2Value,
      }
    };
    // Utiliser loginAction method instead of login and pass action infos object as second param.
    const loginAction = userStuff.loginAction('fr', actionInfos)
    return <button onClick={loginAction}>Button label here</button>
    ...

Si vous souhaitez de plus faire une redirection vers une destination après
l'exécution de l'action vous devez rajouter la destination comme 3ème
paramètre de la method loginAction:

    // Go to /fr after action has been executed.
    const loginAction = userStuff.loginAction('fr', actionInfos, '/fr')
Si le paramètre detination est ignoré alors faire rediriger l'utilisateur
vers la page depuis laquel l'action d'est déclenchée.

#### Exemple d'implémentation du hook côté Drupal:

    /**
     * Implements hook_vactory_action_execute().
     */
    function my_module_vactory_action_execute($action_id, $action_params, $account) {
      if ($action_id === 'my_action_id') {
        // Add your action logic here.
        // You could access action params using:
        // $action_params['param1'], $action_params['param2']...
      }
    }



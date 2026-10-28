<?php

namespace Drupal\vactory_decoupled;

use Drupal\social_auth\User\UserManager as SocialAuthUserManager;

/**
 * Manages database related tasks.
 */
class UserManager extends SocialAuthUserManager
{

    /**
     * Default plugin id, used by downloadProfilePic() for file names and logs.
     */
    protected string $pluginId = 'vactory_decoupled';

    public function generateUniqueUsername(string $name): string {
        return parent::generateUniqueUsername($name);
    }

    public function userPictureEnabled(): bool {
        return parent::userPictureEnabled();
    }

}

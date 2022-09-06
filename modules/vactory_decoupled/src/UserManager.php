<?php

namespace Drupal\vactory_decoupled;

use Drupal\social_auth\User\UserManager as SocialAuthUserManager;

/**
 * Manages database related tasks.
 */
class UserManager extends SocialAuthUserManager
{

    public function generateUniqueUsername($name) {
        return parent::generateUniqueUsername($name);
    }

    public function userPictureEnabled() {
        return parent::userPictureEnabled();
    }

    public function downloadProfilePic($picture_url, $id) {
        return parent::downloadProfilePic($picture_url, $id);
    }

}
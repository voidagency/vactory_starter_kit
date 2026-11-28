<?php

namespace Drupal\vactory_cloudinary\Commands;

use Drupal\vactory_cloudinary\Drush\Commands\CloudinaryCommands;

/**
 * Former location of the move-to-cloudinary command.
 *
 * Kept until 3.5 for code that calls the vactory_cloudinary.commands service
 * or this class; the command itself is declared in
 * \Drupal\vactory_cloudinary\Drush\Commands\CloudinaryCommands.
 */
class VactoryCloudinaryMoveFiles extends CloudinaryCommands {

}

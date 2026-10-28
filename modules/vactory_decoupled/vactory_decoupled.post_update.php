<?php

/**
 * @file
 * Post update functions for Vactory Decoupled.
 */

/**
 * Enable simple_oauth_password_grant and the password grant on consumers.
 */
function vactory_decoupled_post_update_simple_oauth_password_grant() {
  if (!\Drupal::moduleHandler()->moduleExists('simple_oauth_password_grant')) {
    \Drupal::service('module_installer')->install(['simple_oauth_password_grant']);
  }
  \Drupal::moduleHandler()->loadInclude('vactory_decoupled', 'install');
  _vactory_decoupled_enable_oauth_password_grant();
}

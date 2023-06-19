<?php

namespace Drupal\vactory_migrate_plugin\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Element\ManagedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Override existing managed file form element.
 *
 * @FormElement("migrate_managed_file")
 */
class MigrateManagedFile extends ManagedFile {

  /**
   * {@inheritDoc}
   */
  public static function uploadAjaxCallback(&$form, FormStateInterface &$form_state, Request $request) {
    $response = parent::uploadAjaxCallback($form, $form_state, $request);
    $response = vactory_migrate_trigger_form_update($response);
    return $response;
  }

}

<?php

namespace Drupal\vactory_icon;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;

/**
 * Vactory icon provider interface.
 */
interface VactoryIconProviderInterface {

  /**
   * Build the icon provider settings form.
   *
   * @return array
   *   The form array.
   */
  public function settingsForm(Config|ImmutableConfig $config);

  /**
   * Provider settings form submit handler.
   */
  public function settingsFormSubmit(FormStateInterface $form_state, Config|ImmutableConfig $config);

  /**
   * Icon picker library info alter.
   */
  public function iconPickerLibraryInfoAlter(array &$library_info);

  /**
   * Icon picker form element alter.
   */
  public function iconPickerFormElementAlter(array &$element, ImmutableConfig|Config $config);

  /**
   * Icon provider description.
   *
   * @return \Drupal\Core\Annotation\Translation
   *   Icon provider translatable description.
   */
  public function description();

  /**
   * Fetch icons.
   */
  public function fetchIcons(ImmutableConfig|Config $config);

}

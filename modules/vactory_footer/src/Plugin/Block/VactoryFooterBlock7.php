<?php

namespace Drupal\vactory_footer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\mailchimp_signup\Form\MailchimpSignupPageForm;

/**
 * Provides a "Vactory Footer Block 7" block.
 *
 * @Block(
 *   id = "vactory_footer_block7",
 *   admin_label = @Translation("Vactory Footer Block V7"),
 *   category = @Translation("Footers")
 * )
 */
class VactoryFooterBlock7 extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $moduleHandler = \Drupal::service('module_handler');

    if ($moduleHandler->moduleExists('mailchimp')) {
      $signup_id = 'vactory_mailchimp_newsletter';
      $signup = mailchimp_signup_load($signup_id);

      $form = new MailchimpSignupPageForm(\Drupal::messenger());

      $form_id = 'mailchimp_signup_subscribe_block_' . $signup->id . '_form';
      $form->setFormID($form_id);
      $form->setSignup($signup);

      $content = \Drupal::formBuilder()->getForm($form);
      $content['#custom_theme'] = ['block_vactory_footer3__form_newsletter'];
    }
    else {
      $content = ['#markup' => t('Install Vactory Mailchimp for newsletter')];
    }

    return [
      "#cache"   => ["max-age" => 0],
      "#theme"   => "block_vactory_footer7",
      "#content" => ['form' => $content],
    ];

  }

}

<?php

namespace Drupal\vactory_whatsapp\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Whatsapp webhook callback annotation.
 *
 * @Annotation
 */
class WhatsappWebhookCallback extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @var string
   */
  public $label;

  /**
   * The concerned whatsapp fields (messages, account_update...).
   *
   * @var array
   */
  public $fields;

}

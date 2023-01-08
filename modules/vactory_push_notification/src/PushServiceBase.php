<?php

namespace Drupal\vactory_push_notification;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * A base class to help developers implement their own Push Service plugins.
 */
abstract class PushServiceBase extends PluginBase implements PushServiceInterface, ContainerFactoryPluginInterface
{
  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\vactory_push_notification\KeysHelper
   */
  protected $keysHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    $self = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('vactory_push_notification.keys_helper'),
      $container->get('string_translation')
    );
    return $self;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    KeysHelper $keys_helper,
    TranslationInterface $translation
    )
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Store the translation service.
    $this->setStringTranslation($translation);
    $this->configFactory = $config_factory;
    $this->keysHelper = $keys_helper;
  }

  /**
   * Retrieves a configuration object.
   *
   * This is the main entry point to the configuration API. Calling
   * @code $this->config('book.admin') @endcode will return a configuration
   * object in which the book module can store its administrative settings.
   *
   * @param string $name
   *   The name of the configuration object to retrieve. The name corresponds to
   *   a configuration file. For @code \Drupal::config('book.admin') @endcode,
   *   the config object returned will contain the contents of book.admin
   *   configuration file.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   A configuration object.
   */
  protected function config($name)
  {
    return $this
      ->configFactory()
      ->get($name);
  }

  /**
   * Gets the config factory for this form.
   *
   * When accessing configuration values, use $this->config(). Only use this
   * when the config factory needs to be manipulated directly.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected function configFactory()
  {
    if (!$this->configFactory) {
      $this->configFactory = $this
        ->container()
        ->get('config.factory');
    }
    return $this->configFactory;
  }


  /**
   * {@inheritdoc}
   */
  public function title()
  {
    return $this->pluginDefinition['title'];
  }

  abstract public function buildForm(array $form, FormStateInterface $form_state);
  abstract public function saveForm(array &$form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   */
  abstract public function getRequest($data);
}

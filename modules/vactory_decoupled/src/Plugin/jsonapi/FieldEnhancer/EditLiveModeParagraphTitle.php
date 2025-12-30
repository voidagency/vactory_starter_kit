<?php

namespace Drupal\vactory_decoupled\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\vactory_decoupled\EditLiveModeHelper;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Makes paragraph title editable in live mode.
 *
 * @ResourceFieldEnhancer(
 *   id = "edit_live_mode_paragraph_title",
 *   label = @Translation("Edit Live Mode Paragraph Title"),
 *   description = @Translation("Edit Live Mode Paragraph Title.")
 * )
 */
class EditLiveModeParagraphTitle extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * Edit Live Mode Helper.
   *
   * @var \Drupal\vactory_decoupled\EditLiveModeHelper
   */
  protected EditLiveModeHelper $editLiveModeHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    EditLiveModeHelper $editLiveModeHelper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->editLiveModeHelper = $editLiveModeHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('vactory_decoupled.edit_live_mode_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    $object = $context['field_item_object'];
    $entity = $object->getEntity();

    if (!$entity instanceof Paragraph) {
      return $data;
    }

    if ($entity->bundle() !== 'vactory_component') {
      return $data;
    }

    $liveModeAllowed = $this->editLiveModeHelper->checkAccess();
    if (!$liveModeAllowed) {
      return $data;
    }

    $id = "paragraph_title|{$entity->id()}";

    return "{LiveMode id=\"{$id}\"}{$data}{/LiveMode}";
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($value, Context $context) {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema() {
    return [
      'type' => 'string',
    ];
  }

}

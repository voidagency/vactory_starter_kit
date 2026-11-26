<?php

namespace Drupal\Tests\vactory_core\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * The attributes the profile configures for menu_link_attributes.
 *
 * Since menu_link_attributes 1.10, an attribute whose name is not a valid
 * HTML attribute name (underscores included) is no longer offered on the menu
 * link form, and the module settings form refuses to save it.
 *
 * @group vactory_core
 */
class MenuLinkAttributesConfigTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'link',
    'menu_link_content',
    'menu_link_attributes',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('menu_link_content');
    $this->installConfig(['system', 'menu_link_attributes']);
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Every attribute installed by the profile is offered on the menu link form.
   */
  public function testProfileAttributesOfferedOnMenuLinkForm(): void {
    $file = dirname(__DIR__, 5) . '/config/install/menu_link_attributes.config.yml';
    $attributes = Yaml::decode(file_get_contents($file))['attributes'];
    $this->assertNotEmpty($attributes);
    $this->config('menu_link_attributes.config')
      ->set('attributes', $attributes)
      ->save();

    $this->setUpCurrentUser([], ['use menu link attributes']);
    $link = MenuLinkContent::create([
      'title' => 'Test',
      'link' => ['uri' => 'internal:/'],
      'menu_name' => 'main',
    ]);
    $form = $this->container->get('entity.form_builder')->getForm($link);

    foreach (array_keys($attributes) as $name) {
      $this->assertArrayHasKey($name, $form['options']['attributes'], "Attribute \"$name\" is offered on the menu link form.");
    }
  }

}

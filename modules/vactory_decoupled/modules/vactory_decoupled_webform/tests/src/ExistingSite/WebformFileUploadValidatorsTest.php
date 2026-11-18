<?php

namespace Drupal\Tests\vactory_decoupled_webform\ExistingSite;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Upload validators of the decoupled webform file upload resource.
 *
 * @group vactory_decoupled_webform
 */
class WebformFileUploadValidatorsTest extends ExistingSiteBase {

  /**
   * Returns the validators the resource applies to a webform element.
   */
  protected function validatorsFor(array $element_definition): array {
    $webform = Webform::create([
      'id' => 'test_upload_' . strtolower($this->randomMachineName(8)),
      'title' => 'Upload test',
      'elements' => Yaml::encode(['attachment' => $element_definition]),
    ]);
    $webform->save();
    $this->markEntityForCleanup($webform);

    $resource = \Drupal::service('plugin.manager.rest')
      ->createInstance('vactory_decoupled_webform_rest_file_upload');
    $element = $webform->getElement('attachment');
    $element_plugin = \Drupal::service('plugin.manager.webform.element')
      ->createInstance($element['#type']);
    $submission = WebformSubmission::create(['webform_id' => $webform->id()]);
    $element_plugin->prepare($element, $submission);

    $method = new \ReflectionMethod($resource, 'getElementValidators');
    $method->setAccessible(TRUE);
    return $method->invoke($resource, $element);
  }

  /**
   * Without #file_extensions, webform's default extensions are enforced.
   */
  public function testDefaultExtensionsAreEnforced(): void {
    $validators = $this->validatorsFor([
      '#type' => 'managed_file',
      '#title' => 'Attachment',
    ]);
    $this->assertArrayHasKey('FileExtension', $validators);
    $extensions = explode(' ', $validators['FileExtension']['extensions']);
    $this->assertContains('pdf', $extensions);
    $this->assertNotContains('php', $extensions);
    $this->assertNotContains('exe', $extensions);
  }

  /**
   * Extensions set on the element are kept.
   */
  public function testElementExtensionsAreKept(): void {
    $validators = $this->validatorsFor([
      '#type' => 'managed_file',
      '#title' => 'Attachment',
      '#file_extensions' => 'csv',
    ]);
    $this->assertSame('csv', $validators['FileExtension']['extensions']);
  }

}

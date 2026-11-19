<?php

namespace Drupal\Tests\vactory_cloudinary\Kernel;

use Cloudinary\Api\Upload\UploadApi;
use Drupal\Component\Serialization\Yaml;
use Drupal\KernelTests\KernelTestBase;
use Drupal\cloudinary_stream_wrapper\StreamWrapper\CloudinaryStreamWrapper;
use Drupal\file\Entity\File;
use Drupal\file_entity\Form\FileEditForm as FileEntityEditForm;
use Drupal\vactory_cloudinary\Form\FileEditForm;
use Drupal\vactory_cloudinary\Services\VactoryCloudinaryManager;
use Drupal\vactory_cloudinary\StreamWrapper\CloudinaryStreamWrapperOverride;

/**
 * Our use of voidagency/cloudinary (fork of drupal/cloudinary) and its SDK.
 *
 * The vactory_cloudinary module decorates the cloudinary:// stream wrapper,
 * builds URLs and calls the upload API with cloudinary/cloudinary_php, and
 * overrides the file_entity edit form. No call reaches the Cloudinary API:
 * only URIs with a fixed version ("/v1/") are resolved, and no cloudinary://
 * file is saved. A Kernel test, since
 * installing the cloudinary modules on a site leaves media and block types
 * behind. Run it on a candidate version of the fork or of the SDK before
 * upgrading them.
 *
 * @group vactory_cloudinary
 * @group dependencies
 */
class CloudinaryIntegrationTest extends KernelTestBase {

  /**
   * Expected URL of the test images.
   */
  const URL = 'https://res.cloudinary.com/vactory-test/image/upload/c_fill,w_100/v1/vactory-test/image.jpg';

  /**
   * {@inheritdoc}
   *
   * Intentionally minimal: a Kernel test does not resolve info.yml
   * dependencies, testDependencies() checks them.
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'text',
    'filter',
    'serialization',
    'views',
    'token',
    'file_entity',
    'cloudinary_sdk',
    'cloudinary_stream_wrapper',
    'vactory_cloudinary',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->config('cloudinary_sdk.settings')
      ->set('cloudinary_sdk_cloud_name', 'vactory-test')
      ->set('cloudinary_sdk_api_key', '123456789012345')
      ->set('cloudinary_sdk_api_secret', 'not-a-secret')
      ->save();
    // cloudinary_sdk configures the SDK on each request (InitSubscriber).
    $this->assertTrue(cloudinary_sdk_init());
  }

  /**
   * Our decorator handles cloudinary:// URIs with raw transformations.
   */
  public function testStreamWrapperDecorator(): void {
    $uri = 'cloudinary://c_fill,w_100/v1/vactory-test/image.jpg';
    $wrapper = $this->container->get('stream_wrapper_manager')->getViaUri($uri);
    $this->assertInstanceOf(CloudinaryStreamWrapperOverride::class, $wrapper);
    $this->assertSame(self::URL, $wrapper->getExternalUrl());
    $this->assertSame(self::URL, $this->container->get('file_url_generator')->generateAbsoluteString($uri));
    // URIs without a fixed version go through loadResource() (API call, not
    // run here): the parent methods we call must keep their signature.
    foreach (['getTarget' => 0, 'loadResource' => 1] as $method => $required) {
      $reflection = new \ReflectionMethod(CloudinaryStreamWrapper::class, $method);
      $this->assertSame($required, $reflection->getNumberOfRequiredParameters(), $method);
    }
  }

  /**
   * The modules declared as dependencies exist.
   */
  public function testDependencies(): void {
    $info = $this->container->get('extension.list.module')->getExtensionInfo('vactory_cloudinary');
    $list = $this->container->get('extension.list.module');
    foreach ($info['dependencies'] as $dependency) {
      $module = explode(':', $dependency)[1] ?? $dependency;
      $this->assertTrue($list->exists($module), $dependency);
    }
  }

  /**
   * The manager builds the upload API client and public ids.
   */
  public function testManager(): void {
    $manager = $this->container->get('vactory_cloudinary.manager');
    $this->assertInstanceOf(VactoryCloudinaryManager::class, $manager);
    $this->assertSame('folder/image', $manager->getPublicId('cloudinary://folder/image.JPG'));
    $this->assertSame('folder/doc.pdf', $manager->getPublicId('cloudinary:///folder/doc.pdf'));
    $this->assertSame(['public_id' => 'folder/image'], $manager->getBasicOptions('cloudinary://folder/image.png'));
    // The SDK accepts our flat credentials array.
    $upload_api = (new \ReflectionProperty($manager, 'uploadApi'))->getValue($manager);
    $this->assertInstanceOf(UploadApi::class, $upload_api);
    $cloud = (new \ReflectionProperty($upload_api, 'apiClient'))->getValue($upload_api)->getCloud();
    $this->assertSame('vactory-test', $cloud->cloudName);
    $this->assertSame('123456789012345', $cloud->apiKey);
    // Calls made by the manager.
    $this->assertTrue(method_exists($upload_api, 'explicit'));
    $this->assertTrue(method_exists($upload_api, 'upload'));
    // Every service declared by the module can be built.
    foreach (array_keys(Yaml::decode(file_get_contents(__DIR__ . '/../../../vactory_cloudinary.services.yml'))['services']) as $id) {
      if ($id !== 'cloudinary_stream_wrapper.override') {
        $this->assertIsObject($this->container->get($id), $id);
      }
    }
  }

  /**
   * Our file edit form still extends the file_entity one.
   */
  public function testFileEditForm(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $class = $entity_type_manager->getDefinition('file')->getFormClass('edit');
    $this->assertSame(FileEditForm::class, ltrim($class, '\\'));
    $this->assertTrue(is_subclass_of($class, FileEntityEditForm::class));
    $form = $entity_type_manager->getFormObject('file', 'edit')
      ->setEntity(File::create(['uri' => 'public://vactory-test.txt']));
    $this->assertInstanceOf(FileEditForm::class, $form);
  }

  /**
   * The text filter replaces Drupal file URLs with Cloudinary URLs.
   */
  public function testReplaceFilter(): void {
    // Saving a cloudinary:// file makes file_entity read its metadata through
    // the API: the URI is set in the database instead.
    $file = File::create([
      'uri' => 'public://image.jpg',
      'filename' => 'image.jpg',
      'status' => 1,
    ]);
    $file->save();
    $this->container->get('database')->update('file_managed')
      ->fields(['uri' => 'cloudinary://c_fill,w_100/v1/vactory-test/image.jpg'])
      ->condition('fid', $file->id())
      ->execute();
    $this->container->get('entity_type.manager')->getStorage('file')->resetCache();
    $filter = $this->container->get('plugin.manager.filter')->createInstance('vactory_cloudinary_file_replace', [
      'settings' => ['replace_policy' => 'drupal_to_cloudinary'],
    ]);
    $text = $filter->process('<p><img src="/sites/default/files/vactory-test/image.jpg" width="10" height="10"></p>', 'en')->getProcessedText();
    $this->assertStringContainsString('src="' . self::URL . '"', $text);
    $this->assertStringNotContainsString('width=', $text);
  }

}

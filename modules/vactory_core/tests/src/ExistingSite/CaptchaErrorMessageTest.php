<?php

namespace Drupal\Tests\vactory_core\ExistingSite;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Site\Settings;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * The captcha wrong answer message is translated without a profile patch.
 *
 * The profile used to patch captcha to hard-code a translated message. The
 * module translates its default message itself when the setting
 * "wrong_captcha_response_message" is empty: the profile now empties the
 * setting (update hook, install configuration, settings form).
 *
 * @group vactory_core
 * @group dependencies
 */
class CaptchaErrorMessageTest extends ExistingSiteBase {

  /**
   * Source string of the default message in the captcha module.
   */
  const DEFAULT_MESSAGE = 'The answer you entered for the CAPTCHA was not correct.';

  /**
   * French translation set by this test.
   */
  const FRENCH_MESSAGE = 'Réponse au CAPTCHA incorrecte (test).';

  /**
   * Arabic translation set by this test.
   */
  const ARABIC_MESSAGE = 'إجابة CAPTCHA غير صحيحة (test).';

  /**
   * Message of the site before the test.
   *
   * @var mixed
   */
  protected $originalMessage;

  /**
   * Settings before the test.
   *
   * @var array
   */
  protected $originalSettings;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->originalMessage = $this->config()->get('wrong_captcha_response_message');
    $this->originalSettings = Settings::getAll();

    // Known translations, whatever the interface translations of the site.
    new Settings([
      'locale_custom_strings_fr' => ['' => [self::DEFAULT_MESSAGE => self::FRENCH_MESSAGE]],
      'locale_custom_strings_ar' => ['' => [self::DEFAULT_MESSAGE => self::ARABIC_MESSAGE]],
    ] + $this->originalSettings);
    \Drupal::service('string_translator.custom_strings')->reset();

    $module_path = \Drupal::service('extension.list.module')->getPath('vactory_core');
    require_once DRUPAL_ROOT . '/' . $module_path . '/vactory_core.install';
    \Drupal::moduleHandler()->loadInclude('captcha', 'inc');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->config()->set('wrong_captcha_response_message', $this->originalMessage)->save();
    new Settings($this->originalSettings);
    \Drupal::service('string_translator.custom_strings')->reset();
    \Drupal::translation()->setDefaultLangcode(\Drupal::languageManager()->getCurrentLanguage()->getId());
    parent::tearDown();
  }

  /**
   * Editable captcha settings.
   */
  protected function config() {
    return \Drupal::configFactory()->getEditable('captcha.settings');
  }

  /**
   * Stores a message, runs the update and returns the stored message.
   */
  protected function runUpdate(string $message): string {
    $this->config()->set('wrong_captcha_response_message', $message)->save();
    vactory_core_update_10001();
    return (string) $this->config()->get('wrong_captcha_response_message');
  }

  /**
   * Message shown to a visitor browsing in the given language.
   */
  protected function errorMessageIn(string $langcode): string {
    \Drupal::translation()->setDefaultLangcode($langcode);
    return (string) _captcha_get_error_message();
  }

  /**
   * After the update, the default message is translated in fr and ar.
   */
  public function testDefaultMessageIsTranslatedAfterUpdate(): void {
    $this->assertSame('', $this->runUpdate(self::DEFAULT_MESSAGE));

    $this->assertSame(self::FRENCH_MESSAGE, $this->errorMessageIn('fr'));
    $this->assertSame(self::ARABIC_MESSAGE, $this->errorMessageIn('ar'));
    $this->assertSame(self::DEFAULT_MESSAGE, $this->errorMessageIn('en'));
  }

  /**
   * A default message stored in a site language is emptied as well.
   *
   * With the patch, saving the settings form stored the default message in
   * the language of the administrator.
   */
  public function testTranslatedDefaultMessageIsEmptied(): void {
    $langcodes = array_keys(\Drupal::languageManager()->getLanguages());
    if (!in_array('fr', $langcodes, TRUE)) {
      $this->markTestSkipped('French is not a language of the site.');
    }
    $this->assertSame('', $this->runUpdate(self::FRENCH_MESSAGE));
    $this->assertSame('', $this->runUpdate('  ' . self::DEFAULT_MESSAGE . ' '));
  }

  /**
   * A customised message is left untouched by the update.
   */
  public function testCustomisedMessageIsKept(): void {
    $custom = 'Wrong answer, please try again (site message).';
    $this->assertSame($custom, $this->runUpdate($custom));
  }

  /**
   * An empty message stays empty, and the update can run twice.
   */
  public function testUpdateIsIdempotent(): void {
    $this->assertSame('', $this->runUpdate(''));
    $this->assertSame('', $this->runUpdate(self::DEFAULT_MESSAGE));
    vactory_core_update_10001();
    $this->assertSame('', (string) $this->config()->get('wrong_captcha_response_message'));
  }

  /**
   * The settings form accepts an empty message and does not prefill it.
   */
  public function testSettingsFormKeepsMessageEmpty(): void {
    $this->config()->set('wrong_captcha_response_message', self::DEFAULT_MESSAGE)->save();
    $form = \Drupal::formBuilder()->getForm('Drupal\captcha\Form\CaptchaSettingsForm');
    $this->assertFalse($form['wrong_captcha_response_message']['#required']);
    $this->assertSame('', $form['wrong_captcha_response_message']['#default_value']);

    $custom = 'Wrong answer, please try again (site message).';
    $this->config()->set('wrong_captcha_response_message', $custom)->save();
    $form = \Drupal::formBuilder()->getForm('Drupal\captcha\Form\CaptchaSettingsForm');
    $this->assertSame($custom, $form['wrong_captcha_response_message']['#default_value']);
  }

  /**
   * New sites are installed with an empty message.
   *
   * The profile configuration replaces the one of the module at install: it
   * must stay a copy of it, except for the message.
   */
  public function testInstallConfiguration(): void {
    $profile_path = \Drupal::service('extension.list.profile')->getPath('vactory_starter_kit');
    $captcha_path = \Drupal::service('extension.list.module')->getPath('captcha');
    $shipped = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/' . $profile_path . '/config/install/captcha.settings.yml'));
    $upstream = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/' . $captcha_path . '/config/install/captcha.settings.yml'));

    $this->assertSame('', $shipped['wrong_captcha_response_message']);
    $this->assertSame(self::DEFAULT_MESSAGE, $upstream['wrong_captcha_response_message']);
    unset($shipped['wrong_captcha_response_message'], $upstream['wrong_captcha_response_message']);
    $this->assertSame($upstream, $shipped);
  }

  /**
   * The profile no longer patches captcha.
   */
  public function testNoCaptchaPatch(): void {
    $profile_path = \Drupal::service('extension.list.profile')->getPath('vactory_starter_kit');
    $composer = json_decode(file_get_contents(DRUPAL_ROOT . '/' . $profile_path . '/composer.json'), TRUE, 512, JSON_THROW_ON_ERROR);
    $this->assertArrayNotHasKey('drupal/captcha', $composer['extra']['patches'] ?? []);
  }

}

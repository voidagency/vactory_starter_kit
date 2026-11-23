<?php

namespace Drupal\Tests\vactory_quiz_certificat\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the certificate link of the notification mail (Redmine #71440).
 *
 * The default mail body used "[quiz:certificat_url]", a token the module did
 * not answer: the link was not replaced. Sites keep that body in their
 * settings, so both names give the certificate URL.
 *
 * @group vactory_quiz_certificat
 */
class CertificatMailTokenTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'vactory_quiz_certificat',
  ];

  /**
   * The schema of the module settings does not describe every key.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * URL of the certificate given to the mail.
   */
  protected const URL = 'https://example.com/system/files/quiz_certificat/2026-11/a.pdf';

  /**
   * Builds the notification mail with the given body in the settings.
   *
   * @param string $body
   *   The mail body saved in the module settings.
   *
   * @return string
   *   The body of the mail.
   */
  protected function mailBody(string $body): string {
    $this->config('vactory_quiz_certificat.settings')
      ->set('email_subject', 'Your certificat is ready!')
      ->set('email_body', ['value' => $body, 'format' => 'basic_html'])
      ->save();
    $message = ['subject' => '', 'body' => []];
    $params = [
      'tokens' => [
        'quiz_certificat_url' => self::URL,
        'quiz_title' => 'Quiz',
      ],
    ];
    $this->container->get('module_handler')->loadInclude('vactory_quiz_certificat', 'inc', 'vactory_quiz_certificat.tokens');
    vactory_quiz_certificat_mail('quiz_certificate_mail', $message, $params);
    return (string) ($message['body'][0] ?? '');
  }

  /**
   * The declared token is replaced by the certificate URL.
   */
  public function testDeclaredTokenGivesTheUrl(): void {
    $body = $this->mailBody('<p>Download: [quiz:certificate_url]</p>');
    $this->assertSame('<p>Download: ' . self::URL . '</p>', $body);
  }

  /**
   * The name saved on existing sites is replaced by the certificate URL too.
   */
  public function testFormerTokenNameGivesTheUrl(): void {
    $body = $this->mailBody('<p>Download: [quiz:certificat_url]</p>');
    $this->assertSame('<p>Download: ' . self::URL . '</p>', $body);
  }

  /**
   * The mail body shipped with the module only uses declared tokens.
   */
  public function testShippedMailBodyUsesDeclaredTokens(): void {
    $path = $this->container->get('extension.list.module')->getPath('vactory_quiz_certificat');
    $settings = Yaml::decode(file_get_contents($path . '/config/install/vactory_quiz_certificat.settings.yml'));
    $shipped = $settings['email_body']['value'];

    $this->container->get('module_handler')->loadInclude('vactory_quiz_certificat', 'inc', 'vactory_quiz_certificat.tokens');
    $declared = array_keys(vactory_quiz_certificat_token_info()['tokens']['quiz']);
    $used = $this->container->get('token')->scan($shipped)['quiz'] ?? [];
    $this->assertNotEmpty($used);
    $this->assertSame([], array_diff(array_keys($used), $declared));

    $body = $this->mailBody($shipped);
    $this->assertStringContainsString(self::URL, $body);
    $this->assertStringNotContainsString('[quiz:', $body);
  }

}

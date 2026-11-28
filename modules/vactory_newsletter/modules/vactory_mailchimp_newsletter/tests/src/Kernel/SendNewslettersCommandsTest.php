<?php

namespace Drupal\Tests\vactory_mailchimp_newsletter\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\vactory_mailchimp_newsletter\Drush\Commands\SendNewslettersCommands;
use Drupal\vactory_mailchimp_newsletter\Services\CampaignsService;

/**
 * Tests the send_newsletters Drush command.
 *
 * @coversDefaultClass \Drupal\vactory_mailchimp_newsletter\Drush\Commands\SendNewslettersCommands
 * @group vactory_mailchimp_newsletter
 */
class SendNewslettersCommandsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'datetime',
    'options',
    'vactory_mailchimp_newsletter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    NodeType::create(['type' => 'newsletter', 'name' => 'Newsletter'])->save();
    $fields = [
      'field_vactory_datetime' => 'datetime',
      'field_recurrence' => 'string',
    ];
    foreach ($fields as $name => $type) {
      FieldStorageConfig::create([
        'field_name' => $name,
        'entity_type' => 'node',
        'type' => $type,
      ])->save();
      FieldConfig::create([
        'field_name' => $name,
        'entity_type' => 'node',
        'bundle' => 'newsletter',
      ])->save();
    }
  }

  /**
   * A due newsletter is sent then unpublished.
   *
   * @covers ::sendNewsletters
   */
  public function testSendDueNewsletter(): void {
    $node = Node::create([
      'type' => 'newsletter',
      'title' => 'Due newsletter',
      'status' => 1,
      'field_vactory_datetime' => '2020-01-01T10:00:00',
    ]);
    $node->save();

    $campaigns = $this->createMock(CampaignsService::class);
    $campaigns->method('mailchimpGetInfos')->willReturn(['list_id' => 'list-1']);
    $campaigns->expects($this->once())->method('mailchimpSendNewsletter')
      ->with($this->callback(fn ($sent) => (int) $sent->id() === (int) $node->id()), 'list-1');
    $this->container->set('vactory_mailchimp_newsletter.campaigns.manage', $campaigns);

    SendNewslettersCommands::create($this->container)->sendNewsletters();

    $this->assertFalse(Node::load($node->id())->isPublished());
  }

  /**
   * The deprecated service still builds the command, until 3.5.
   *
   * @group legacy
   */
  public function testDeprecatedService(): void {
    $this->expectDeprecation('The "newsletters_send.commands" service is deprecated in vactory_starter_kit:3.4.0 and is removed from vactory_starter_kit:3.5.0. Run "drush send_newsletters" instead.');
    $this->assertInstanceOf(SendNewslettersCommands::class, $this->container->get('newsletters_send.commands'));
  }

}

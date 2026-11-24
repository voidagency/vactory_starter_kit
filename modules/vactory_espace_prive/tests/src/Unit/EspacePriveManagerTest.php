<?php

namespace Drupal\Tests\vactory_espace_prive\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Drupal\vactory_espace_prive\Services\EspacePriveManager;

/**
 * Characterization of the espace prive manager.
 *
 * @coversDefaultClass \Drupal\vactory_espace_prive\Services\EspacePriveManager
 * @group vactory_espace_prive
 */
class EspacePriveManagerTest extends UnitTestCase {

  /**
   * Request time used by the tests (2024-01-31T00:00:00 UTC).
   */
  const NOW = 1706659200;

  /**
   * Builds the manager.
   *
   * @param array $settings
   *   Values of vactory_espace_prive.settings.
   * @param array $users
   *   Users returned for the webmaster role.
   * @param array|null $load_properties
   *   Receives the properties given to loadByProperties().
   */
  protected function manager(array $settings = [], array $users = [], ?array &$load_properties = NULL): EspacePriveManager {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturnCallback(function (array $properties) use ($users, &$load_properties) {
      $load_properties = $properties;
      return $users;
    });
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('user')->willReturn($storage);
    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(self::NOW);
    return new EspacePriveManager(
      $entity_type_manager,
      $this->getConfigFactoryStub(['vactory_espace_prive.settings' => $settings]),
      $time
    );
  }

  /**
   * Calls a protected method.
   */
  protected function call(EspacePriveManager $manager, string $method, ...$args) {
    $reflection = new \ReflectionMethod($manager, $method);
    $reflection->setAccessible(TRUE);
    return $reflection->invoke($manager, ...$args);
  }

  /**
   * Builds a user mock with a field_reset_password_date value.
   *
   * @param mixed $value
   *   Stored value.
   * @param bool $has_field
   *   Whether the user has the field.
   */
  protected function user($value, bool $has_field = TRUE) {
    $user = $this->createMock(UserInterface::class);
    $user->method('hasField')->with('field_reset_password_date')->willReturn($has_field);
    $item = new \stdClass();
    $item->value = $value;
    $user->method('get')->with('field_reset_password_date')->willReturn($item);
    return $user;
  }

  /**
   * Values of field_reset_password_date and the timestamp read from them.
   */
  public static function providerTimestamps(): array {
    return [
      'null' => [NULL, NULL],
      'empty string' => ['', NULL],
      'legacy numeric string' => ['1700000000', 1700000000],
      'legacy integer' => [1700000000, 1700000000],
      'zero string' => ['0', 0],
      'datetime storage (UTC)' => ['2024-01-02T03:04:05', 1704164645],
      'epoch' => ['1970-01-01T00:00:00', 0],
      'out of range parts overflow' => ['2024-02-30T00:00:00', 1709251200],
      'date only' => ['2024-01-02', NULL],
      'with timezone suffix' => ['2024-01-02T03:04:05+00:00', NULL],
      'space separator' => ['2024-01-02 03:04:05', NULL],
      'garbage' => ['not a date', NULL],
      'negative number' => ['-5', NULL],
    ];
  }

  /**
   * Timestamp for a parsable value, NULL otherwise.
   *
   * @covers ::getLastResetPasswordTimestamp
   * @dataProvider providerTimestamps
   */
  public function testGetLastResetPasswordTimestamp($value, ?int $expected): void {
    $result = $this->call($this->manager(), 'getLastResetPasswordTimestamp', $this->user($value));
    $this->assertSame($expected, $result);
  }

  /**
   * The timestamp does not depend on the PHP default timezone.
   *
   * @covers ::getLastResetPasswordTimestamp
   * @covers ::formatDatetimeStorageValue
   */
  public function testTimezoneIndependent(): void {
    $previous = date_default_timezone_get();
    date_default_timezone_set('Africa/Casablanca');
    try {
      $manager = $this->manager();
      $this->assertSame(1704164645, $this->call($manager, 'getLastResetPasswordTimestamp', $this->user('2024-01-02T03:04:05')));
      $this->assertSame('2024-01-02T03:04:05', $this->call($manager, 'formatDatetimeStorageValue', 1704164645));
    }
    finally {
      date_default_timezone_set($previous);
    }
  }

  /**
   * Timestamps formatted for datetime storage, in UTC.
   *
   * @covers ::formatDatetimeStorageValue
   */
  public function testFormatDatetimeStorageValue(): void {
    $manager = $this->manager();
    $this->assertSame('1970-01-01T00:00:00', $this->call($manager, 'formatDatetimeStorageValue', 0));
    $this->assertSame('2024-01-31T00:00:00', $this->call($manager, 'formatDatetimeStorageValue', self::NOW));
    // Round trip.
    $this->assertSame(self::NOW, $this->call($manager, 'getLastResetPasswordTimestamp', $this->user($this->call($manager, 'formatDatetimeStorageValue', self::NOW))));
  }

  /**
   * Black listed domains.
   *
   * @covers ::validateEmail
   */
  public function testValidateEmail(): void {
    $this->assertTrue($this->manager()->validateEmail('user@spam.test'));
    $this->assertTrue($this->manager(['domain_black_list' => ''])->validateEmail('user@spam.test'));

    $manager = $this->manager(['domain_black_list' => 'spam.test;junk.test']);
    $this->assertFalse($manager->validateEmail('user@spam.test'));
    $this->assertFalse($manager->validateEmail('user@junk.test'));
    $this->assertTrue($manager->validateEmail('user@example.test'));
    // Exact, case-sensitive match on the whole domain.
    $this->assertTrue($manager->validateEmail('user@SPAM.test'));
    $this->assertTrue($manager->validateEmail('user@sub.spam.test'));
    // Only the part between the first and the second "@" is compared.
    $this->assertFalse($manager->validateEmail('a@spam.test@example.test'));

    // Entries are not trimmed.
    $manager = $this->manager(['domain_black_list' => 'spam.test; junk.test']);
    $this->assertTrue($manager->validateEmail('user@junk.test'));
  }

  /**
   * An address without "@" raises a warning (undefined array key).
   *
   * @covers ::validateEmail
   */
  public function testValidateEmailWithoutAt(): void {
    $manager = $this->manager(['domain_black_list' => 'spam.test']);
    $this->expectWarning();
    $this->expectWarningMessage('Undefined array key 1');
    $manager->validateEmail('no-at-sign');
  }

  /**
   * No password lifetime: nothing is changed.
   *
   * @covers ::resetWebmastersPasswords
   */
  public function testResetWithoutLifetime(): void {
    foreach ([[], ['password_lifetime' => 0], ['password_lifetime' => '']] as $settings) {
      $user = $this->user('1970-01-01T00:00:00');
      $user->expects($this->never())->method('save');
      $user->expects($this->never())->method('setPassword');
      $user->expects($this->never())->method('set');
      $properties = NULL;
      $this->manager($settings, [$user], $properties)->resetWebmastersPasswords();
      // Users are loaded before the setting is read.
      $this->assertSame(['roles' => 'webmaster'], $properties);
    }
  }

  /**
   * Password reset according to the last reset date.
   *
   * @covers ::resetWebmastersPasswords
   */
  public function testResetWebmastersPasswords(): void {
    $day = 86400;
    $now_storage = '2024-01-31T00:00:00';

    // No field: skipped.
    $no_field = $this->user(NULL, FALSE);
    $no_field->expects($this->never())->method('save');
    $no_field->expects($this->never())->method('set');

    // No date yet: the date is initialized, the password is kept.
    $no_date = $this->user(NULL);
    $no_date->expects($this->never())->method('setPassword');
    $no_date->expects($this->once())->method('set')->with('field_reset_password_date', $now_storage);
    $no_date->expects($this->once())->method('save');

    // Unparsable date: same as no date.
    $garbage = $this->user('garbage');
    $garbage->expects($this->never())->method('setPassword');
    $garbage->expects($this->once())->method('set')->with('field_reset_password_date', $now_storage);
    $garbage->expects($this->once())->method('save');

    // Exactly the lifetime: reset.
    $expired = $this->user(gmdate('Y-m-d\TH:i:s', self::NOW - 30 * $day));
    $expired->expects($this->once())->method('setPassword')->with('reSet' . self::NOW);
    $expired->expects($this->once())->method('set')->with('field_reset_password_date', $now_storage);
    $expired->expects($this->once())->method('save');

    // Legacy numeric value, long expired: reset.
    $legacy = $this->user((string) (self::NOW - 365 * $day));
    $legacy->expects($this->once())->method('setPassword')->with('reSet' . self::NOW);
    $legacy->expects($this->once())->method('save');

    // One second short of the lifetime: kept.
    $recent = $this->user(gmdate('Y-m-d\TH:i:s', self::NOW - 30 * $day + 1));
    $recent->expects($this->never())->method('setPassword');
    $recent->expects($this->never())->method('set');
    $recent->expects($this->never())->method('save');

    // Date in the future: kept.
    $future = $this->user(gmdate('Y-m-d\TH:i:s', self::NOW + $day));
    $future->expects($this->never())->method('save');

    $this->manager(
      ['password_lifetime' => '30'],
      [$no_field, $no_date, $garbage, $expired, $legacy, $recent, $future]
    )->resetWebmastersPasswords();
  }

}

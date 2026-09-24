<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel\Traits;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Logger\RfcLogLevel;
use PHPUnit\Framework\Assert;

/**
 * Helper to assert dblog messages.
 */
class DbLogTestReader {

  /**
   * Map of dblog ids marked as read.
   *
   * This is ArrayObject, not array, so that it will be shared between cloned
   * instances.
   *
   * @var \ArrayObject<int, true>
   */
  protected \ArrayObject $readIdsMap;

  /**
   * A callback to create a select query on 'watchdog' table.
   *
   * @var \Closure(): \Drupal\Core\Database\Query\SelectInterface
   */
  protected \Closure $select;

  public function __construct() {
    $this->readIdsMap = new \ArrayObject();
    $this->select = function () {
      return \Drupal::database()->select('watchdog')->orderBy('wid');
    };
  }

  /**
   * Asserts no failures, and marks all as read.
   */
  public function sweep(): void {
    $this->assertNoUnreadFailures();
    $this->withNonFailuresOnly()->markAllAsRead();
  }

  /**
   * Reads the next unread failure message, and fails if none found.
   *
   * @return array
   *   Next unread failure message.
   */
  public function assertReadNextUnreadFailure(): array {
    return $this->withFailuresOnly()->assertReadNextUnread();
  }

  /**
   * Reads the next unread matching message, and fails if none found.
   *
   * @return array
   *   The next unread message matching the current filter.
   */
  public function assertReadNextUnread(): array {
    $record = $this->readNextUnread();
    if (!$record) {
      Assert::fail('No dblog record was found that matches the query conditions.');
    }
    return $record;
  }

  /**
   * Reads the next unread message.
   *
   * @return array|null
   *   The next unread message.
   */
  public function readNextUnread(): ?array {
    $record = $this->withUnreadOnly()->fetchRecord();
    if (!$record) {
      return NULL;
    }
    $this->readIdsMap[$record['wid']] = TRUE;
    return $record;
  }

  /**
   * Asserts that no unread failure messages exist.
   *
   * @param bool $mark_all_read
   *   TRUE to mark all unread failures as read, before throwing the failure.
   *
   * @return $this
   */
  public function assertNoUnreadFailures(bool $mark_all_read = TRUE): static {
    $this->withFailuresOnly()
      ->assertNoUnread($mark_all_read);
    return $this;
  }

  /**
   * Asserts no unread log messages that match the current filter.
   *
   * @param bool $mark_all_read
   *   TRUE to mark all messages as read before throwing the failure.
   *
   * @return $this
   */
  public function assertNoUnread(bool $mark_all_read = TRUE): static {
    $instance = $this->withUnreadOnly();
    $record = $instance->fetchRecord();
    if (!$record) {
      return $this;
    }
    $instance->markAllAsRead();
    $this->failWithUnexpectedRecord($record);
  }

  /**
   * Asserts that no records match the current filter.
   *
   * @return $this
   */
  public function assertEmpty(): static {
    $record = $this->fetchRecord();
    if ($record) {
      $this->failWithUnexpectedRecord($record);
    }
    return $this;
  }

  /**
   * Throws a failure for an unexpected record.
   *
   * @param array $record
   *   The unexpected record.
   */
  protected function failWithUnexpectedRecord(array $record): never {
    $message = (string) new FormattableMarkup($record['message'], $record['variables']);
    $message = html_entity_decode(strip_tags($message));
    Assert::fail(sprintf(
      "Found an unexpected log message:\n\n%s",
      Yaml::encode([
        'severity' => (string) (RfcLogLevel::getLevels()[$record['severity']] ?? $record['severity']),
        'type' => $record['type'],
        'message' => $message,
      ] + $record),
    ));
  }

  /**
   * Fetches the first record that matches the conditions.
   *
   * @return array|null
   *   The record, or NULL if none found.
   */
  protected function fetchRecord(): ?array {
    $query = ($this->select)()->fields('watchdog');
    $record = $query->execute()->fetchAssoc();
    if (!$record) {
      return NULL;
    }
    $record['variables'] = match ($record['variables']) {
      '', NULL => [],
      default => unserialize($record['variables']),
    };
    return $record;
  }

  /**
   * Marks all as read that match the current filter.
   *
   * @return int
   *   The number of records that were marked as read.
   */
  public function markAllAsRead(): int {
    $ids = $this->fetchIds();
    foreach ($ids as $id) {
      $this->readIdsMap[$id] = TRUE;
    }
    return count($ids);
  }

  /**
   * Fetches ids of matching records.
   *
   * @return list<int>
   *   Ids of matching records.
   */
  protected function fetchIds(): array {
    $query = ($this->select)()->fields('watchdog', ['wid']);
    return $query->execute()->fetchCol();
  }

  /**
   * Immutable setter. Constrains the filter to only unread records.
   *
   * @return static
   *   Modified clone with constrained filter.
   */
  public function withUnreadOnly(): static {
    $clone = clone $this;
    $clone->select = function () {
      $query = ($this->select)();
      $read_ids = array_keys($this->readIdsMap->getArrayCopy());
      if ($read_ids) {
        $query->condition('wid', $read_ids, 'NOT IN');
      }
      return $query;
    };
    return $clone;
  }

  /**
   * Immutable setter. Constrains the filter to only failure records.
   *
   * @return static
   *   Modified clone with constrained filter.
   */
  public function withFailuresOnly(): static {
    return $this->withCondition('severity', RfcLogLevel::INFO, '<');
  }

  /**
   * Immutable setter. Constrains the filter to only non-failure records.
   *
   * @return static
   *   Modified clone with constrained filter.
   */
  public function withNonFailuresOnly(): static {
    return $this->withCondition('severity', RfcLogLevel::INFO, '>=');
  }

  /**
   * Immutable setter. Constrains the filter with a query condition.
   *
   * @param string $field
   *   A database field name.
   * @param string|int|array $value
   *   An expected value to filter by.
   * @param string $operator
   *   A comparison operator for the query.
   *
   * @return static
   *   Modified clone with constrained filter.
   */
  public function withCondition(string $field, string|int|array $value, string $operator = '='): static {
    $clone = clone $this;
    $clone->select = fn () => ($this->select)()->condition($field, $value, $operator);
    return $clone;
  }

}

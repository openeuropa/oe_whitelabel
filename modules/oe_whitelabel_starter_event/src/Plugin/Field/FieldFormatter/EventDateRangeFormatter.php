<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_starter_event\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeFormatterBase;
use Drupal\datetime_range\DateTimeRangeTrait;

/**
 * Plugin implementation of the 'Event dates' formatter for 'daterange' fields.
 *
 * This formatter renders the data range as plain text, with a fully
 * configurable date format using the PHP date syntax and separator.
 *
 * @FieldFormatter(
 *   id = "event_date_range_format",
 *   label = @Translation("Event date range format"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class EventDateRangeFormatter extends DateTimeFormatterBase {

  use DateTimeRangeTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (empty($item->start_date) && empty($item->end_date)) {
        continue;
      }

      /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
      $start_date = $item->start_date;
      /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
      $end_date = $item->end_date;

      // Event on a single day.
      if ($start_date->format('Y-m-d') === $end_date->format('Y-m-d')) {
        $elements[$delta] = [
          'start_date' => $this->buildCustomDate($start_date, 'l d F Y, H.i'),
          'separator' => ['#plain_text' => '-'],
          'end_date' => $this->buildCustomDate($end_date, 'H.i (T)'),
        ];
      }
      // Event on multiple days.
      else {
        $elements[$delta] = [
          'start_date' => $this->buildCustomDate($start_date, 'l d F Y, H.i'),
          'separator' => ['#plain_text' => '-'],
          'end_date' => $this->buildCustomDate($end_date, 'l d F Y, H.i (T)'),
        ];
      }
    }

    return $elements;
  }

  /**
   * Prepare render for a date with custom format.
   */
  protected function buildCustomDate(DrupalDateTime $date, $format) {
    $this->setTimeZone($date);

    $build = [
      '#markup' => $this->dateFormatter->format($date->getTimestamp(), 'custom', $format),
      '#cache' => [
        'contexts' => [
          'timezone',
        ],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatDate($date) {
    $format_type = $this->getSetting('format_type');
    $timezone = $this->getSetting('timezone_override') ?: $date->getTimezone()->getName();
    return $this->dateFormatter->format($date->getTimestamp(), $format_type, '', $timezone != '' ? $timezone : NULL);
  }

}

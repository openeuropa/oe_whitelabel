<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_starter_event\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
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
  public static function defaultSettings() {
    return [
      'time_format' => 'H.i',
      'datetime_format' => 'l d F Y, H.i',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['time_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time format'),
      '#description' => $this->t('See <a href="https://www.php.net/manual/datetime.format.php#refsect1-datetime.format-parameters" target="_blank">the documentation for PHP date formats</a>.'),
      '#default_value' => $this->getSetting('time_format'),
    ];

    $form['datetime_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date/time format'),
      '#description' => $this->t('See <a href="https://www.php.net/manual/datetime.format.php#refsect1-datetime.format-parameters" target="_blank">the documentation for PHP date formats</a>.'),
      '#default_value' => $this->getSetting('datetime_format'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $date = new DrupalDateTime();
    $this->setTimeZone($date);
    $summary[] = 'Time format: ' . $date->format($this->getSetting('time_format'), $this->getFormatSettings());
    $summary[] = 'Datetime format: ' . $date->format($this->getSetting('datetime_format'), $this->getFormatSettings());

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (empty($item->start_date) || empty($item->end_date)) {
        continue;
      }

      /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
      $start_date = $item->start_date;
      /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
      $end_date = $item->end_date;

      $this->setTimeZone($start_date);
      $this->setTimeZone($end_date);

      if ($start_date->format('Y-m-d') === $end_date->format('Y-m-d')) {
        // The event is on a single day with time.
        $elements[$delta] = [
          'start_date' => $this->buildCustomDate($start_date, $this->getSetting('datetime_format')),
          'separator' => ['#plain_text' => '-'],
          'end_date' => $this->buildCustomDate($end_date, $this->getSetting('time_format') . ' (T)'),
        ];
        // The event is on a single day with no time.
        if (empty($this->getSetting('time_format'))) {
          $elements[$delta] = $this->buildCustomDate($start_date, $this->getSetting('datetime_format'));
        }
      }
      else {
        // Start day and end day are different in the displayed time zone.
        $elements[$delta] = [
          'start_date' => $this->buildCustomDate($start_date, $this->getSetting('datetime_format')),
          'separator' => ['#plain_text' => ' - '],
          'end_date' => !empty($this->getSetting('time_format'))
          ? $this->buildCustomDate($end_date, $this->getSetting('datetime_format') . ' (T)')
          : $this->buildCustomDate($end_date, $this->getSetting('datetime_format')),
        ];
      }
    }

    return $elements;
  }

  /**
   * Prepare render for a date with custom format.
   */
  protected function buildCustomDate(DrupalDateTime $date, $format) {
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
    return $this->dateFormatter->format($date->getTimestamp(), 'custom', 'Y-m-d');
  }

}

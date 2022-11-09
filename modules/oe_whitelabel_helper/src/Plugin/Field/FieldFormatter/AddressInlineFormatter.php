<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_helper\Plugin\Field\FieldFormatter;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\Locale;
use Drupal\address\AddressInterface;
use Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Format an address inline with locale format and a configurable separator.
 *
 * @FieldFormatter(
 *   id = "oe_whitelabel_helper_address_inline",
 *   label = @Translation("Inline address"),
 *   field_types = {
 *     "address",
 *   },
 * )
 *
 * @see https://github.com/openeuropa/oe_theme/blob/3.x/modules/oe_theme_helper/src/Plugin/Field/FieldFormatter/AddressInlineFormatter.php
 */
class AddressInlineFormatter extends AddressDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'delimiter' => ', ',
      'fields_display' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delimiter'),
      '#default_value' => $this->getSetting('delimiter'),
      '#description' => $this->t('Specify delimiter between address items.'),
      '#required' => TRUE,
    ];

    $form['fields_display'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Fields to display'),
      '#default_value' => $this->getSetting('fields_display'),
      '#description' => $this->t('Which fields should be displayed. Leave empty for all.'),
      '#options' => $this->getFieldsDisplayOptions(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('Delimiter: @delimiter', [
        '@delimiter' => $this->getSetting('delimiter'),
      ]),
      $this->t('Fields to display: @fields', [
        '@fields' => implode(', ', array_filter($this->getSetting('fields_display'))),
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewElement($item, $langcode);
    }

    return $elements;
  }

  /**
   * Builds a renderable array for a single address item.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array.
   */
  protected function viewElement(AddressInterface $address, $langcode) {
    $country_code = $address->getCountryCode();
    $countries = $this->countryRepository->getList($langcode);
    $address_format = $this->addressFormatRepository->get($country_code);
    $values = $this->getValues($address, $address_format);

    $address_elements['%country'] = $countries[$country_code];
    foreach ($address_format->getUsedFields() as $field) {
      $address_elements['%' . $field] = $values[$field];
    }

    if (Locale::matchCandidates($address_format->getLocale(), $address->getLocale())) {
      $format_string = '%country' . "\n" . $address_format->getLocalFormat();
    }
    else {
      $format_string = $address_format->getFormat() . "\n" . '%country';
    }

    /*
     * Remove extra characters from address format since address fields are
     * optional.
     *
     * @see \CommerceGuys\Addressing\AddressFormat\AddressFormatRepository::getDefinitions()
     */
    $format_string = str_replace([',', ' - ', '/'], "\n", $format_string);
    $format_string = $this->alterFormatString($format_string);

    $items = $this->extractAddressItems($format_string, $address_elements);

    return [
      '#theme' => 'oe_whitelabel_helper_address_inline',
      '#address' => $address,
      '#address_items' => $items,
      '#address_delimiter' => $this->getSetting('delimiter'),
      '#cache' => [
        'contexts' => [
          'languages:' . LanguageInterface::TYPE_INTERFACE,
        ],
      ],
    ];
  }

  /**
   * Extract address items from a format string and replace placeholders.
   *
   * @param string $string
   *   The address format string, containing placeholders.
   * @param array $replacements
   *   An array of address items.
   *
   * @return array
   *   The exploded lines.
   */
  protected function extractAddressItems(string $string, array $replacements): array {
    // Make sure the replacements don't have any unneeded newlines.
    $replacements = array_map('trim', $replacements);
    $string = strtr($string, $replacements);
    // Remove noise caused by empty placeholders.
    $lines = explode("\n", $string);

    foreach ($lines as $index => $line) {
      // Remove leading punctuation, excess whitespace.
      $line = trim(preg_replace('/^[-,]+/', '', $line, 1));
      $line = preg_replace('/\s\s+/', ' ', $line);
      $lines[$index] = $line;
    }
    // Remove empty lines.
    $lines = array_filter($lines);

    return $lines;
  }

  /**
   * Provides the options for the fields display setting.
   *
   * @return array
   *   The fields display options.
   */
  protected function getFieldsDisplayOptions(): array {
    return [
      'country' => $this->t('The country'),
      AddressField::ADMINISTRATIVE_AREA => $this->t('The top-level administrative subdivision of the country'),
      AddressField::LOCALITY => $this->t('The locality (i.e. city)'),
      AddressField::DEPENDENT_LOCALITY => $this->t('The dependent locality (i.e. neighbourhood)'),
      AddressField::POSTAL_CODE => $this->t('The postal code'),
      AddressField::SORTING_CODE => $this->t('The sorting code'),
      AddressField::ADDRESS_LINE1 => $this->t('The first line of the address block'),
      AddressField::ADDRESS_LINE2 => $this->t('The second line of the address block'),
      AddressField::ORGANIZATION => $this->t('The organization'),
      AddressField::GIVEN_NAME => $this->t('The given name'),
      AddressField::ADDITIONAL_NAME => $this->t('The additional name'),
      AddressField::FAMILY_NAME => $this->t('The family name'),
    ];
  }

  /**
   * Alters the format string depending on fields options selected.
   *
   * @param string $format_string
   *   The format string.
   *
   * @return string
   *   The altered format string.
   */
  protected function alterFormatString(string $format_string): string {
    $options_selected = array_filter($this->getSetting('fields_display'));
    if (empty($options_selected)) {
      return $format_string;
    }
    $options_list = array_keys($this->getFieldsDisplayOptions());
    // Negate the selected options against the list.
    $fields = array_diff($options_list, $options_selected);
    // Prepend % to all items.
    $fields = array_map(function (string $field): string {
      return '%' . $field;
    }, $fields);
    // Alter the format string.
    return str_replace($fields, '', $format_string);
  }

}

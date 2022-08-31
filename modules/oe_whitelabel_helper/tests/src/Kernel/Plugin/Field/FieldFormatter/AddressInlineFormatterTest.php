<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_helper\Kernel\Plugin\Field\FieldFormatter;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\address\Kernel\Formatter\FormatterTestBase;

/**
 * Test AddressInlineFormatter plugin.
 */
class AddressInlineFormatterTest extends FormatterTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_whitelabel_helper',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createField('address', 'oe_whitelabel_helper_address_inline');
  }

  /**
   * Tests formatting of address.
   */
  public function testInlineFormatterAddress(): void {
    $entity = EntityTest::create([]);
    foreach ($this->addressFieldTestData() as $data) {
      if (!empty($data['settings'])) {
        $this->alterDisplaySettings($data['settings']);
      }

      $cloned_entity = clone $entity;
      $cloned_entity->{$this->fieldName} = $data['address'];
      $this->renderEntityFields($cloned_entity, $this->display);
      $this->assertRaw($data['expected']);
      $this->assertNoRaw('<img');
    }

    // Enable the formatter to show the icon.
    $this->display->setComponent($this->fieldName, [
      'type' => 'oe_whitelabel_helper_address_inline',
      'settings' => [
        'show_country_flag' => TRUE,
      ],
    ]);
    $this->display->save();
    $country_flags_folder = $this->container->get('extension.list.theme')->getPath('oe_whitelabel') . '/assets/icons/world-flags/';
    $file_url_generator = $this->container->get('file_url_generator');
    $country_repository = $this->container->get('address.country_repository');

    foreach ($this->addressFieldTestData() as $data) {
      $entity->set($this->fieldName, $data['address']);
      $this->renderEntityFields($entity, $this->display);

      $country_code = strtolower($data['address']['country_code']);
      $country_flag_path = $file_url_generator->generateString($country_flags_folder . $country_code . '.svg');
      $country_flag_html = sprintf('<img class="me-2 icon--fluid" alt="%s" src="%s" />', $country_repository->get($country_code)->getName(), $country_flag_path);
      $this->assertRaw($country_flag_html . ' ' . $data['expected']);
    }
  }

  /**
   * Test data for testInlineFormatterAddress.
   *
   * @return array[]
   *   An array of test data arrays with expected result.
   */
  protected function addressFieldTestData(): array {
    return [
      'Brussels Belgium' => [
        'address' => [
          'country_code' => 'BE',
          'locality' => 'Brussels <Bruxelles>',
          'postal_code' => '1000',
          'address_line1' => 'Rue de la Loi, 56 <123>',
          'address_line2' => 'or \'Wetstraat\' (Dutch), meaning "Law Street"',
        ],
        'expected' => 'Rue de la Loi, 56 &lt;123&gt;, or &#039;Wetstraat&#039; (Dutch), meaning &quot;Law Street&quot;, 1000 Brussels &lt;Bruxelles&gt;, Belgium',
      ],
      'Mexico' => [
        'address' => [
          'country_code' => 'MX',
        ],
        'expected' => 'Mexico',
      ],
      'Mexico Ciudad de Mexico' => [
        'address' => [
          'country_code' => 'MX',
          'administrative_area' => 'CDMX',
        ],
        'expected' => 'CDMX, Mexico',
      ],
      'Mexico Baja California Tijuana' => [
        'address' => [
          'country_code' => 'MX',
          'administrative_area' => 'B.C.',
          'locality' => 'Tijuana',
        ],
        'expected' => 'Tijuana, B.C., Mexico',
      ],
      'Mexico Baja California Tijuana 22000' => [
        'address' => [
          'country_code' => 'MX',
          'administrative_area' => 'B.C.',
          'locality' => 'Tijuana',
          'postal_code' => '22000',
        ],
        'expected' => '22000 Tijuana, B.C., Mexico',
      ],
      'Mexico Baja California Tijuana 22000 Street' => [
        'address' => [
          'country_code' => 'MX',
          'administrative_area' => 'B.C.',
          'locality' => 'Tijuana',
          'postal_code' => '22000',
          'address_line1' => 'Street',
        ],
        'expected' => 'Street, 22000 Tijuana, B.C., Mexico',
      ],
      'Bangladesh Dhaka' => [
        'address' => [
          'country_code' => 'BD',
          'locality' => 'Dhaka',
        ],
        'expected' => 'Dhaka, Bangladesh',
      ],
      'Bangladesh Dhaka 1100' => [
        'address' => [
          'country_code' => 'BD',
          'locality' => 'Dhaka',
          'postal_code' => '1100',
        ],
        'expected' => 'Dhaka, 1100, Bangladesh',
      ],
      'Brussels Belgium with $ delimiter' => [
        'address' => [
          'country_code' => 'BE',
          'locality' => 'Brussels',
          'postal_code' => '1000',
          'address_line1' => 'Rue de la Loi',
          'address_line2' => '56',
        ],
        'expected' => 'Rue de la Loi$56$1000 Brussels$Belgium',
        'settings' => [
          'delimiter' => '$',
        ],
      ],
      'Brussels Belgium only country and locality' => [
        'address' => [
          'country_code' => 'BE',
          'locality' => 'Brussels',
          'postal_code' => '1000',
          'address_line1' => 'Rue de la Loi',
          'address_line2' => '56',
        ],
        'expected' => 'Brussels, Belgium',
        'settings' => [
          'delimiter' => ', ',
          'properties' => [
            'country' => 'country',
            'locality' => 'locality',
          ],
        ],
      ],
    ];
  }

  /**
   * Alters the display's settings.
   *
   * @param array $settings
   *   The settings.
   */
  protected function alterDisplaySettings(array $settings): void {
    $this->display->setComponent($this->fieldName, [
      'type' => 'oe_whitelabel_helper_address_inline',
      'settings' => $settings,
    ]);
    $this->display->save();
  }

}

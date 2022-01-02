<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_helper\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\oe_whitelabel_helper\TwigExtension\TwigExtension;
use Drupal\Tests\token\Kernel\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Twig\Error\RuntimeError;

/**
 * Tests for the custom Twig filters and functions extension.
 *
 * @group oe_whitelabel_helper
 *
 * @coversDefaultClass \Drupal\oe_whitelabel_helper\TwigExtension\TwigExtension
 */
class TwigExtensionTest extends KernelTestBase {

  /**
   * A static point in time.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $staticTime;

  /**
   * The twig environment.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  /**
   * The twig extension.
   *
   * @var \Twig\Extension\ExtensionInterface
   */
  protected $extension;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_whitelabel_helper',
    'block',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->staticTime = new DrupalDateTime('2021-12-02 14:00:00', DateTimeItemInterface::STORAGE_TIMEZONE);

    $request_stack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->getMock();

    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_TIME', $this->staticTime->getTimestamp());

    // Mocks a the request stack getting the current request.
    $request_stack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $this->container->set('request_stack', $request_stack);
    $this->twig = $this->container->get('twig');
    $this->extension = $this->twig->getExtension(TwigExtension::class);
  }

  /**
   * Test the time ago method.
   */
  public function testTimeAgo(): void {
    // Now.
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $this->staticTime->getTimestamp()]);
    $this->assertEquals('0 seconds ago', $actual);
    // Seconds.
    $time_one_seconds = (clone $this->staticTime)->modify('- 1 second')->getTimestamp();
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $time_one_seconds]);
    $this->assertEquals('1 second ago', $actual);
    $time_two_seconds = (clone $this->staticTime)->modify('- 2 seconds')->getTimestamp();
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $time_two_seconds]);
    $this->assertEquals('2 seconds ago', $actual);
    // Minutes.
    $time_one_minute = (clone $this->staticTime)->modify('- 1 minute')->getTimestamp();
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $time_one_minute]);
    $this->assertEquals('1 minute ago', $actual);
    $time_two_minutes = (clone $this->staticTime)->modify('- 2 minutes')->getTimestamp();
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $time_two_minutes]);
    $this->assertEquals('2 minutes ago', $actual);
    // Hours.
    $time_one_hour = (clone $this->staticTime)->modify('- 1 hour')->getTimestamp();
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $time_one_hour]);
    $this->assertEquals('1 hour ago', $actual);
    $time_three_hours = (clone $this->staticTime)->modify('- 3 hours')->getTimestamp();
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $time_three_hours]);
    $this->assertEquals('3 hours ago', $actual);
    // Days.
    $time_one_day = (clone $this->staticTime)->modify('- 1 day')->getTimestamp();
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $time_one_day]);
    $this->assertEquals('1 day ago', $actual);
    $time_four_days = (clone $this->staticTime)->modify('- 4 days')->getTimestamp();
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $time_four_days]);
    $this->assertEquals('4 days ago', $actual);
    // Weeks.
    $time_one_week = (clone $this->staticTime)->modify('- 1 week')->getTimestamp();
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $time_one_week]);
    $this->assertEquals('1 week ago', $actual);
    $time_three_weeks = (clone $this->staticTime)->modify('- 3 weeks')->getTimestamp();
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $time_three_weeks]);
    $this->assertEquals('3 weeks ago', $actual);
    // Months.
    $time_one_month = (clone $this->staticTime)->modify('- 1 month')->getTimestamp();
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $time_one_month]);
    $this->assertEquals('1 month ago', $actual);
    $time_four_months = (clone $this->staticTime)->modify('- 4 months')->getTimestamp();
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $time_four_months]);
    $this->assertEquals('4 months ago', $actual);
    // Years.
    $time_one_year = (clone $this->staticTime)->modify('- 1 year')->getTimestamp();
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $time_one_year]);
    $this->assertEquals('1 year ago', $actual);
    $time_five_years = (clone $this->staticTime)->modify('- 5 years')->getTimestamp();
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $time_five_years]);
    $this->assertEquals('5 years ago', $actual);
    // Test for unusual but valid values.
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => 0]);
    $this->assertEquals('51 years ago', $actual);
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => -1]);
    $this->assertEquals('51 years ago', $actual);
    $actual = $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => TRUE]);
    $this->assertEquals('51 years ago', $actual);
  }

  /**
   * Test the time ago method with invalid data.
   *
   * @param mixed $timestamp
   *   The invalid timestamp.
   * @param mixed $error
   *   The expected error class.
   *
   * @dataProvider invalidTimeAgoProvider
   */
  public function testInvalidTimeAgo($timestamp, $error): void {
    $this->expectException($error);
    $this->twig->renderInline('{{ timestamp|bcl_timeago }}', ['timestamp' => $timestamp]);
  }

  /**
   * Provides invalid values.
   *
   * @return array
   *   Test cases with invalid values.
   */
  public function invalidTimeAgoProvider(): array {
    return [
      [FALSE, RuntimeError::class],
      [NULL, \TypeError::class],
      ['', RuntimeError::class],
      ['lizard', RuntimeError::class],
      [[], \TypeError::class],
    ];
  }

  /**
   * Test the formatting of the BCL footer links.
   */
  public function testFooterLinks(): void {
    $context = [
      'bcl_icon_path' => '/path/to/theme/resources/icons/',
    ];
    $links = [
      [
        'label' => 'Simple Link',
        'href' => '#',
      ],
      [
        'label' => 'External Link',
        'href' => '#',
        'external' => TRUE,
      ],
      [
        'label' => 'Social Link',
        'href' => '#',
        'social_network' => 'facebook',
      ],
    ];
    $expected = [
      [
        'label' => 'Simple Link',
        'path' => '#',
        'icon_position' => 'after',
        'standalone' => TRUE,
        'attributes' => [
          'class' => [
            'd-block',
            'mb-1',
          ],
        ],
      ],
      [
        'label' => 'External Link',
        'path' => '#',
        'icon_position' => 'after',
        'standalone' => TRUE,
        'attributes' => [
          'class' => [
            'd-block',
            'mb-1',
          ],
        ],
        'icon' => [
          'path' => '/path/to/theme/resources/icons/',
          'name' => 'box-arrow-up-right',
          'size' => 'xs',
        ],
      ],
      [
        'label' => 'Social Link',
        'path' => '#',
        'icon_position' => 'before',
        'standalone' => TRUE,
        'attributes' => [
          'class' => [
            'd-block',
            'mb-1',
          ],
        ],
        'icon' => [
          'path' => '/path/to/theme/resources/icons/',
          'name' => 'facebook',
          'size' => 'xs',
        ],
      ],
    ];

    $actual = $this->extension->bclFooterLinks($context, $links);
    $this->assertSame($expected, $actual);
  }

  /**
   * Test the result of the bcl_block method.
   */
  public function testBlock(): void {
    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('name', 'Site name')
      ->set('slogan', 'Slogan')
      ->save();

    $actual = $this->extension->bclBlock('system_branding_block');
    $expected = [
      'site_logo',
      'site_name',
      'site_slogan',
    ];
    $this->assertSame($expected, array_keys($actual));
    $this->assertSame('image', $actual['site_logo']['#theme']);
    $this->assertArrayHasKey('#uri', $actual['site_logo']);
    $this->assertSame(TRUE, $actual['site_logo']['#access']);
    $expected = [
      '#markup' => 'Site name',
      '#access' => TRUE,
    ];
    $this->assertSame($expected, $actual['site_name']);
    $expected = [
      '#markup' => 'Slogan',
      '#access' => TRUE,
    ];
    $this->assertSame($expected, $actual['site_slogan']);
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_contact_forms\Kernel;

use Drupal\contact\Entity\ContactForm;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\oe_whitelabel\PatternAssertions\DescriptionListAssert;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests that our contact forms renders with right markup.
 */
class ContactFormRenderTest extends KernelTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_bootstrap_theme_helper',
    'ui_patterns',
    'ui_patterns_library',
    'path',
    'path_alias',
    'options',
    'user',
    'system',
    'telephone',
    'contact',
    'contact_storage',
    'rdf_skos',
    'oe_contact_forms',
    'oe_corporate_countries',
    'sparql_entity_storage',
    'oe_whitelabel_contact_forms',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    \Drupal::service('theme_installer')->install(['oe_whitelabel']);
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('contact_message');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'oe_whitelabel')
      ->save();
    $this->setUpSparql();
  }

  /**
   * Tests that corporate contact form is rendered with the correct ECL markup.
   */
  public function testContactForm(): void {
    $contact_form = ContactForm::create(['id' => 'oe_contact_form']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'header', 'this is a test header');
    $privacy_url = 'http://example.net';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'privacy_policy', $privacy_url);
    $optional_selected = ['oe_telephone' => 'oe_telephone'];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'optional_fields', $optional_selected);
    $topics = [
      [
        'topic_name' => 'Topic name',
        'topic_email_address' => 'topic@emailaddress.com',
      ],
    ];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', $topics);
    $contact_form->save();

    /** @var \Drupal\contact\MessageInterface $contact_message */
    $message = $this->container->get('entity_type.manager')->getStorage('contact_message')->create([
      'contact_form' => $contact_form->id(),
      'name' => 'sender_name',
      'mail' => 'test@example.com',
      'subject' => 'subject',
      'message' => 'welcome_message',
      'oe_telephone' => '0123456',
      'oe_topic' => 'Topic name',
    ]);

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $form = $this->container->get('entity.form_builder')->getForm($message, 'corporate_default');
    $crawler = new Crawler($this->render($form));

    // Assert classes and contact form.
    $actual = $crawler->filter('hr.mt-5.mb-5');
    $this->assertCount(1, $actual);
    $actual = $crawler->filter('div.mb-2');
    $this->assertCount(6, $actual);
    $form = $crawler->filter('form.contact-form');
    $this->assertCount(6, $actual);

    /** @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = $this->container->get('messenger');
    $full_view = $entity_type_manager->getViewBuilder('contact_message')->view($message, 'full');
    $messenger->addMessage($full_view);
    $messages = $messenger->messagesByType('status');
    $html = $this->render($messages);
    $crawler = new Crawler($html);

    // Assert message success and values.
    $actual = $crawler->filter('div.alert-success');
    $this->assertCount(1, $actual);

    $description_lists = $crawler->filter('dl');
    $description_list_assert = new DescriptionListAssert();

    $description_list_assert->assertPattern([
      'items' => [
        [
          'term' => 'The sender\'s name',
          'definition' => 'sender_name',
        ],
      ],
    ], $description_lists->eq(0)->html());

    $description_list_assert->assertPattern([
      'items' => [
        [
          'term' => 'The sender\'s email',
          'definition' => 'test@example.com',
        ],
      ],
    ], $description_lists->eq(1)->html());

    $description_list_assert->assertPattern([
      'items' => [
        [
          'term' => 'Subject',
          'definition' => 'subject',
        ],
      ],
    ], $description_lists->eq(2)->html());

    $description_list_assert->assertPattern([
      'items' => [
        [
          'term' => 'Message',
          'definition' => 'welcome_message',
        ],
      ],
    ], $description_lists->eq(3)->html());

    $description_list_assert->assertPattern([
      'items' => [
        [
          'term' => 'Topic',
          'definition' => 'Topic name',
        ],
      ],
    ], $description_lists->eq(4)->html());

    $description_list_assert->assertPattern([
      'items' => [
        [
          'term' => 'Phone',
          'definition' => '0123456',
        ],
      ],
    ], $description_lists->eq(5)->html());
  }

}

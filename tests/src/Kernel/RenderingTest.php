<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel\Kernel;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\token\Kernel\KernelTestBase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests that rendering of elements follows the theme implementation.
 */
class RenderingTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oe_whitelabel_rendering_test_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $structure
   *   The structure of the form, read from the fixtures files.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $structure = NULL): array {
    $form['test'] = $structure;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Test rendering of elements.
   *
   * @param array $structure
   *   A render array.
   * @param array $assertions
   *   Test assertions.
   *
   * @throws \Exception
   *
   * @dataProvider renderingDataProvider
   */
  public function testRendering(array $structure, array $assertions): void {
    // Wrap all the test structure inside a form. This will allow proper
    // processing of form elements and invocation of form alter hooks.
    // Even if the elements being tested are not form related, the form can
    // host them without causing any issues.
    $form_state = new FormState();
    $form_state->addBuildInfo('args', [$structure]);
    $form_state->setProgrammed();

    $form = $this->container->get('form_builder')->buildForm($this, $form_state);
    $this->assertRendering($this->renderRoot($form), $assertions);
  }

  /**
   * Data provider for rendering tests.
   *
   * The actual data is read from fixtures stored in a YAML configuration.
   *
   * @return array
   *   A set of dump data for testing.
   */
  public function renderingDataProvider(): array {
    return $this->getFixtureContent('rendering.yml');
  }

  /**
   * Run various assertion on given HTML string via CSS selectors.
   *
   * Specifically:
   *
   * - 'count': assert how many times the given HTML elements occur.
   * - 'equals': assert content of given HTML elements.
   * - 'contains': assert content contained in given HTML elements.
   *
   * Assertions array has to be provided in the following format:
   *
   * [
   *   'count' => [
   *     '.ecl-page-header' => 1,
   *   ],
   *   'equals' => [
   *     '.ecl-page-header__identity' => 'Digital single market',
   *   ],
   *   'contains' => [
   *     'Digital',
   *     'single',
   *     'market',
   *   ],
   * ]
   *
   * @param string $html
   *   A render array.
   * @param array $assertions
   *   Test assertions.
   */
  protected function assertRendering(string $html, array $assertions): void {
    $crawler = new Crawler($html);

    // Assert presence of given strings.
    if (isset($assertions['contains'])) {
      foreach ($assertions['contains'] as $string) {
        $message = "String '{$string}' not found in:" . PHP_EOL . $html;
        $this->assertContains($string, $html, $message);
      }
    }

    // Assert occurrences of given elements.
    if (isset($assertions['count'])) {
      foreach ($assertions['count'] as $name => $expected) {
        $message = "Wrong number of occurrences found for element '{$name}' in:" . PHP_EOL . $html;
        $this->assertCount($expected, $crawler->filter($name), $message);
      }
    }

    // Assert that a given element content equals a given string.
    if (isset($assertions['equals'])) {
      foreach ($assertions['equals'] as $name => $expected) {
        try {
          $actual = trim($crawler->filter($name)->html());
        }
        catch (\InvalidArgumentException $exception) {
          $this->fail(sprintf('Element "%s" not found (exception: "%s") in: ' . PHP_EOL . ' %s', $name, $exception->getMessage(), $html));
        }
        $this->assertEquals($expected, $actual);
      }
    }
  }

  /**
   * Get fixture content.
   *
   * @param string $filepath
   *   File path.
   *
   * @return array
   *   A set of test data.
   */
  protected function getFixtureContent(string $filepath): array {
    return Yaml::parse(file_get_contents(__DIR__ . "/fixtures/{$filepath}"));
  }

}

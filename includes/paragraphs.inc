<?php

/**
 * @file
 * Theme functions for paragraphs.
 */

declare(strict_types=1);

use Drupal\oe_whitelabel\ColorSchemePreprocess;

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function oe_whitelabel_theme_suggestions_paragraph_alter(array &$suggestions, array $variables): void {
  /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
  $paragraph = $variables['elements']['#paragraph'];

  if ($paragraph->bundle() === 'oe_content_row') {
    $variant = $paragraph->get('oe_paragraphs_variant')->first()->value;
    $suggestions[] = 'paragraph__' . $paragraph->bundle() . '__variant_' . $variant;
  }
}

/**
 * Implements hook_preprocess_HOOK() for paragraphs.
 */
function oe_whitelabel_preprocess_paragraph(&$variables) {
  /** @var \Drupal\oe_whitelabel\ColorSchemePreprocess $color_scheme_preprocess */
  $color_scheme_preprocess = \Drupal::classResolver(ColorSchemePreprocess::class);
  $color_scheme_preprocess->injectColorScheme($variables);
}

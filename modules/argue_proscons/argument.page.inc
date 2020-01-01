<?php

/**
 * @file
 * Contains argument.page.inc.
 *
 * Page callback for Argument entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Argument templates.
 *
 * Default template: argument.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_argument(array &$variables) {
  // Fetch Argument Entity Object.
  $argument = $variables['elements']['#argument'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

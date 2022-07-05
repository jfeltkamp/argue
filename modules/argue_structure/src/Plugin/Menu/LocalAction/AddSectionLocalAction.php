<?php

namespace Drupal\argue_structure\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines a local action plugin with a dynamic title.
 */
class AddSectionLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   *
   * Get contextual term id and set default query parameter on action link.
   */
  public function getOptions(RouteMatchInterface $route_match) {
    /** @var \Drupal\taxonomy\TermInterface|NULL $term */
    if ($term = $route_match->getParameters()->get('taxonomy_term')) {
      return [
        'query' => [
          'sector' => $term->id(),
        ],
      ];
    }
    else {
      return [];
    }
  }

}

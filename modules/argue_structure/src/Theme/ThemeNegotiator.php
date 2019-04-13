<?php
namespace Drupal\argue_structure\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;

class ThemeNegotiator implements ThemeNegotiatorInterface {
  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // Use this theme on a certain route.
    // return $route_match->getRouteName() == 'example_route_name';

    // Or use this for more than one route:
    $possible_routes = array(
      'entity.argument.add_form',
      'entity.argument.edit_form',
      'entity.argument.delete_form',
      'entity.argument.version_history',
      'entity.patch.edit_form',
      'entity.user.edit_form'
    );

    $current_route = $route_match->getRouteName();

    return (in_array($current_route, $possible_routes));
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // Here you return the actual theme name.
    return 'argue_base';
  }

}

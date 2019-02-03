<?php

namespace Drupal\argue_structure\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Displayed instead of the default login form.
 *
 * see
 *    \Drupal\argue_structure\Routing\AlterRouteSubscriber
 * and
 *    \Drupal\argue_structure\Plugin\Block\UserLoginBlockArgue
 */
class LoginController extends ControllerBase {

  /**
   * Content displayed instead of the default user.login form.
   *
   * @return array
   *   Return empty content.
   */
  public function content() {
    return [];
  }

}

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
class DashboardController extends ControllerBase {

  /**
   * Content displayed instead of the default user.login form.
   *
   * @return array
   *   Return empty content.
   */
  public function content() {
    return ['#markup' => '<h2>hihi</h2>'];
  }

  /**
   * Content displayed instead of the default user.login form.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *
   *   Return empty content.
   */
  public function getTitle() {
    return $this->t('Argue Dashboard');
  }

}

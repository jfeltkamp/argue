<?php

namespace Drupal\argue_structure\Controller;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new RuleOverviewController object.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }
  /**
   * Content displayed instead of the default user.login form.
   *
   * @return array
   *   Return empty content.
   */
  public function content() {

    return [
      '#attached' => [
        'library' => ['argue_base/history']
      ]
    ];
  }

  /**
   * Content displayed instead of the default user.login form.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *
   *   Return empty content.
   */
  public function getTitle() {
    return $this->t('Welcome back, @username', [
      '@username' => $this->currentUser->getAccountName()
    ]);
  }

}

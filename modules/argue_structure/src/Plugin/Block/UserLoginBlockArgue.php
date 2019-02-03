<?php

namespace Drupal\argue_structure\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\user\Plugin\Block\UserLoginBlock;
use Drupal\Core\Session\AccountInterface;

/**
 * For Argue a block is needed display everywhere.
 * Provides a 'User login' block even displayed on route user/login.
 * All what this class does is to replicate the user login form block
 * and remove the route user.login from the filter.
 * was before: !in_array($route_name, ['user.login', 'user.logout']))
 *
 * Issue was, that the login form at user.login is another than the one
 * on user login block. The route user.login is altered in this class.
 *
 *
 * @Block(
 *   id = "user_login_block_argue",
 *   admin_label = @Translation("User login Argue"),
 *   category = @Translation("Forms")
 * )
 */
class UserLoginBlockArgue extends UserLoginBlock {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $route_name = $this->routeMatch->getRouteName();
    if ($account->isAnonymous() && !in_array($route_name, ['user.logout'])) {
      return AccessResult::allowed()
        ->addCacheContexts(['route.name', 'user.roles:anonymous']);
    }
    return AccessResult::forbidden();
  }

}

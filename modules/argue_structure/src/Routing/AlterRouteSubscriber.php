<?php

namespace Drupal\argue_structure\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Replace login form by an empty controller because .
 */
class AlterRouteSubscriber extends RouteSubscriberBase
{

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    // Change path '/user/login' to '/argue/login'.
    // Set empty controller instead of login.form.
    if ($route = $collection->get('user.login')) {
      $route->setPath('/argue/login');
      $route->addDefaults([
        '_controller' => '\Drupal\argue_structure\Controller\LoginController::content',
        '_title' => 'Argue login',
      ]);
    }

    // Change path '/user/register' to '/argue/login'.
    if ($route = $collection->get('user.register')) {
      $route->setPath('/argue/register');
    }

    // Change path '/user/register' to '/argue/login'.
    if ($route = $collection->get('user.pass')) {
      $route->setPath('/argue/password');
    }

    // Change path '/user/logout' to '/argue/logout'.
    if ($route = $collection->get('user.logout')) {
      $route->setPath('/argue/logout');
    }

  }

}
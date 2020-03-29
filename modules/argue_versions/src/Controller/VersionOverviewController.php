<?php

namespace Drupal\argue_versions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class VersionOverviewController.
 */
class VersionOverviewController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->configFactory = $container->get('config.factory');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * viewVersions.
   *
   * @return array
   *   Return Hello string.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function viewVersions() {
    if($this->currentUser->hasPermission('access argue version overview')) {
      /** @var \Drupal\node\NodeInterface[] $versions */
      $versions = $this->entityTypeManager->getStorage('node')->loadByProperties([
        'type' => 'version',
        'status' => 1,
      ]);

      // Sort newest first.
      krsort($versions);

      $view = [
        '#theme' => 'argue_versions',
        '#description' => $this->t('Implement method: title'),
      ];

      $date = new DrupalDateTime('now');
      $today = $date->format('Y-m-d');

      $view_builder = $this->entityTypeManager->getViewBuilder('node');
      foreach ($versions as $version) {
        $version_view = $view_builder->view($version, 'list_item');
        $version_valid_date = $version->get('field_valid_date')->getString();
        if($version_valid_date > $today) {
          $view['#latest'][] = $version_view;
        } elseif (!isset($view['#current']) && $version_valid_date <= $today) {
          $view['#current'][] = $version_view;
        } else {
          $view['#history'][] = $version_view;
        }
      }

    }


    return $view;
  }

}

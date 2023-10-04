<?php

namespace Drupal\argue_user;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * ArgueUserPointsPlugin plugin manager.
 */
class ArgueUserPointsPluginManager extends DefaultPluginManager {

  /**
   * Constructs ArgueUserPointsPluginPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ArgueUserPoints',
      $namespaces,
      $module_handler,
      'Drupal\argue_user\ArgueUserPointsPluginInterface',
      'Drupal\argue_user\Annotation\ArgueUserPointsPlugin'
    );
    $this->alterInfo('argue_user_points_info');
    $this->setCacheBackend($cache_backend, 'argue_user_points_plugins');
  }

  /**
   * Selects proper plugin and assigns user points.
   *
   * @param string $base_type
   *   The base point type.
   * @param string $action
   *   The action to handle.
   * @param mixed $context
   *   The context to handle inside the plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function assignPoints(string $base_type, string $action, mixed $context): void {
    if ($plugin_id = $this->findPluginId($base_type, $action, $context)) {
      /** @var ArgueUserPointsPluginInterface $plugin */
      $plugin = $this->createInstance($plugin_id);
      $plugin->handleAction($action, $context);
    }
  }

  /**
   * Get proper plugin id for request.
   *
   * @param string $base_type
   *   The base point type.
   * @param string $action
   *   The action to handle.
   * @param mixed $context
   *   The context to handle inside the plugin.
   *
   * @return string|null
   *   The plugin id that fits to request.
   */
  protected function findPluginId(string $base_type, string $action, mixed $context): ?string {
    switch ($base_type) {
      case 'entity_actions':
        return $this->findEntityActionPluginId($action, $context);

      default:
        return NULL;
    }
  }

  /**
   * Sub-action of getPluginId() to validate for entity_actions.
   *
   * @param string $action
   *   The action to handle.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The context entity.
   *
   * @return string|null
   *   The plugin id that fits to request.
   */
  protected function findEntityActionPluginId(string $action, EntityInterface $entity): ?string {
    $type_bundle = "{$entity->getEntityTypeId()}__{$entity->bundle()}";
    foreach ($this->getDefinitions() as $def) {
      $isType = ($def['base'] === 'entity_actions');
      $hasActionKey = isset($def['actions'][$action]);
      $supported = $def['supported_entity_bundle_types'];
      $supports = (is_string($supported) && preg_match($supported, $type_bundle)) || (is_array($supported) && in_array($type_bundle, $supported));
      if ($isType && $hasActionKey && $supports) {
        return $def['id'];
      }
    }
    return NULL;
  }

}

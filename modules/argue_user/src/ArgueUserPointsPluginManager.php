<?php

namespace Drupal\argue_user;

use Drupal\Core\Cache\CacheBackendInterface;
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
   * @param string $plugin_key
   *   The plugin key to find proper plugin.
   * @param string $action
   *   The action to handle.
   * @param mixed $context
   *   Context to handle in plugin. Data type vary - to be checked in plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function assignPoints(string $plugin_key, string $action, mixed $context): void {
    if ($plugin_id = $this->getActionPluginId($plugin_key, $action)) {
      /** @var ArgueUserPointsPluginInterface $plugin */
      $plugin = $this->createInstance($plugin_id);
      $plugin->handleAction($action, $context);
    }
  }

  /**
   * Sub-action of getPluginId() to validate for entity_actions.
   *
   * @param string $plugin_key
   *   The plugin key to find proper plugin.
   * @param string $action
   *   The action to handle.
   *
   * @return string|null
   *   The plugin id that fits to request.
   */
  protected function getActionPluginId(string $plugin_key, string $action): ?string {
    foreach ($this->getDefinitions() as $def) {
      $hasActionKey = isset($def['actions'][$action]);
      $plugin_keys = $def['plugin_keys'];
      $supports = (is_string($plugin_keys) && preg_match($plugin_keys, $plugin_key)) || (is_array($plugin_keys) && in_array($plugin_key, $plugin_keys));
      if ($hasActionKey && $supports) {
        return $def['id'];
      }
    }
    return NULL;
  }

}

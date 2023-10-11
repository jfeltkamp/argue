<?php

namespace Drupal\argue_user\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines argue_user_points_plugin annotation object.
 *
 * @Annotation
 */
class ArgueUserPointsPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

  /**
   * The plugin configuration.
   *
   * @var array
   */
  public array $actions;

  /**
   * The interface for given entity.
   *
   * @var string
   */
  public string $interface;

  /**
   * The userpoint type the plugin by default works on.
   *
   * @var string
   */
  public string $default_userpoint_type;

  /**
   * Match keys given as pattern or array to select plugin.
   *
   * @var string|array
   */
  public string|array $plugin_keys;

  /**
   * Default validation settings to avoid typical side effects.
   *
   * Can be overwritten by config and plugin hook.
   *
   * @var array[]
   */
  public array $validation_defaults;

}

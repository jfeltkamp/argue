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
   * The plugin base type e.g. entity_actions.
   *
   * @var string
   */
  public string $base;

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
  public string $default_user_point_type;

}

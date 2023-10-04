<?php

namespace Drupal\argue_user;

/**
 * Interface for argue_user_points_plugin plugins.
 */
interface ArgueUserPointsPluginInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label(): string;

  /**
   * Entry point for handling points.
   *
   * @param string $action
   *   The action (id) that the user triggered.
   * @param mixed $context
   *   Referrer to handle in plugin. This may be an entity or any other type.
   */
  public function handleAction(string $action, mixed $context = NULL): void;

  /**
   * Returns available actions for this plugin.
   *
   * @return array
   *   Available actions with points to assign.
   */
  public function getActions(): array;

  /**
   * Returns number of action points to assign for triggered action.
   *
   * @return int
   *   Number of action points.
   */
  public function getActionPoints(string $action): int;

  /**
   * Returns if plugin support this action.
   *
   * @return bool
   *   Is TRUE if action is supported, FALSE if not.
   */
  public function hasAction(string $action): bool;

  /**
   * Returns the default userpoints Type for this plugin.
   *
   * @return string
   *   The default userpoints type.
   */
  public function getDefaultUserPointsType(): string;

}

<?php

namespace Drupal\argue_user;

use Drupal\Core\Entity\EntityInterface;

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
  public function getDefaultUserpointType(): string;

  /**
   * Build an ID from collection of different identifiers.
   *
   * @param array $ids
   *   Multiple different identifiers.
   *
   * @return string
   *   The ID that is qualified as selector.
   */
  public function buildLogId(array $ids): string;

  /**
   * Validates if rule is applicable.
   *
   * @param string $action
   *   Action id to load the validation_defaults from plugin definition.
   *
   * @return bool
   *   Return true if rule is applicable.
   */
  public function validates(string $action): bool;

  /**
   * Get validation defaults.
   *
   * @param string $action
   *   The action for which the defaults should be returned.
   *
   * @return array
   *   Array with the validation definitions.
   */
  public function getValidationDefaults(string $action): array;

}

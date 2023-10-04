<?php

namespace Drupal\argue_user;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\userpoints\Service\UserPointsServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\UserInterface;

/**
 * Base class for argue_user_points_plugin plugins.
 */
abstract class ArgueUserPointsPluginBase extends PluginBase implements ArgueUserPointsPluginInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\user\UserInterface definition.
   *
   * @var \Drupal\user\UserInterface|null
   */
  protected ?UserInterface $user = NULL;

  /**
   * Drupal\userpoints\Service\UserPointsServiceInterface definition.
   *
   * @var \Drupal\userpoints\Service\UserPointsServiceInterface
   */
  protected $userpointsService;

  /**
   * Constructs a FieldDiffBuilderBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current users AccountProxy.
   * @param \Drupal\userpoints\Service\UserPointsServiceInterface $user_points
   *   The user points service from UserPoints contrib module.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, UserPointsServiceInterface $user_points) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    if ($user_id = $this->currentUser->id()) {
      $this->user = $this->entityTypeManager->getStorage('user')->load($user_id);
    }
    $this->userpointsService = $user_points;
    // $this->configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('userpoints.points')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getActions(): array {
    return array_keys($this->getPluginDefinition()['actions'] ?? []);
  }

  /**
   * {@inheritdoc}
   */
  public function getActionPoints(string $action): int {
    $actions = $this->getPluginDefinition()['actions'];
    return (int) $actions[$action] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAction(string $action): bool {
    return in_array($action, $this->getActions());
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultUserPointsType(): string {
    return $this->getPluginDefinition()['default_user_point_type'];
  }

}

<?php

namespace Drupal\argue_user;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
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
abstract class EntityActionsPluginBase extends PluginBase implements ArgueUserPointsPluginInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current active database's master connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

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
  protected UserPointsServiceInterface $userpointsService;

  /**
   * Actions provided by this plugin.
   *
   * @var array|null
   */
  protected ?array $actions = NULL;

  /**
   * Service to receive configuration for the ruled behaviour.
   *
   * @var UserPointsActionRules
   */
  protected UserPointsActionRules $userPointsActionRules;

  /**
   * Saves the ruled behaviour during runtime.
   *
   * @var array|null
   */
  protected ?array $rule = NULL;

  /**
   * The log id used for userpoints log messages.
   *
   * @var string
   */
  protected string $logId;

  /**
   * Service to receive configuration for the ruled behaviour.
   *
   * @var array|null
   */
  protected ?array $repetitions = NULL;

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
   * @param \Drupal\argue_user\UserPointsActionRules $user_points_action_rules
   *   The user points service from UserPoints contrib module.
   * @param \Drupal\Core\Database\Connection $database
   *   The current active database's master connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, UserPointsServiceInterface $user_points, UserPointsActionRules $user_points_action_rules, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->userpointsService = $user_points;
    $this->userPointsActionRules = $user_points_action_rules;
    $this->database = $database;
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
      $container->get('userpoints.points'),
      $container->get('argue_user_points.action_rules'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function handleAction(string $action, mixed $context = NULL): void {
    if ($this->hasAction($action) && $context instanceof EntityInterface) {
      if ($method = $this->getAction($action)) {
        $this->{$method}($context, $action);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLogIdStack(EntityInterface $entity, string $action): ?array {
    return [
      'rule' => $this->rule['id'],
      'entity_shortcut' => "{$entity->getEntityTypeId()}:{$entity->id()}",
    ];
  }

  /**
   * Lazy load the current rule config.
   *
   * Because this is an initial action, some mor things are done.
   * - Receive the config.
   * - Find the rule definition in config.
   * - Sets addressed user.
   *
   * @return bool
   *   Return the rule config.
   */
  public function initRule(EntityInterface $entity, string $action): bool {
    if (!$this->rule) {
      $user_points_rule_id = $this->buildRuleId($entity, $action);
      if ($rule_content = $this->userPointsActionRules->getRule($user_points_rule_id)) {
        $this->rule = array_merge(['id' => $user_points_rule_id], $rule_content);
        if (!$this->setUser($entity, $user_points_rule_id)) {
          return FALSE;
        }
        if (!($log_id_stack = $this->getLogIdStack($entity, $action))) {
          return FALSE;
        }
        $this->logId = $this->buildLogId($log_id_stack);
      }
    }
    return !!$this->rule;
  }

  /**
   * Represents the default action that can always be used.
   *
   * Just add points without any conditions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The currently handled entity.
   * @param string $action
   *   The current action passed through as string.
   */
  protected function defaultAction(EntityInterface $entity, string $action): void {
    if ($this->initRule($entity, $action) && $this->validates($action)) {
      $log_msg = $this->buildLogMessage($entity, $action, $this->logId);
      $this->userpointsService->addPoints($this->rule['points'], $this->getDefaultUserpointType(), $this->user, $log_msg);
    }
  }

  /**
   * Get the user that created an entity, given ba entity_type_id and entity_id.
   *
   * @param string $entity_type
   *   The entity type id of the entity to look up for the creator.
   * @param string|int $entity_id
   *   The entity id of the entity to look up for the creator.
   * @param bool $return_user_entity
   *   If FALSE the method returns the user_id, if TRUE the user object.
   *
   * @return \Drupal\user\UserInterface|int|string|null
   *   Returns the user that created the entity.
   */
  public function getEntityCreator(string $entity_type, string|int $entity_id, bool $return_user_entity = FALSE): UserInterface|int|string|null {
    $user = NULL;
    try {
      // Get entity type.
      $entity_storage = $this->entityTypeManager->getStorage($entity_type);
      $entity_type = $entity_storage->getEntityType();
      if ($entity_type->isRevisionable() && $table = $entity_type->getRevisionTable()) {
        $entity_id_key = $entity_type->getKey('id');
        $entity_revision_user = $entity_type->getRevisionMetadataKey('revision_user');
        $entity_revision_created = $entity_type->getRevisionMetadataKey('revision_created');
        $data_set = \Drupal::database()
          ->select($table, 't')
          ->fields('t', [
            $entity_id_key,
            'vid',
            $entity_revision_user,
            $entity_revision_created,
          ])
          ->condition($entity_id_key, $entity_id)
          ->orderBy($entity_revision_created)
          ->execute()
          ->fetchAssoc();
        if ($data_set) {
          $user = $data_set[$entity_revision_user];
          $user = ((int) $user) ? $user : NULL;
        }
      }
      elseif (!$entity_type->isRevisionable() && $table = $entity_type->getDataTable()) {
        $entity_id_key = $entity_type->getKey('id');
        $owner_key = $entity_type->hasKey('owner')
          ? $entity_type->getKey('owner')
          : $entity_type->getKey('uid');
        if ($owner_key) {
          $data_set = \Drupal::database()
            ->select($table, 't')
            ->fields('t', [
              $entity_id_key,
              $owner_key,
            ])
            ->condition($entity_id_key, $entity_id)
            ->execute()
            ->fetchAssoc();
          if ($data_set) {
            $user = $data_set[$owner_key];
            $user = ((int) $user) ? $user : NULL;
          }
        }
      }
      if ($user && $return_user_entity) {
        /** @var \Drupal\user\UserStorage $user_storage */
        $user_storage = $this->entityTypeManager->getStorage('user');
        return $user_storage->load($user);
      }
    }
    catch (\Exception $exception) {
      \Drupal::logger('argue_user')->error($exception->getMessage());
    }

    return $user;
  }

  /**
   * Set the user receiving points.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity we are working on.
   * @param string $user_points_rule_id
   *   For error messages a rule reference is required.
   *
   * @return bool
   *   Returns if the user is set.
   */
  protected function setUser(EntityInterface $entity, string $user_points_rule_id = ''): bool {
    try {
      if (isset($this->rule['addressed_user']) && $this->rule['addressed_user'] === 'entity_creator') {
        // To enforce 'early fail', addressed user is identified immediately.
        $this->user = $this->getEntityCreator($entity->getEntityTypeId(), $entity->id(), TRUE);
        if (!$this->user) {
          $error_msg = "Userpoints rule '$user_points_rule_id' requires user 'entity_creator' on entity" .
            " {$entity->getEntityTypeId()}:{$entity->id()}. But the user could not be identified.";
          \Drupal::logger('argue_user')->error($error_msg);
          return FALSE;
        }
      }
      elseif ($user_id = $this->currentUser->id()) {
        $this->user = $this->entityTypeManager->getStorage('user')->load($user_id);
      }
      else {
        $error_msg = "For Userpoints rule '$user_points_rule_id' on entity" .
          " {$entity->getEntityTypeId()}:{$entity->id()} no user could be set.";
        \Drupal::logger('argue_user')->error($error_msg);
        return FALSE;
      }
      return !!$this->user;
    }
    catch (\Exception $e) {
      \Drupal::logger('argue_user')->error($e->getMessage());
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildRuleId(EntityInterface $entity, string $action) {
    return implode('__', [
      'entity_actions',
      "{$entity->getEntityTypeId()}_{$entity->bundle()}",
      $action,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildLogId(array $ids): string {
    foreach ($ids as $key => $id) {
      $ids[$key] = trim($id);
    }
    $combined_id = implode('][', $ids);
    return "[$combined_id]";
  }

  /**
   * Create a log message for userpoints.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Associated with user points to get log information.
   * @param string $action
   *   The the triggered userpoint action.
   * @param string $id
   *   The ID string that might be a query selector to find this entry.
   *
   * @return string
   *   Log message for inserting userpoints.
   */
  public function buildLogMessage(EntityInterface $entity, string $action, string $id): string {
    $label = $entity->label();
    $msg = $this->t("@action @type (@entity_id)@title", [
      '@action' => ucfirst($action),
      '@type' => $entity->bundle(),
      '@entity_id' => $entity->id(),
      '@title' => ($label) ? ": '$label'." : '',
    ])->render();
    return trim("$id \n$msg");
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
    if (!$this->actions) {
      $this->actions = $this->getPluginDefinition()['actions'] ?? [];
    }
    return $this->actions;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAction(string $action): bool {
    $actions = $this->getActions();
    return method_exists($this, $actions[$action]);
  }

  /**
   * {@inheritdoc}
   */
  public function getAction(string $action): ?string {
    $actions = $this->getActions();
    return ($this->hasAction($action)) ? $actions[$action] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultUserpointType(): string {
    return $this->getPluginDefinition()['default_userpoint_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function validates(string $action): bool {
    $default = $this->getValidationDefaults($action);
    $rule_config = $this->rule['config'] ?? [];
    $rule_config = array_merge($default, $rule_config);
    foreach ($rule_config as $key => $value) {
      switch ($key) {
        case 'repeat':
          if ($value >= 0 && count($this->getRepetitions()) > $value) {
            return FALSE;
          }
        default:
      }

    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidationDefaults(string $action): array {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['validation_defaults'][$action] ?? [];
  }

  /**
   * Get the repetitions of the same rule from userpoints history.
   *
   * @return array
   *   Array with all application of current rule.
   */
  protected function getRepetitions(): array {
    if (!$this->repetitions) {
      $this->repetitions = $this->database
        ->select('userpoints_revision', 't')
        ->fields('t')
        ->condition('revision_log', "%{$this->database->escapeLike($this->logId)}%", 'LIKE')
        ->condition('entity_type_id', $this->user->getEntityTypeId())
        ->condition('entity_id', $this->user->id())
        ->orderBy('vid')
        ->execute()
        ->fetchAllAssoc('vid');
    }
    return $this->repetitions;
  }

}

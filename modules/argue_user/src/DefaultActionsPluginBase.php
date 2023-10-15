<?php

namespace Drupal\argue_user;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\Connection;
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
abstract class DefaultActionsPluginBase extends PluginBase implements ArgueUserPointsPluginInterface, ContainerFactoryPluginInterface {

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
   * Service to receive configuration for the ruled behaviour.
   *
   * @var UserPointsActionRules
   */
  protected UserPointsActionRules $userPointsActionRules;

  /**
   * Actions provided by this plugin.
   *
   * @var array|null
   */
  protected ?array $actions = NULL;

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
   * The final validation config with overwritten defaults.
   *
   * @var array|null
   */
  protected ?array $validationConfig = NULL;

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
    if ($this->hasAction($action)) {
      if ($method = $this->getAction($action)) {
        $this->{$method}($action);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLogIdStack(string $action): ?array {
    return [
      'rule' => $this->buildRuleId($action),
      'entity_shortcut' => "{$this->entity->getEntityTypeId()}:{$this->entity->id()}",
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
  public function initRule(string $action): bool {
    if (!$this->rule) {
      $user_points_rule_id = $this->buildRuleId($action);
      if ($rule_content = $this->userPointsActionRules->getRule($user_points_rule_id)) {
        $this->rule = array_merge(['id' => $user_points_rule_id], $rule_content);
        if (!$this->setUser($user_points_rule_id)) {
          return FALSE;
        }
        if (!($log_id_stack = $this->getLogIdStack($action))) {
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
   * @param string $action
   *   The current action passed through as string.
   */
  protected function defaultAction(string $action): void {
    if ($this->initRule($action) && $this->validates($action)) {
      $log_msg = $this->buildLogMessage($action, $this->logId);
      $this->userpointsService->addPoints($this->rule['points'], $this->getDefaultUserpointType(), $this->user, $log_msg);
    }
  }

  /**
   * Set the user receiving points.
   *
   * @param string|null $rule_id
   *   Rule id for log message.
   *
   * @return bool
   *   Returns if the user is set.
   */
  protected function setUser(string $rule_id = NULL): bool {
    $is_current_user = (!isset($this->rule['addressed_user']) || $this->rule['addressed_user'] === 'current_user');
    if ($is_current_user && $user_id = $this->currentUser->id()) {
      try {
        $this->user = $this->entityTypeManager->getStorage('user')->load($user_id);
      }
      catch (\Exception $e) {
        \Drupal::logger('argue_user')->error($e->getMessage());
        return FALSE;
      }
    }
    if ($is_current_user && !$this->user) {
      $plugin_id = $rule_id ?? $this->getPluginId();
      $error_msg = "For UserpointsActionPlugin '$plugin_id' no user could be set.";
      \Drupal::logger('argue_user')->error($error_msg);
      return FALSE;
    }
    return !!$this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRuleId(string $action): string {
    return implode('__', [
      $this->getPluginId(),
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
   * @param string $action
   *   The triggered userpoint action.
   * @param string $id
   *   The ID string that might be a query selector to find this entry.
   *
   * @return string
   *   Log message for inserting userpoints.
   */
  public function buildLogMessage(string $action, string $id): string {
    $msg = $this->t("@action @type (@entity_id)@title", [
      '@action' => ucfirst($action),
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
    $validation_config = $this->getValidationConfig($action);
    foreach ($validation_config as $key => $value) {
      switch ($key) {
        case 'repeat':
          if ($value >= 0 && count($this->getRepetitions()) > $value) {
            return FALSE;
          }
          break;

        case 'earliest_repeat':
          if (!$this->validateEarliestRepeat($value)) {
            return FALSE;
          }
          break;

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
   * {@inheritdoc}
   */
  public function getValidationConfig(string $action): array {
    if (!$this->validationConfig) {
      $default = $this->getValidationDefaults($action);
      $rule_config = $this->rule['config'] ?? [];
      $this->validationConfig = array_merge($default, $rule_config);
    }
    return $this->validationConfig;
  }

  /**
   * Lazy getter for repetitions of the same rule from userpoints history.
   *
   * @return array
   *   Array with all application of current rule.
   */
  protected function getRepetitions(): array {
    if (!$this->repetitions) {
      $this->repetitions = $this->queryLog($this->logId, $this->user);
    }
    return $this->repetitions;
  }

  /**
   * Query userpoints revisions for given user and log_id.
   *
   * @param string $log_id
   *   The queried log_id.
   * @param \Drupal\user\UserInterface|null $user
   *   The user receiving userpoints on log_id (Must not be the current user).
   *
   * @return array
   *   Return assoc array of userpoints revisions keyed by version id.
   */
  protected function queryLog(string $log_id, UserInterface $user = NULL): array {
    $query = $this->database
      ->select('userpoints_revision', 't')
      ->fields('t')
      ->condition('revision_log', "%{$this->database->escapeLike($log_id)}%", 'LIKE')
      ->orderBy('vid');
    if ($user) {
      $query->condition('entity_type_id', $user->getEntityTypeId())
        ->condition('entity_id', $user->id());
    }
    return $query
      ->execute()
      ->fetchAllAssoc('vid');
  }

  /**
   * Checks if last repetition is older than the given time interval.
   *
   * @param int $interval
   *   Minimal time in seconds since last repetition.
   *
   * @return bool
   *   Returns FALSE if the last repetition is younger than <$value> in seconds.
   */
  public function validateEarliestRepeat(int $interval) {
    $time = \Drupal::time()->getRequestTime();
    $max_time = $time - $interval;
    foreach ($this->getRepetitions() as $repetition) {
      if ((int) $repetition->revision_timestamp > $max_time) {
        return FALSE;
      }
    }
    return TRUE;
  }

}

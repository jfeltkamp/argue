<?php

namespace Drupal\argue_user;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class EntityActionsUserPoints manages user points on base of entity actions.
 */
class UserPointsActionRules {

  use StringTranslationTrait;

  /**
   * Definition of ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Config objet for this service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Rules definition.
   *
   * @var array[]
   */
  protected array $rules;

  /**
   * Defines if dev mode is enabled to look up available but undefined rules.
   *
   * @var bool
   */
  protected bool $devMode;

  /**
   * Definition of the messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Constructs a new ArgumentStructuredListBuilder object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('argue_user.userpoints.rules');
    $this->rules = $this->config->get('userpoints_rules');
    $this->devMode = $this->config->get('dev_mode') ?? FALSE;
    $this->messenger = $messenger;
  }

  /**
   * @param string $user_points_action_id
   *   The look-up id.
   * @return array|null
   *   The looked up rule definition or NULL if this does not exists.
   */
  public function getRule(string $user_points_action_id): ?array {
    $rule = $this->rules[$user_points_action_id] ?? NULL;
    if ($this->devMode) {
      if ($rule) {
        $this->messenger->addStatus($this->t("Used action point rule '@rule' with following config: <pre>@conf</pre>", [
          '@rule' => $user_points_action_id,
          '@conf' => print_r($rule, TRUE),
        ]));
      }
      else {
        $this->messenger->addWarning($this->t('Action point rule "@rule" not in use.', [
          '@rule' => $user_points_action_id,
        ]));
      }
    }
    return $rule;
  }

}

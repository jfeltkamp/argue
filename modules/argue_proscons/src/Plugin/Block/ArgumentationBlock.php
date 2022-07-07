<?php

namespace Drupal\argue_proscons\Plugin\Block;

use Drupal\argue_proscons\ArgumentListService;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a 'ArgumentationBlock' block.
 *
 * @Block(
 *  id = "argumentation_block",
 *  admin_label = @Translation("Argumentation"),
 *  category = @Translation("Argue"),
 *  context_definitions = {
 *   "node" = @ContextDefinition("entity:node", label = @Translation("Current Node"))
 *  }
 * )
 */
class ArgumentationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;

  /**
   * Constructs a new ArgumentationBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  protected $argumentListService;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  public $currentUser;

  /**
   * ArgumentationBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\Core\Config\ConfigManager $config_manager
   * @param \Drupal\argue_proscons\ArgumentListService $argument_list_service
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManager $entity_type_manager,
    ConfigManager $config_manager,
    ArgumentListService $argument_list_service,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->configManager = $config_manager;
    $this->argumentListService = $argument_list_service;
    $this->currentUser = $current_user;
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
      $container->get('config.manager'),
      $container->get('argue_proscons.argument_list_service'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['introduction' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['introduction'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Introduction'),
      '#description' => '',
      '#default_value' => $this->configuration['introduction'],
      '#maxlength' => 128,
      '#size' => 60,
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['introduction'] = $form_state->getValue('introduction');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#attributes' => [
        'class' => ['argumentation-block'],
      ],
      'header' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['argumentation-header'],
        ],
        'primary_actions' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['argumentation-header--actions'],
          ],
        ],
      ],
      'content' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['argumentation-content'],
        ],
      ],
      '#cache' => [
        'tags' => ['argue_block'],
        'contexts' => ['user.roles:authenticated'],
      ],
    ];
    $node = $this->getContextValue('node');
    $node_types = $this->configManager->getConfigFactory()
      ->get('argue_proscons.settings')
      ->get('argue_proscons.argue_proscons_node_types');

    /** @var \Drupal\node\NodeInterface $node */
    if (!$node->isNew() && isset($node_types[$node->getType()]) && $node_types[$node->getType()]) {

      $reference_id = $node->id();

      if (isset($this->configuration['intro']) && $this->configuration['intro']) {
        $build['header']['argumentation_block_introduction'] = [
          '#markup' => '<div class="argumentation-header--intro">' . $this->configuration['introduction'] . '</div>',
        ];
      }

      $text = $this->t('Add Argument');

      $add_link = $this->argumentListService->getAddArgumentLink($reference_id, $text);
      if (isset($add_link['#attributes'])) {
        $add_link['#attributes']['class'] = [
          'button',
          'button--primary',
          'button--action',
        ];
        $build['header']['primary_actions']['add_link'] = $add_link;
      }

      // Sorter for arguements.
      $build['header']['argumentation_sorter'] = [
        '#markup' => '<div class="votejsr argumentation-header--sorter"
                           data-votejsr="sort"
                           data-sort-plugin="argument"
                           data-additional="ttl_res:created:changed"
                           data-context=".argue-proscons__col"></div>',
      ];

      $build['content']['argumentation_list'] = $this->argumentListService->render($reference_id);

      // Enable history automatism to mark as new.
      if (
        $this->argumentListService->moduleHandler->moduleExists('history')
        && $this->currentUser->isAuthenticated()
      ) {
        $build['content']['#attributes']['data-history-node-id'] = $reference_id;
        /* @ToDo find solution for comment module is not enabled. */
        $build['content']['#attached']['library'][] = 'comment/drupal.comment-new-indicator';
      }
    }

    return $build;
  }

}

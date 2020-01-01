<?php

namespace Drupal\argue_proscons\Plugin\Block;

use Drupal\argue_proscons\ArgumentListService;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Config\ConfigManager;

/**
 * Provides a 'ArgumentationBlock' block.
 *
 * @Block(
 *  id = "argumentation_block",
 *  admin_label = @Translation("Argumentation"),
 *  category = @Translation("Argue"),
 *  context = {
 *   "node" = @ContextDefinition(
 *    "entity:node",
 *    label = @Translation("Current Node")
 *   )
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

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManager $entity_type_manager,
    ConfigManager $config_manager,
    ArgumentListService $argument_list_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->configManager = $config_manager;
    $this->argumentListService = $argument_list_service;
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
      $container->get('argue_proscons.argument_list_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
         'introduction' => $this->t(''),
        ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['introduction'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Introduction'),
      '#description' => $this->t(''),
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
      '#cache' => [
        'tags' => ['argue_block']
      ]
    ];
    $node = $this->getContextValue('node');
    $node_types = $this->configManager->getConfigFactory()
      ->get('argue_proscons.settings')
      ->get('argue_proscons.argue_proscons_node_types');

    /* @var $node NodeInterface */
    if (!$node->isNew() && isset($node_types[$node->getType()]) && $node_types[$node->getType()]) {
      $build = [];
      $reference_id = $node->id();

      $build['argumentation_block_introduction'] = [
        '#markup' => '<p>' . $this->configuration['introduction'] . '</p>',
      ];

      $text = new TranslatableMarkup('<span class="material-icons mdc-fab__icon">add</span><span class="mdc-fab__label">Add Argument</span>');

      $add_link = $this->argumentListService->getAddArgumentLink($reference_id, $text);
      if (isset($add_link['#attributes'])) {
        $add_link['#attributes']['class'] = [
          'mdc-fab', 'mdc-fab--extended', 'mdc-ripple'
        ];
        $build['argumentation_block_introduction']['add_link'] = $add_link;
      }
      $build['argumentation_list'] = $this->argumentListService->render($reference_id);
    }

    return $build;
  }

}

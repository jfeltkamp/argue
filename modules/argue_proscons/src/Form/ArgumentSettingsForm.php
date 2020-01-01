<?php

namespace Drupal\argue_proscons\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ArgumentSettingsForm.
 *
 * @ingroup argue_proscons
 */
class ArgumentSettingsForm extends ConfigFormBase {

  /**
   * @var EntityTypeManager $entityTypeManager
   */
  var $entityTypeManager;

  /**
   * Config Manager.
   *
   * @var CacheBackendInterface
   */
  protected $cacheRender;

  /**
   * Constructs a new DefaultForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager, CacheBackendInterface $cache_render) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheRender = $cache_render;

  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('cache.render')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'argue_proscons.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'argue_proscons';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('argue_proscons.settings');
    $form['max_argument_name_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Max. Argument name length'),
      '#description' => $this->t('The max length of the title heading the Argument.'),
      '#default_value' => $config->get('argue_proscons.max_argument_name_length'),
    ];
    $form['max_argument_text_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Max. argument text length.'),
      '#description' => $this->t('The max length of the argument text body.'),
      '#default_value' => $config->get('argue_proscons.max_argument_text_length'),
    ];

    $node_types = [];
    foreach ($this->entityTypeManager->getStorage('node_type')->loadMultiple() as $node_type) {
      $node_types[$node_type->id()] = $node_type->label();
    }

    $form['argue_proscons_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Argue Node Types'),
      '#description' => $this->t('Enable Node types for argumentation.'),
      '#default_value' => $config->get('argue_proscons.argue_proscons_node_types'),
      '#options' => $node_types,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('argue_proscons.settings')
      ->set('argue_proscons.max_argument_name_length', $form_state->getValue('max_argument_name_length'))
      ->set('argue_proscons.max_argument_text_length', $form_state->getValue('max_argument_text_length'))
      ->set('argue_proscons.argue_proscons_node_types', $form_state->getValue('argue_proscons_node_types'))
      ->save();
    Cache::invalidateTags(['argument_list', 'argue_block']);
  }


}

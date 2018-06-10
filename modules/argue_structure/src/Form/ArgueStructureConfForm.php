<?php

namespace Drupal\argue_structure\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManager;

/**
 * Class ArgueStructureConfForm.
 */
class ArgueStructureConfForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ArgueStructureConfForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'argue_structure.arguestructureconf',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'argue_structure_conf_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('argue_structure.arguestructureconf');

    $options = $this->getVocabOptions();
    $form['argue_header_ov_page'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#attributes' => [],
      '0' => ['#markup' => $this->t('Overview Page')]
    ];

    $form['title_section_term_overview_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title rule overview page.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('title_section_term_overview_page'),
      '#description' => $this->t('Override title of the rule page. Leave empty to use the Vocabulary title.'),
    ];

    $form['description_section_term_overview_page'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description rule overview page.'),
      '#maxlength' => 256,
      '#cols' => 64,
      '#rows' => 3,
      '#default_value' => $config->get('description_section_term_overview_page'),
      '#description' => $this->t('Override description of the rule page. Leave empty to use the Vocabulary description.'),
    ];

    $form['link_section_terms_to_the_term_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link rule terms to the term page.'),
      '#default_value' => $config->get('link_section_terms_to_the_term_page'),
      '#description' => $this->t('Enable to link rules to their separate overview page.'),
    ];

    $form['argue_header_main_vocab'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#attributes' => [],
      '0' => ['#markup' => $this->t('Main content vocabulary')]
    ];

    $form['argue_vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Vocabulary containing the argued content.'),
      '#options' => $options,
      '#default_value' => $config->get('argue_vocabulary'),
      '#description' => $this->t('Change main vocabulary. (not recommended, default: sections )'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * @return array
   *   The options for the vocabulary.
   */
  protected function getVocabOptions() {
    $options = [];
    $vocabs = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadByProperties();
    foreach($vocabs as $vocab) {
      $options[$vocab->id()] = $vocab->label();
    };
    return $options;
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

    $this->config('argue_structure.arguestructureconf')
      ->set('argue_vocabulary', $form_state->getValue('argue_vocabulary'))
      ->set('title_section_term_overview_page', $form_state->getValue('title_section_term_overview_page'))
      ->set('description_section_term_overview_page', $form_state->getValue('description_section_term_overview_page'))
      ->set('link_section_terms_to_the_term_page', $form_state->getValue('link_section_terms_to_the_term_page'))
      ->save();
  }

}
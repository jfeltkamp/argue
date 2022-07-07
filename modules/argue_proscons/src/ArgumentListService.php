<?php

namespace Drupal\argue_proscons;

use Drupal\argue_proscons\Entity\Argument;
use Drupal\argue_proscons\Entity\ArgumentInterface;
use Drupal\argue_proscons\Events\ArgueEvent;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\vote\VotingApiService;

/**
 * Class ArgumentListService.
 */
class ArgumentListService {
  use StringTranslationTrait;

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  public $moduleHandler;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $entityTypeManager;

  /**
   * Information about the entity type 'argument'.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * Information about the entity type 'argument'.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilder
   */
  protected $entityViewBuilder;

  /**
   * Information about the entity type 'argument'.
   *
   * @var \Drupal\vote\VotingApiService
   */
  protected $voteResultManager;

  /**
   * Constructs a new ArgumentStructuredListBuilder object.
   *
   * @param ModuleHandler $module_handler
   * @param EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\vote\VotingApiService $vote_result_manager
   */
  public function __construct(ModuleHandler $module_handler, EntityTypeManagerInterface $entity_type_manager, VotingApiService $vote_result_manager) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->voteResultManager = $vote_result_manager;
    $this->storage = $this->entityTypeManager->getStorage('argument');
    $this->entityType = $this->storage->getEntityType();
    $this->entityViewBuilder = $this->entityTypeManager->getViewBuilder('argument');
  }

  /**
   * {@inheritdoc}
   */
  public function load($reference_id) {
    $entity_ids = $this->getEntityIds($reference_id);
    return $this->storage->loadMultiple($entity_ids);
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds($reference_id) {
    $query = $this
      ->storage->getQuery()
      ->condition('reference_id', $reference_id)
      ->sort($this->entityType->getKey('id'));

    return $query->execute();
  }

  /**
   * Get an Argument add link from reference_id.
   *
   * @param $reference_id integer
   *   The node id of referred node entity.
   * @param $text mixed
   *   The node id of referred node entity.
   *
   * @return array
   *   Render array with Link.
   */
  public function getAddArgumentLink($reference_id, TranslatableMarkup $text = NULL) {
    $return = [];
    if (\Drupal::currentUser()->hasPermission('add argument entities')) {
      $text = ($text) ?: $this->t('Add argument');
      $return = [
        '#type' => 'link',
        '#title' => $text,
        '#url' => new Url('entity.argument.add_form', ['reference_id' => $reference_id]),
        '#attributes' => [],
        '#cache' => [
          'contexts' => [
            'user',
          ],
        ],
      ];
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = $this->getDefaultOperations($entity);
    $operations += $this->moduleHandler->invokeAll('entity_operation', [$entity]);
    $this->moduleHandler->alter('entity_operation', $operations, $entity);
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $operations;
  }

  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   *
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = [];
    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $entity->toUrl('edit-form'),
      ];
    }
    if ($entity->access('view') && $entity->hasLinkTemplate('version-history')) {
      $operations['revision'] = [
        'title' => $this->t('Revisions'),
        'weight' => 50,
        'url' => $entity->toUrl('version-history'),
      ];
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $entity->toUrl('delete-form'),
      ];
    }

    return $operations;
  }

  /**
   * Builds a renderable list of operation links for the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which the linked operations will be performed.
   *
   * @return array
   *   A renderable array of operation links.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::buildRow()
   */
  public function buildOperations(EntityInterface $entity) {
    $build = [
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Builds the entity listing as renderable array for table.html.twig.
   */
  public function render($reference_id) {
    $build['argumentation'] = [
      '#theme' => 'argue_proscons',
      '#attributes' => ['id' => 'argue-proscons'],
      'pro_title' => [
        '#theme' => 'argue_proscons__header',
        '#attributes' => [],
        '#label' => $this->t('Pro Arguments'),
        '#type' => 'pro_arguments'
      ],
      'con_title' =>[
        '#theme' => 'argue_proscons__header',
        '#attributes' => [],
        '#label' => $this->t('Contra Arguments'),
        '#type' => 'con_arguments'
      ],
      'pro' => [],
      'con' => [],
      '#empty' => $this->t('There is no argument yet.'),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
    foreach ($this->load($reference_id) as $entity) {
      /* @var $entity Argument */
      if ($item = $this->buildItem($entity)) {
        switch ($entity->get('type')->getString()) {
          case ArgueEvent::ARGUE_PRO:
            $build['argumentation']['pro'][$entity->id()] = $item;
            break;
          case ArgueEvent::ARGUE_CON:
            $build['argumentation']['con'][$entity->id()] = $item;
            break;
        };
      }
    }

    return $build;
  }

  /**
   * Get the absolute weight of an item by voting api.
   *
   * @param int $entity_id
   *   The entity id to request.
   *
   * @return int
   *   Returns weight calculated by vote result (not related to other entities).
   */
  public function getWeight(int $entity_id) {
    $results = $this->voteResultManager->getResults('argument', $entity_id, TRUE);
    return (isset($results["ttl_res"]["abs"])) ? (int) round($results["ttl_res"]["abs"] * -100) : 0;
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for this row of the list.
   *
   * @return array
   *   A render array structure of fields for this entity.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::render()
   */
  public function buildItem(ArgumentInterface $entity) {
    $item = $this->entityViewBuilder->view($entity, 'teaser');
    $item['operations'] = $this->buildOperations($entity);
    $item['#weight'] = $this->getWeight($entity->id());

    return $item;
  }


}

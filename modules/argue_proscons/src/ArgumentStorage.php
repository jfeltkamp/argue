<?php

namespace Drupal\argue_proscons;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\argue_proscons\Entity\ArgumentInterface;

/**
 * Defines the storage handler class for Argument entities.
 *
 * This extends the base storage class, adding required special handling for
 * Argument entities.
 *
 * @ingroup argue_proscons
 */
class ArgumentStorage extends SqlContentEntityStorage implements ArgumentStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ArgumentInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {argument_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {argument_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ArgumentInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {argument_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('argument_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}

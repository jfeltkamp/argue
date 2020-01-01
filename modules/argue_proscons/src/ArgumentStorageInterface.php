<?php

namespace Drupal\argue_proscons;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface ArgumentStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Argument revision IDs for a specific Argument.
   *
   * @param \Drupal\argue_proscons\Entity\ArgumentInterface $entity
   *   The Argument entity.
   *
   * @return int[]
   *   Argument revision IDs (in ascending order).
   */
  public function revisionIds(ArgumentInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Argument author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Argument revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\argue_proscons\Entity\ArgumentInterface $entity
   *   The Argument entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ArgumentInterface $entity);

  /**
   * Unsets the language for all Argument with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}

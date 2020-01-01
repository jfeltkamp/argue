<?php

namespace Drupal\argue_proscons\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Argument entities.
 *
 * @ingroup argue_proscons
 */
interface ArgumentInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Argument name.
   *
   * @return string
   *   Name of the Argument.
   */
  public function getName();

  /**
   * Sets the Argument name.
   *
   * @param string $name
   *   The Argument name.
   *
   * @return \Drupal\argue_proscons\Entity\ArgumentInterface
   *   The called Argument entity.
   */
  public function setName($name);

  /**
   * Gets the Argument creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Argument.
   */
  public function getCreatedTime();

  /**
   * Sets the Argument creation timestamp.
   *
   * @param int $timestamp
   *   The Argument creation timestamp.
   *
   * @return \Drupal\argue_proscons\Entity\ArgumentInterface
   *   The called Argument entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Argument published status indicator.
   *
   * Unpublished Argument are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Argument is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Argument.
   *
   * @param bool $published
   *   TRUE to set this Argument to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\argue_proscons\Entity\ArgumentInterface
   *   The called Argument entity.
   */
  public function setPublished($published);

  /**
   * Gets the Argument revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Argument revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\argue_proscons\Entity\ArgumentInterface
   *   The called Argument entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Argument revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Argument revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\argue_proscons\Entity\ArgumentInterface
   *   The called Argument entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Returns type of argument (pro/con) as string.
   *
   * @return string
   *   Returns type as a string.
   */
  public function getTypeStr();

  /**
   * Returns type of argument (pro/con) as integer.
   *
   * @return integer
   *   Type id defined as constant in ArgueEvent.
   */
  public function getType();

  /**
   * @param $type int
   *   Type of argument defined as constant in ArgueEvent.
   *
   * @return ArgumentInterface
   *   Returns full instance.
   */
  public function setType($type);

  /**
   * @return int
   *   Returns the entity id of the node this argument belongs to.
   */
  public function getReferenceId();

  /**
   * @param $reference_id int
   *   The entity id of the node this argument belongs to.
   *
   * @return ArgumentInterface
   *   Instance of this.
   */
  public function setReferenceId($reference_id);

}

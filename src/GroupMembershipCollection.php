<?php

namespace Drupal\group;

use Drupal\group\GroupMembership;
use Drupal\group\Entity\GroupContentInterface;

/**
 * A representation of a set of GroupMembership instances.
 */
class GroupMembershipCollection implements \IteratorAggregate, \Countable {

  /**
   * @var GroupMembership[]
   */
  private $memberships = array();

  /**
   * Gets the current GroupMembershipCollection as an Iterator that includes all
   * memberships.
   *
   * It implements \IteratorAggregate.
   *
   * @see all()
   *
   * @return \ArrayIterator An \ArrayIterator object for iterating over
   * memberships.
   */
  public function getIterator() {
    return new \ArrayIterator($this->memberships);
  }

  /**
   * Gets the number of memberships in this collection.
   *
   * @return int The number of memberships.
   */
  public function count() {
    return count($this->memberships);
  }

  /**
   * Adds a group membership to the collection.
   *
   * @param GroupMembership $membership
   *   A group membership instance.
   */
  public function add(GroupMembership $membership) {
    $uuid = $membership->getGroupContent()->uuid();
    unset($this->memberships[$uuid]);

    $this->memberships[$uuid] = $membership;
  }

  /**
   * Adds a membership.
   *
   * @param GroupContentInterface $membership
   *   A membership group content instance.
   */
  public function addGroupContent(GroupContentInterface $group_content) {
    $this->add(new GroupMembership($group_content));
  }

  /**
   * Adds memberships.
   *
   * @param GroupContentInterface[] $group_contents
   *   A set of membership group content instances.
   */
  public function addGroupContents(array $group_contents) {
    foreach ($group_contents as $group_content) {
      $this->addGroupContent($group_content);
    }
  }

  /**
   * Returns all memberships in this collection keyed by UUID.
   *
   * @return GroupMembership[] An array of memberships keyed by UUID.
   */
  public function all() {
    return $this->memberships;
  }

  /**
   * Returns all memberships in this collection.
   *
   * @return GroupMembership[] An array of memberships.
   */
  public function allValues() {
    return array_values($this->memberships);
  }

  /**
   * Gets a membership by UUID.
   *
   * @param string $uuid The membership UUID.
   *
   * @return GroupMembership|null
   *   A GroupMembership instance or null when not found.
   */
  public function get($uuid) {
    return isset($this->memberships[$uuid]) ? $this->memberships[$name] : null;
  }

  /**
   * Removes a membership or an array of memberships by UUID from the
   * collection.
   *
   * @param string|array $uuid The membership UUID or an array of membership
   *   UUIDs.
   */
  public function remove($uuid) {
    foreach ((array) $uuid as $id) {
      unset($this->memberships[$id]);
    }
  }

  /**
   * Adds a group membership collection at the end of the current set by
   * appending all group memberships of the added collection.
   *
   * @param GroupMembershipCollection $collection
   *   A GroupMembershipCollection instance.
   */
  public function addCollection(GroupMembershipCollection $collection) {
    // We need to remove all memberships with the same UUIDs first because just
    // replacing them would not place the new membership at the end of the
    // merged array.
    foreach ($collection->all() as $uuid => $membership) {
      unset($this->memberships[$uuid]);
      $this->memberships[$uuid] = $membership;
    }
  }

}
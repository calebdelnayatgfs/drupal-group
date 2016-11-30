<?php

namespace Drupal\group;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipCollection;
use Symfony\Component\EventDispatcher\Event;

/**
 * An event dispatched when a group's user membership is being loaded.
 * Subscribers may affect the group membership by modifying the
 * GroupMembershipCollection returned by getMemberships().
 *
 * @see \Drupal\group\GroupMembershipLoader::loadByGroup()
 */
class GroupMembershipLoaderByGroupEvent extends Event {

  /**
   * The group for which memberships are being loaded.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The memberships for the event.
   *
   * @var \Drupal\group\GroupMembershipCollection
   */
  protected $memberships;

  /**
   * The roles for which the user's memberships were filtered.
   *
   * @var string[]
   */
  protected $roles;

  /**
   * Constructs a new event instance.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for this event.
   * @param string[] $roles
   *   The roles for which the user's memberships were filtered.
   * @param \Drupal\group\GroupMembershipCollection $memberships
   *   The loaded memberships for the user.
   */
  public function __construct(GroupInterface $group, array $roles, GroupMembershipCollection $memberships) {
    $this->group = $group;
    $this->memberships = $memberships;
    $this->roles = $roles;
  }

  /**
   * Get the group for which membership is being loaded.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The group for which membership is being loaded.
   */
  public function getGroup() {
    return $this->group;
  }

  /**
   * Get the memberships for the group to which the event corresponds.
   *
   * @return \Drupal\group\GroupMembershipCollection
   *   The memberships for the group.
   */
  public function getMemberships() {
    return $this->memberships;
  }

  /**
   * Get the roles for which the user's memberships were filtered.
   *
   * @return string[]
   *   An array of roles used to filter the user's loaded memberships.
   */
  public function getRoles() {
    return $this->roles;
  }

}
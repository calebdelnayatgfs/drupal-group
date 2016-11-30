<?php

namespace Drupal\group;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipCollection;
use Symfony\Component\EventDispatcher\Event;

/**
 * An event dispatched when a user's group membership is being loaded for a
 * specific group. Subscribers may affect the group membership by modifying the
 * GroupMembershipCollection returned by getMemberships().
 *
 * @see \Drupal\group\GroupMembershipLoader::load()
 */
class GroupMembershipLoaderByUserGroupEvent extends Event {

  /**
   * The user for the event.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The memberships for the event.
   *
   * @var \Drupal\group\GroupMembershipCollection
   */
  protected $memberships;

  /**
   * The group for the event.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Constructs a new event instance.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for this event.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for this event.
   * @param \Drupal\group\GroupMembershipCollection $memberships
   *   The existing memberships for this event.
   */
  public function __construct(GroupInterface $group, AccountInterface $account, GroupMembershipCollection $memberships) {
    $this->group = $group;
    $this->account = $account;
    $this->memberships = $memberships;
  }

  /**
   * Get the user for which membership is being loaded.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user for which membership is being loaded.
   */
  public function getUser() {
    return $this->account;
  }

  /**
   * Get the memberships for the user to which the event corresponds.
   *
   * @return \Drupal\group\GroupMembershipCollection
   *   The memberships for the user.
   */
  public function getMemberships() {
    return $this->memberships;
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

}
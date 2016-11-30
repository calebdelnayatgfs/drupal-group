<?php

namespace Drupal\group;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\GroupMembershipCollection;
use Symfony\Component\EventDispatcher\Event;

/**
 * An event dispatched when a user's group membership is being loaded.
 * Subscribers may affect the group membership by modifying the
 * GroupMembershipCollection returned by getMemberships().
 *
 * @see \Drupal\group\GroupMembershipLoader::loadByUser()
 */
class GroupMembershipLoaderByUserEvent extends Event {

  /**
   * The user for which memberships are being loaded.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The loaded memberships for the user.
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
   * Constructs a new GroupMembershipLoaderByUserEvent object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which memberships are being loaded.
   * @param string[] $roles
   *   The roles for which the user's memberships were filtered.
   * @param \Drupal\group\GroupMembershipCollection $memberships
   *   The loaded memberships for the user.
   */
  public function __construct(AccountInterface $account, array $roles, GroupMembershipCollection $memberships) {
    $this->account = $account;
    $this->memberships = $memberships;
    $this->roles = $roles;
  }

  /**
   * Get the user for which memberships are being loaded.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user for which memberships are being loaded.
   */
  public function getUser() {
    return $this->account;
  }

  /**
   * Get the memberships for the user to which the event corresponds.
   *
   * @return \Drupal\group\MembreshipCollection
   *   The collection of memberships for the user.
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
<?php

namespace Drupal\group;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoaderEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Generates and caches the permissions hash for a group membership.
 */
class GroupMembershipLoader implements GroupMembershipLoaderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user's account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new GroupTypeController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\EventDispatcher\EventDispatchInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Gets the group content storage.
   *
   * @return \Drupal\group\Entity\Storage\GroupContentStorageInterface
   */
  protected function groupContentStorage() {
    return $this->entityTypeManager->getStorage('group_content');
  }

  /**
   * {@inheritdoc}
   */
  public function load(GroupInterface $group, AccountInterface $account) {
    $filters = ['entity_id' => $account->id()];
    $group_contents = $this->groupContentStorage()->loadByGroup($group, 'group_membership', $filters);
    $memberships = new GroupMembershipCollection();
    $memberships->addGroupContents($group_contents);

    $event = new GroupMembershipLoaderByUserGroupEvent($group, $account, $memberships);
    $this->eventDispatcher->dispatch(GroupMembershipLoaderEvents::ALTER_BY_USER_GROUP, $event);
    $group_memberships = $memberships->allValues();

    return $group_memberships ? reset($group_memberships) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByGroup(GroupInterface $group, $roles = NULL) {
    $filters = [];
    
    if (isset($roles)) {
      $filters['group_roles'] = (array) $roles;
    }

    /** @var \Drupal\group\Entity\GroupContentInterface[] $group_contents */
    $group_contents = $this->groupContentStorage()->loadByGroup($group, 'group_membership', $filters);
    $memberships = new GroupMembershipCollection();
    $memberships->addGroupContents($group_contents);

    $event = new GroupMembershipLoaderByGroupEvent($group, !is_null($roles) ? $roles : [], $memberships);
    $this->eventDispatcher->dispatch(GroupMembershipLoaderEvents::ALTER_BY_GROUP, $event);

    return $memberships->allValues();
  }

  /**
   * {@inheritdoc}
   */
  public function loadByUser(AccountInterface $account = NULL, $roles = NULL) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }

    // Load all group content types for the membership content enabler plugin.
    $group_content_types = $this->entityTypeManager
      ->getStorage('group_content_type')
      ->loadByProperties(['content_plugin' => 'group_membership']);

    // If none were found, there can be no memberships either.
    if (empty($group_content_types)) {
      return [];
    }

    // Try to load all possible membership group content for the user.
    $group_content_type_ids = [];
    foreach ($group_content_types as $group_content_type) {
      $group_content_type_ids[] = $group_content_type->id();
    }

    $properties = ['type' => $group_content_type_ids, 'entity_id' => $account->id()];
    if (isset($roles)) {
      $properties['group_roles'] = (array) $roles;
    }

    /** @var \Drupal\group\Entity\GroupContentInterface[] $group_contents */
    $group_contents = $this->groupContentStorage()->loadByProperties($properties);
    $memberships = new GroupMembershipCollection();
    $memberships->addGroupContents($group_contents);

    $event = new GroupMembershipLoaderByUserEvent($account, !is_null($roles) ? $roles : [], $memberships);
    $this->eventDispatcher->dispatch(GroupMembershipLoaderEvents::ALTER_BY_USER, $event);

    return $memberships->allValues();
  }

}

<?php

namespace Drupal\ggroup;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ggroup\GroupHierarchyManagerInterface;
use Drupal\group\GroupMembershipCollection;
use Drupal\group\GroupMembershipLoaderEvents;
use Drupal\group\GroupMembershipLoaderByGroupEvent;
use Drupal\group\GroupMembershipLoaderByUserEvent;
use Drupal\group\GroupMembershipLoaderByUserGroupEvent;
use Drupal\group\Entity\GroupContent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to group membership loader events to add indirect memberships to
 * groups based on group hierarchy.
 */
class GroupMembershipSubscriber implements EventSubscriberInterface {

  /**
   * The group hierarchy manager.
   *
   * @var \Drupal\ggroup\GroupHierarchyManagerInterface
   */
  protected $groupHierarchyManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GroupMembershipSubscriber instance.
   *
   * @param \Drupal\ggroup\GroupHierarchyManagerInterface $group_hierarchy_manager
   *   The group hierarchy manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(GroupHierarchyManager $group_hierarchy_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->groupHierarchyManager = $group_hierarchy_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[GroupMembershipLoaderEvents::ALTER_BY_GROUP] = 'onAlterMembershipsByGroup';
    $events[GroupMembershipLoaderEvents::ALTER_BY_USER] = 'onAlterMembershipsByUser';
    $events[GroupMembershipLoaderEvents::ALTER_BY_USER_GROUP] = 'onAlterMembershipsByUserAndGroup';

    return $events;
  }

  /**
   * Adds additional memberships to the membership collection based on group
   * hierarchy. Finds all memberships in the event group's subgroups and adds
   * corresponding indirect memberships (as unsaved group content entities) to
   * the membership collection for the group.
   *
   * @param GroupMembershipLoaderByGroupEvent $event
   *   The event data, including the group and the group's direct memberships.
   */
  public function onAlterMembershipsByGroup(GroupMembershipLoaderByGroupEvent $event) {
    $memberships = $event->getMemberships();
    $group = $event->getGroup();
    $plugin = $group->getGroupType()->getContentPlugin('group_membership');
    $subgroup_ids = $this->groupHierarchyManager->getGroupSubgroupIds($group);

    if (empty($subgroup_ids)) {
      return;
    }

    // Load all group content types for the membership content enabler plugin.
    $group_content_types = $this->entityTypeManager
      ->getStorage('group_content_type')
      ->loadByProperties(['content_plugin' => 'group_membership']);

    // If none were found, there can be no memberships either.
    if (empty($group_content_types)) {
      return [];
    }

    // Try to load all possible membership group content.
    $group_content_type_ids = [];
    foreach ($group_content_types as $group_content_type) {
      $group_content_type_ids[] = $group_content_type->id();
    }

    $properties = ['type' => $group_content_type_ids, 'gid' => $subgroup_ids];

    /** @var \Drupal\group\Entity\GroupContentInterface[] $group_contents */
    $group_contents = $this->entityTypeManager
      ->getStorage('group_content')
      ->loadByProperties($properties);

    $existing_membership_users = [];

    foreach ($memberships as $membership) {
      $existing_membership_users[] = $membership->getUser()->id();
    }

    foreach ($group_contents as $group_content) {
      $user_id = $group_content->getEntity()->id();

      // Don't want to create another indirect group membership instance if the
      // user is already a member.
      // @todo Fold indirect membership roles into the existing membership
      // objects once roles are being mapped properly.
      if (in_array($user_id, $existing_membership_users)) {
        continue;
      }

      $existing_membership_users[] = $user_id;

      $properties = [
        'type' => $plugin->getContentTypeConfigId(),
        'gid' => $group->id(),
        'entity_id' => $user_id,
      ];

      $membership = GroupContent::create($properties);
      $memberships->addGroupContent($membership);
    }
  }

  /**
   * Adds additional memberships to the membership collection based on group
   * hierarchy. Uses all memberships of the event user to find supergroups of
   * those membership groups and then adds corresponding indirect memberships
   * (as unsaved group content entities) to the membership collection for the
   * user.
   *
   * @param GroupMembershipLoaderByGroupEvent $event
   *   The event data, including the user and the user's direct memberships.
   */
  public function onAlterMembershipsByUser(GroupMembershipLoaderByUserEvent $event) {
    $user = $event->getUser();
    $memberships = $event->getMemberships();
    $supergroup_ids = [];

    foreach ($memberships as $membership) {
      $group = $membership->getGroup();
      $supergroup_ids = array_merge($supergroup_ids, $this->groupHierarchyManager->getGroupSupergroupIds($group));
    }

    $supergroup_ids = array_unique($supergroup_ids);

    if (empty($supergroup_ids)) {
      return;
    }

    $supergroups = $this->entityTypeManager
      ->getStorage('group')
      ->loadMultiple($supergroup_ids);

    foreach ($supergroups as $supergroup_id => $supergroup) {
      $properties = [
        'type' => $supergroup->getGroupType()->getContentPlugin('group_membership')->getContentTypeConfigId(),
        'gid' => $supergroup_id,
        'entity_id' => $user->id(),
      ];

      $membership = GroupContent::create($properties);
      $memberships->addGroupContent($membership);
    }
  }

  /**
   * Adds additional memberships to the membership collection based on group
   * hierarchy. Uses the event user and event group load direct memberships for
   * the user then looks for the given group among the supergroups of those user
   * memberships. If the group is found, an indirect membership (as an unsaved
   * group content entity) is added to the membership collection for the user.
   *
   * @param GroupMembershipLoaderByUserGroupEvent $event
   *   The event data, including user, group, and direct membership.
   */
  public function onAlterMembershipsByUserAndGroup(GroupMembershipLoaderByUserGroupEvent $event) {
    // @todo Most of this method is code copied from
    // GroupMembershipLoader::loadByUser() and should be refactored.

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

    $properties = [
      'type' => $group_content_type_ids,
      'entity_id' => $event->getUser()->id()
    ];

    /** @var \Drupal\group\Entity\GroupContentInterface[] $group_contents */
    $group_contents = $this->entityTypeManager
      ->getStorage('group_content')
      ->loadByProperties($properties);

    $supergroup_ids = [];
    $supergroup_id = $event->getGroup()->id();

    foreach ($group_contents as $group_content) {
      $group = $group_content->getGroup();
      $supergroup_ids = array_merge($supergroup_ids, $this->groupHierarchyManager->getGroupSupergroupIds($group));
    }

    $supergroup_ids = array_unique($supergroup_ids);

    if (!in_array($supergroup_id, $supergroup_ids)) {
      return;
    }

    $supergroup = $this->entityTypeManager
      ->getStorage('group')
      ->load($supergroup_id);

    $properties = [
      'type' => $supergroup->getGroupType()->getContentPlugin('group_membership')->getContentTypeConfigId(),
      'gid' => $supergroup_id,
      'entity_id' => $event->getUser()->id(),
    ];

    $membership = GroupContent::create($properties);
    $event->getMemberships()->addGroupContent($membership);
  }

}

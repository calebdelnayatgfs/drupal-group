<?php

namespace Drupal\group;

/**
 * Contains all events dispatched by the group membership loader.
 */
final class GroupMembershipLoaderEvents {

  /**
   * This event gives modules the opportunity to alter group memberships that
   * were loaded by group.
   */
  const ALTER_BY_GROUP = 'group.membership_loader.alter_by_group';

  /**
   * This event gives modules the opportunity to alter group memberships that
   * were loaded by user.
   */
  const ALTER_BY_USER = 'group.membership_loader.alter_by_user';

  /**
   * This event gives modules the opportunity to alter group memberships that
   * were loaded by the combination of group and user.
   */
  const ALTER_BY_USER_GROUP = 'group.membership_loader.alter_by_user_group';

}
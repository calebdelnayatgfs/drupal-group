<?php

namespace Drupal\ggroup;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContentInterface;

/**
 * An interface for the group hierarchy manager.
 */
interface GroupHierarchyManagerInterface {

  /**
   * Relates one group to another as a subgroup.
   *
   * @param GroupContentInterface $group_content
   *   The group content representing the subgroup relationship.
   */
  public function addSubgroup(GroupContentInterface $group_content);

  /**
   * Removes the relationship of a subgroup.
   *
   * @param GroupContentInterface $group_content
   *   The group content representing the subgroup relationship.
   */
  public function removeSubgroup(GroupContentInterface $group_content);
  
  /**
   * Checks if a given group has another group as a subgroup anywhere in its
   * descendent subgroups.
   *
   * @param GroupInterface $group
   *   The parent group whose subgroups will be checked.
   * @param GroupInterface $subgroup
   *   The subgroup that will be searched for within the parent group's
   *   subgroups.
   * @return bool
   *   TRUE if the given group has the given subgroup, or FALSE if not.
   */
  public function groupHasSubgroup(GroupInterface $group, GroupInterface $subgroup);

  /**
   * Loads the subgroups of a given group.
   *
   * @param GroupInterface $group
   *   The group for which subgroups will be loaded.
   * @return GroupInterface[]
   *   An array of subgroups for the given group.
   */
  public function getGroupSubgroups(GroupInterface $group);

  /**
   * Gets the IDs of the subgroups of a given group.
   *
   * @param GroupInterface $group
   *   The group for which subgroups will be loaded.
   * @return int[]
   *   An array of subgroup IDs for the given group.
   */
  public function getGroupSubgroupIds(GroupInterface $group);

  /**
   * Loads the supergroups of a given group.
   *
   * @param GroupInterface $group
   *   The group for which supergroups will be loaded.
   * @return GroupInterface[]
   *   An array of supergroups for the given group.
   */
  public function getGroupSupergroups(GroupInterface $group);

  /**
   * Gets the IDs of the supergroups of a given group.
   *
   * @param GroupInterface $group
   *   The group for which supergroups will be loaded.
   * @return int[]
   *   An array of supergroup IDs for the given group.
   */
  public function getGroupSupergroupIds(GroupInterface $group);

}
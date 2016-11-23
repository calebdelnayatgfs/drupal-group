<?php

namespace Drupal\ggroup\Graph;

/**
 * An interface for defining the storage of group relationships as a graph.
 */
interface GroupGraphStorageInterface {

  /**
   * Relates group A and group B such that group B will be a child of group A.
   * Inferred relationships based on existing relationships to group A and
   * group B will also be created.
   *
   * @param int $a
   *   The ID of the parent group.
   * @param int $b
   *   The ID of the child group.
   * @return int
   *   The ID of the graph edge relating parent group A to child group B.
   */
  public function addEdge($a, $b);

  /**
   * Removes the relationship between group A and group B. Group B will no
   * longer be a child of group A. Inferred relationships based on existing
   * relationships to group A and group B will also be removed.
   *
   * @param int $a
   *   The ID of the parent group.
   * @param int $b
   *   The ID of the child group.
   */
  public function removeEdge($a, $b);

  /**
   * Gets all descendant child groups of the given parent group.
   *
   * @param int $group_id
   *   The parent group ID.
   * @return int[]
   *   An array of descendant child group IDs.
   */
  public function getDescendants($group_id);

  /**
   * Gets all ancestor parent groups of the given child group.
   *
   * @param int $group_id
   *   The child group ID.
   * @return int[]
   *   An array of ancestor parent group IDs.
   */
  public function getAncestors($group_id);

  /**
   * Checks if a group (group A) is the ancestor of another group (group B).
   *
   * @param int $a
   *   The group whose ancestry status will be checked.
   * @param int $b
   *   The group for which ancestry status will be checked against.
   * @return bool
   *   TRUE if group A is an ancestor of group B.
   */
  public function isAncestor($a, $b);

  /**
   * Checks if a group (group A) is the descendant of another group (group B).
   *
   * @param int $a
   *   The group whose descent status will be checked.
   * @param int $b
   *   The group for which descent status will be checked against.
   * @return bool
   *   TRUE if group A is an descendant of group B.
   */
  public function isDescendant($a, $b);
}
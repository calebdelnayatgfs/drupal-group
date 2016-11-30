<?php

namespace Drupal\ggroup;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ggroup\Graph\GroupGraphStorageInterface;
use Drupal\ggroup\GroupHierarchyManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContentInterface;

/**
 * Manages the relationship between groups (as subgroups).
 */
class GroupHierarchyManager implements GroupHierarchyManagerInterface {

  /**
   * The group graph storage service.
   *
   * @var \Drupal\ggroup\Graph\GroupGraphStorageInterface
   */
  protected $groupGraphStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GroupHierarchyManager.
   *
   * @param \Drupal\ggroup\Graph\GroupGraphStorageInterface $group_graph_storage
   *   The group graph storage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(GroupGraphStorageInterface $group_graph_storage, EntityTypeManagerInterface $entity_type_manager) {
    $this->groupGraphStorage = $group_graph_storage;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function groupHasSubgroup(GroupInterface $group, GroupInterface $subgroup) {
    return $this->groupGraphStorage->isDescendant($subgroup->id(), $group->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupSubgroups(GroupInterface $group) {
    $subgroup_ids = $this->getGroupSubgroupIds($group);
    return $this->entityTypeManager->getStorage('group')->loadMultiple($subgroup_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupSubgroupIds(GroupInterface $group) {
    return $this->groupGraphStorage->getDescendants($group->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupSupergroups(GroupInterface $group) {
    $subgroup_ids = $this->getGroupSupergroupIds($group);
    return $this->entityTypeManager->getStorage('group')->loadMultiple($subgroup_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupSupergroupIds(GroupInterface $group) {
    return $this->groupGraphStorage->getAncestors($group->id());
  }

  /**
   * {@inheritdoc}
   */
  public function addSubgroup(GroupContentInterface $group_content) {
    $plugin = $group_content->getContentPlugin();

    if ($plugin->getEntityTypeId() !== 'group') {
      throw new \InvalidArgumentException('Given group content entity does not represent a subgroup relationship.');
    }

    $parent_group = $group_content->getGroup();
    /** @var \Drupal\group\Entity\GroupInterface $child_group */
    $child_group = $group_content->getEntity();

    if ($parent_group->id() === NULL) {
      throw new \InvalidArgumentException('Parent group must be saved before it can be related to another group.');
    }

    if ($child_group->id() === NULL) {
      throw new \InvalidArgumentException('Child group must be saved before it can be related to another group.');
    }

    $new_edge_id = $this->groupGraphStorage->addEdge($parent_group->id(), $child_group->id());

    // @todo Invalidate some kind of cache?
  }

  /**
   * {@inheritdoc}
   */
  public function removeSubgroup(GroupContentInterface $group_content) {
    $plugin = $group_content->getContentPlugin();

    if ($plugin->getEntityTypeId() !== 'group') {
      throw new \InvalidArgumentException('Given group content entity does not represent a subgroup relationship.');
    }

    $parent_group = $group_content->getGroup();
    /** @var \Drupal\group\Entity\GroupInterface $child_group */
    $child_group = $group_content->getEntity();

    $this->groupGraphStorage->removeEdge($parent_group->id(), $child_group->id());

    // @todo Invalidate some kind of cache?
  }

}
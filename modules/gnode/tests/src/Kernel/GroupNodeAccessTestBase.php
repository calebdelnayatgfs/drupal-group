<?php

namespace Drupal\Tests\gnode\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Test base for testing access records and grants for group nodes.
 */
abstract class GroupNodeAccessTestBase extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['group', 'node', 'gnode'];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The account to use for retrieving the grants.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * A dummy group type with ID 'a'.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeA;

  /**
   * A dummy group type with ID 'b'.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeB;

  /**
   * A dummy group of type 'a' with the test account as a member.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupA1;

  /**
   * A dummy group of type 'a' with the test account as an outsider.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupA2;

  /**
   * A dummy group of type 'b' with the test account as a member.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupB1;

  /**
   * A dummy group of type 'b' with the test account as an outsider.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupB2;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->installConfig(['group', 'node']);
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');

    // Create the test user account.
    $this->account = $this->createUser(['uid' => 2]);

    // Create some group types.
    $storage = $this->entityTypeManager->getStorage('group_type');
    $values = ['label' => 'foo', 'description' => 'bar'];
    $this->groupTypeA = $storage->create(['id' => 'a'] + $values);
    $this->groupTypeB = $storage->create(['id' => 'b'] + $values);
    $this->groupTypeA->save();
    $this->groupTypeB->save();

    // Create some node types.
    $storage = $this->entityTypeManager->getStorage('node_type');
    $values = ['name' => 'foo', 'description' => 'bar'];
    $storage->create(['type' => 'a'] + $values)->save();
    $storage->create(['type' => 'b'] + $values)->save();
    $storage->create(['type' => 'c'] + $values)->save();

    // Install some node types on some group types.
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->createFromPlugin($this->groupTypeA, 'group_node:a')->save();
    $storage->createFromPlugin($this->groupTypeA, 'group_node:b')->save();
    $storage->createFromPlugin($this->groupTypeB, 'group_node:b')->save();

    // Set group_node permissions on the group types.
    $member_a = ['view a node', 'view b node', 'update own a node', 'delete own a node'];
    $member_b = ['update any b node', 'delete any b node'];
    $outsider_a = ['view a node', 'update any a node', 'delete any a node'];
    $outsider_b = ['view b node', 'update own b node', 'delete own b node'];
    $this->groupTypeA->getMemberRole()->grantPermissions($member_a)->save();
    $this->groupTypeB->getMemberRole()->grantPermissions($member_b)->save();
    $this->groupTypeA->getOutsiderRole()->grantPermissions($outsider_a)->save();
    $this->groupTypeB->getOutsiderRole()->grantPermissions($outsider_b)->save();

    // Create some groups.
    $storage = $this->entityTypeManager->getStorage('group');
    $values = ['uid' => $this->account->id(), 'label' => 'foo'];
    $this->groupA1 = $storage->create(['type' => 'a'] + $values);
    $this->groupA2 = $storage->create(['type' => 'a'] + $values);
    $this->groupB1 = $storage->create(['type' => 'b'] + $values);
    $this->groupB2 = $storage->create(['type' => 'b'] + $values);
    $this->groupA1->save();
    $this->groupA2->save();
    $this->groupB1->save();
    $this->groupB2->save();

    // Remove the test account from the A2 and B2 groups. @todo removeMember().
    $this->groupA2->getMember($this->account)->getGroupContent()->delete();
    $this->groupB2->getMember($this->account)->getGroupContent()->delete();
  }

}

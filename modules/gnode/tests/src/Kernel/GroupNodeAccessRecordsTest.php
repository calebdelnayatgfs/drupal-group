<?php

namespace Drupal\Tests\gnode\Kernel;

/**
 * Tests the access records that are set for group nodes.
 *
 * @group gnode
 */
class GroupNodeAccessRecordsTest extends GroupNodeAccessTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('node', 'node_access');
  }

  /**
   * Tests that no access records are set for ungrouped nodes.
   */
  public function testUngroupedNode() {
    $node = $this->entityTypeManager
      ->getStorage('node')
      ->create([
        'type' => 'a',
        'title' => $this->randomMachineName(),
      ]);
    $node->save();

    $records = gnode_node_access_records($node);
    $this->assertEmpty($records, 'No access records set for an ungrouped node.');
  }

  /**
   * Tests the access records for a published group node.
   */
  public function testPublishedGroupNode() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager
      ->getStorage('node')
      ->create([
        'type' => 'a',
        'title' => $this->randomMachineName(),
      ]);
    $node->save();
    $this->groupA1->addContent($node, 'group_node:a');

    $records = gnode_node_access_records($node);
    $this->assertCount(3, $records, '3 access records set for a published group node.');

    $base = [
      'grant_view' => 1,
      'grant_update' => 1,
      'grant_delete' => 1,
      'priority' => 0,
    ];
    $gid = $this->groupA1->id();
    $uid = $node->getOwnerId();
    $this->assertEquals(['gid' => $gid, 'realm' => 'gnode:a'] + $base, $records[0], 'General gnode:NODE_TYPE grant found.');
    $this->assertEquals(['gid' => $gid, 'realm' => "gnode_author:$uid:a"] + $base, $records[1], 'Author gnode_author:UID:NODE_TYPE grant found.');
    $this->assertEquals(['gid' => GNODE_MASTER_GRANT_ID, 'realm' => 'gnode_bypass'] + $base, $records[2], 'Admin gnode_bypass grant found.');
  }

  /**
   * Tests the access records for an unpublished group node.
   */
  public function testUnpublishedGroupNode() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager
      ->getStorage('node')
      ->create([
        'type' => 'a',
        'title' => $this->randomMachineName(),
        'status' => NODE_NOT_PUBLISHED,
      ]);
    $node->save();
    $this->groupA1->addContent($node, 'group_node:a');

    $records = gnode_node_access_records($node);
    $this->assertCount(2, $records, '2 access records set for an unpublished group node.');

    $base = [
      'grant_view' => 1,
      'grant_update' => 1,
      'grant_delete' => 1,
      'priority' => 0,
    ];
    $gid = $this->groupA1->id();
    $uid = $node->getOwnerId();
    $this->assertEquals(['gid' => $gid, 'realm' => "gnode_author:$uid:a"] + $base, $records[0], 'Author gnode_author:UID:NODE_TYPE grant found.');
    $this->assertEquals(['gid' => GNODE_MASTER_GRANT_ID, 'realm' => 'gnode_bypass'] + $base, $records[1], 'Admin gnode_bypass grant found.');
  }

}

<?php

namespace Drupal\ggroup\Graph;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\ggroup\Graph\GroupGraphStorageInterface;
use Drupal\ggroup\Graph\CyclicGraphException;

/**
 * SQL based storage of the group relationship graph.
 */
class SqlGroupGraphStorage implements GroupGraphStorageInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Contracts a new class instance.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @return static
   *   A new class instance.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Gets the edge ID relating parent group A to child group B.
   *
   * @param int $a
   *   The ID of the parent group.
   * @param int $b
   *   The ID of the child group.
   * @return int
   *   The ID of the edge relating parent group A to child group B.
   */
  protected function getEdgeId($a, $b) {
    return $this->connection->query('SELECT gg.id FROM {group_graph} gg WHERE
      gg.start_vertex = :a AND
      gg.end_vertex = :b AND
      gg.hops = 0', [
        ':a' => $a,
        ':b' => $b,
    ])->fetchField();
  }

  /**
   * Relates parent group A to child group B so that child group B can be
   * considered a subgroup of group A. This method only creates the relationship
   * from group A to group B and not any of the inferred relationships based on
   * what other relationships group A and group B already have.
   *
   * @param int $a
   *   The ID of the parent group.
   * @param int $b
   *   The ID of the child group.
   * @return int
   *   The ID of the new edge relating parent group A to child group B.
   */
  protected function insertEdge($a, $b) {
    $new_edge_id = $this->connection->insert('group_graph')
      ->fields([
        'start_vertex' => $a,
        'end_vertex' => $b,
        'hops' => 0,
      ])
      ->execute();

    $this->connection->update('group_graph')
      ->fields([
        'entry_edge_id' => $new_edge_id,
        'exit_edge_id' => $new_edge_id,
        'direct_edge_id' => $new_edge_id,
      ])
      ->condition('id', $new_edge_id)
      ->execute();

    return $new_edge_id;
  }

  /**
   * @todo Add description.
   *
   * @param int $edge_id
   *   The existing edge ID relating parent group A to child group B.
   * @param int $a
   *   The ID of the parent group.
   * @param int $b
   *   The ID of the child group.
   */
  protected function insertEdgesAIncomingToB($edge_id, $a, $b) {
    // A's incoming edges to B.
    $insert_query = <<<EOT
      INSERT INTO {group_graph} (
          entry_edge_id,
          direct_edge_id,
          exit_edge_id,
          start_vertex,
          end_vertex,
          hops)
        SELECT gg.id,
          :edge_id,
          :edge_id,
          gg.start_vertex,
          :b,
          gg.hops + 1
        FROM {group_graph} gg
        WHERE end_vertex = :a
EOT;

    $this->connection->query($insert_query, [
      ':edge_id' => $edge_id,
      ':a' => $a,
      ':b' => $b,
    ]);
  }

  /**
   * @todo Add description.
   *
   * @param int $edge_id
   *   The existing edge ID relating parent group A to child group B.
   * @param int $a
   *   The ID of the parent group.
   * @param int $b
   *   The ID of the child group.
   */
  protected function insertEdgesAToBOutgoing($edge_id, $a, $b) {
    // A to B's outgoing edges.
    $insert_query = <<<EOT
      INSERT INTO {group_graph} (
          entry_edge_id,
          direct_edge_id,
          exit_edge_id,
          start_vertex,
          end_vertex,
          hops)
        SELECT :edge_id,
          :edge_id,
          gg.id,
          :a,
          gg.end_vertex,
          gg.hops + 1
        FROM {group_graph} gg
        WHERE start_vertex = :b
EOT;

    $this->connection->query($insert_query, [
      ':edge_id' => $edge_id,
      ':a' => $a,
      ':b' => $b,
    ]);
  }

  /**
   * @todo Add description.
   *
   * @param int $edge_id
   *   The existing edge ID relating parent group A to child group B.
   * @param int $a
   *   The ID of the parent group.
   * @param int $b
   *   The ID of the child group.
   */
  protected function insertEdgesAIncomingToBOutgoing($edge_id, $a, $b) {
    // A’s incoming edges to B's outgoing edges.
    $insert_query = <<<EOT
      INSERT INTO {group_graph} (
          entry_edge_id,
          direct_edge_id,
          exit_edge_id,
          start_vertex,
          end_vertex,
          hops)
        SELECT a.id,
          :edge_id,
          b.id,
          a.start_vertex,
          b.end_vertex,
          a.hops + b.hops + 1
        FROM {group_graph} a
          CROSS JOIN {group_graph} b
        WHERE a.end_vertex = :a
          AND b.start_vertex = :b
EOT;

    $this->connection->query($insert_query, [
      ':edge_id' => $edge_id,
      ':a' => $a,
      ':b' => $b,
    ]);

  }

  /**
   * {@inheritdoc}
   */
  public function addEdge($a, $b) {
    if ($a === $b) {
      return FALSE;
    }

    $ab_edge_id = $this->getEdgeId($a, $b);

    if (!empty($ab_edge_id)) {
      return $ab_edge_id;
    }

    $ba_edge_id = $this->getEdgeId($a, $b);

    if (!empty($ba_edge_id)) {
      return $ba_edge_id;
    }

    if ($this->isDescendant($a, $b)) {
      throw new CyclicGraphException($a, $b);
    }

    $new_edge_id = $this->insertEdge($a, $b);
    $this->insertEdgesAIncomingToB($new_edge_id, $a, $b);
    $this->insertEdgesAToBOutgoing($new_edge_id, $a, $b);
    $this->insertEdgesAIncomingToBOutgoing($new_edge_id, $a, $b);

    return $new_edge_id;
  }

  /**
   * {@inheritdoc}
   */
  public function removeEdge($a, $b) {
    $edge_id = $this->getEdgeId($a, $b);

    if (empty($edge_id)) {
      return;
    }

    $edges_to_delete = [];

    $results = $this->connection->query('SELECT gg.id FROM {group_graph} gg WHERE direct_edge_id = :edge_id', [
      ':edge_id' => $edge_id
    ]);

    while ($id = $results->fetchField()) {
      $edges_to_delete[] = $id;
    }

    if (empty($edges_to_delete)) {
      return;
    }

    $select_query = <<<EOT
      SELECT id FROM {group_graph} WHERE hops > 0 AND
        (entry_edge_id IN (:edge_ids[]) OR exit_edge_id IN (:edge_ids[])) AND
        (id NOT IN (:edge_ids[]))
EOT;

    do {
      $total_edges = count($edges_to_delete);

      $results = $this->connection->query($select_query, [
        ':edge_ids[]' => $edges_to_delete,
      ]);

      while ($id = $results->fetchField()) {
        $edges_to_delete[] = $id;
      }
    } while (count($edges_to_delete) > $total_edges);

    $this->connection->delete('group_graph')
      ->condition('id', $edges_to_delete, 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescendants($group_id) {
    return $this->connection->query('SELECT end_vertex FROM {group_graph} WHERE start_vertex = :group_id', [
      ':group_id' => $group_id,
    ])->fetchAll(\PDO::FETCH_COLUMN);
  }

  /**
   * {@inheritdoc}
   */
  public function getAncestors($group_id) {
    return $this->connection->query('SELECT start_vertex FROM {group_graph} WHERE end_vertex = :group_id', [
      ':group_id' => $group_id,
    ])->fetchAll(\PDO::FETCH_COLUMN);
  }

  /**
   * {@inheritdoc}
   */
  public function isAncestor($a, $b) {
    return $this->isDescendant($b, $a);
  }

  /**
   * {@inheritdoc}
   */
  public function isDescendant($a, $b) {
    return $this->connection->query('SELECT COUNT(id) FROM {group_graph} WHERE start_vertex = :b AND end_vertex = :a', [
      ':a' => $a,
      ':b' => $b,
    ])->fetchField() > 0;
  }

}
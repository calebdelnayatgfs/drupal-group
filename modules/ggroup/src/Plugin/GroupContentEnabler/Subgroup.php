<?php

namespace Drupal\ggroup\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\group\Entity\GroupType;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a content enabler for subgroups.
 *
 * @GroupContentEnabler(
 *   id = "subgroup",
 *   label = @Translation("Subgroup"),
 *   description = @Translation("Adds groups to groups."),
 *   entity_type_id = "group",
 *   pretty_path_key = "group",
 *   deriver = "Drupal\ggroup\Plugin\GroupContentEnabler\SubgroupDeriver"
 * )
 */
class Subgroup extends GroupContentEnablerBase {

  /**
   * Retrieves the group type this plugin supports.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface
   *   The group type this plugin supports.
   */
  protected function getSubgroupType() {
    return GroupType::load($this->getEntityBundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $type = $this->getEntityBundle();
    $operations = [];

    if ($group->hasPermission("create $type group", $account)) {
      $route_params = ['group' => $group->id(), 'group_type' => $this->getEntityBundle()];
      $operations["ggroup_create-$type"] = [
        'title' => $this->t('Create @type', ['@type' => $this->getSubgroupType()->label()]),
        'url' => new Url('entity.group_content.subgroup_add_form', $route_params),
        'weight' => 35,
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $permissions = parent::getPermissions();

    // Override default permission titles and descriptions.
    $plugin_id = $this->getPluginId();
    $type_arg = ['%group_type' => $this->getSubgroupType()->label()];
    $defaults = [
      'title_args' => $type_arg,
      'description' => 'Only applies to %group_type subgroups that belong to this group.',
      'description_args' => $type_arg,
    ];

    $permissions["view $plugin_id content"] = [
      'title' => '%group_type: View subgroups',
    ] + $defaults;

    $permissions["create $plugin_id content"] = [
      'title' => '%group_type: Create new subgroups',
      'description' => 'Allows you to create %group_type subgroups that immediately belong to this group.',
      'description_args' => $type_arg,
    ] + $defaults;

    $permissions["edit own $plugin_id content"] = [
      'title' => '%group_type: Edit own subgroups',
    ] + $defaults;

    $permissions["edit any $plugin_id content"] = [
      'title' => '%group_type: Edit any subgroup',
    ] + $defaults;

    $permissions["delete own $plugin_id content"] = [
      'title' => '%group_type: Delete own subgroups',
    ] + $defaults;

    $permissions["delete any $plugin_id content"] = [
      'title' => '%group_type: Delete any subgroup',
    ] + $defaults;

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;

    // This string will be saved as part of the group type config entity. We do
    // not use a t() function here as it needs to be stored untranslated.
    $config['info_text']['value'] = '<p>By submitting this form you will add this subgroup to the group.<br />It will then be subject to the access control settings that were configured for the group.<br/>Please fill out any available fields to describe the relation between the subgroup and the group.</p>';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other content enabler plugins.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return ['config' => ['group.type.' . $this->getEntityBundle()]];
  }

}

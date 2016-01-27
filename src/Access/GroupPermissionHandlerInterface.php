<?php

/**
 * @file
 * Contains \Drupal\group\Access\GroupPermissionHandlerInterface.
 */

namespace Drupal\group\Access;

/**
 * Defines an interface to list available permissions.
 */
interface GroupPermissionHandlerInterface {

  /**
   * Gets all available permissions.
   *
   * @return array
   *   An array whose keys are permission names and whose corresponding values
   *   are arrays containing the following key-value pairs:
   *   - title: The untranslated human-readable name of the permission, to be
   *     shown on the permission administration page. You may use placeholders
   *     as you would in t().
   *   - title_args: (optional) The placeholder values for the title.
   *   - description: (optional) An untranslated description of what the
   *     permission does. You may use placeholders as you would in t().
   *   - description_args: (optional) The placeholder values for the description.
   *   - restrict access: (optional) A boolean which can be set to TRUE to
   *     indicate that site administrators should restrict access to this
   *     permission to trusted users. This should be used for permissions that
   *     have inherent security risks across a variety of potential use cases.
   *     When set to TRUE, a standard warning message will be displayed with the
   *     permission on the permission administration page. Defaults to FALSE.
   *   - warning: (optional) An untranslated warning message to display for this
   *     permission on the permission administration page. This warning
   *     overrides the automatic warning generated by 'restrict access' being
   *     set to TRUE. This should rarely be used, since it is important for all
   *     permissions to have a clear, consistent security warning that is the
   *     same across the site. Use the 'description' key instead to provide any
   *     information that is specific to the permission you are defining. You
   *     may use placeholders as you would in t().
   *   - warning_args: (optional) The placeholder values for the warning.
   *   - allowed for: (optional) An array of strings that define which
   *     membership types can use this permission. Possible values are:
   *     'anonymous', 'outsider', 'member'. Will default to all three when left
   *     empty.
   *   - provider: (optional) The provider name of the permission. Defaults to
   *     the module providing the permission. You may set this to another
   *     module's name to make it appear as if the permission was provided by
   *     that module.
   */
  public function getPermissions();

  /**
   * Completes a permission by adding in defaults and translating its strings.
   *
   * Warning: This does not set the 'provider' key! This should be taken care of
   * by the permission provider or the implementation of this interface. Outside
   * of discovery or custom implementations, it's almost impossible to guess who
   * provided a specific permission.
   *
   * @param array $permission
   *   The raw permission to complete.
   *
   * @return array
   *   A permission which is guaranteed to have all the required keys set.
   */
  public function completePermission($permission);

}


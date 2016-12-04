<?php
/*
  Plugin Name: ACF PHP Recovery Tool
  Description: Converts your PHP export back-in as an editable ACF fields. To be used when you lose the original database and json fieldsets.
  Author: BE API
  Author URI: http://www.beapi.fr
  Version: 1.0.0
*/

define('ACFPR_GROUP_POST_TYPE', 'acf-field-group');

function acf_php_recovery_menu() {
  add_submenu_page('edit.php?post_type='.ACFPR_GROUP_POST_TYPE, __('ACF PHP Recovery','acf_php_recovery'), __('PHP Recovery','acf_php_recovery'), 'manage_options','acf-php-import', 'acf_php_recovery_page' );
}
add_action('admin_menu', 'acf_php_recovery_menu', 100);

function acf_php_recovery_page() {
  global $wpdb;

  $acf_local = acf_local();

  // process the form
  if(isset($_POST['acf_php_recovery_action']) && $_POST['acf_php_recovery_action'] == 'import' && isset($_POST['fieldsets']) && check_admin_referer( 'acf_php_recovery' ) ) {
    $import_fieldsets = $_POST['fieldsets'];

    $imported = array(); // Keep track of the imported

    $key_to_post_id = array(); // Group or field key to post id

    // Now we can import the groups
    foreach( $acf_local->groups as $key => $group ) {
      $group['title'] = $group['title'] . ' (Recovered)';

      // Only import those that were selected
      if( in_array($key, $import_fieldsets) ) {
        $saved_group = acf_update_field_group($group);

        $key_to_post_id[$key] = $saved_group['ID'];

        // For displaying the success message
        $imported[] = array(
          'title' => $group['title'],
          'id' => $saved_group['ID'],
        );
      }
    }

    // This requires multipile runs to handle sub-fields that have their parent set to the parent field instead of the group
    $field_parents = $import_fieldsets; // The groups and fields
    $imported_fields = array(); // Keep track of the already imported
    do {
      $num_import = 0;
      foreach( $acf_local->fields as $key => $field ) {
        if ( !in_array($key, $imported_fields) && in_array($field['parent'], $field_parents) ) {
          $num_import = $num_import + 1;
          $field_parents[] = $key;
          $imported_fields[] = $key;

          $field['parent'] = $key_to_post_id[$field['parent']]; // Convert the key into the post_parent
          $saved_field = acf_update_field( $field );
          $key_to_post_id[$key] = $saved_field['ID'];
        }
      }
    } while( $num_import > 0 );
  }


  // output
  ?>
  <div class="wrap">
  <h2>ACF PHP Recovery Tool</h2>

  <?php
  // Check the version of ACF
  $acf_version = explode( '.', acf_get_setting('version') );
  if ( $acf_version[0] != '5' ):
  ?>
  <div id="message" class="error below-h2">
    <p><?php printf( __( 'This tool was built for ACF version 5 and you have version %s.' ), $acf_version[0] ); ?></p>
  </div>
  <?php
  endif;
  ?>

  <?php
  if(!empty($imported)) {
    ?>
      <div id="message" class="updated below-h2"><p><?php _e('Fieldsets recovered'); ?>.</p>
      <ul>
      <?php
        foreach($imported as $import) {
          ?>
          <li><?php edit_post_link( $import['title'], '', '', $import['id']); ?></li>
          <?php
        }
      ?>
        <li><strong><?php _e( 'Remove the PHP defined fields! The duplicate field IDs interfer with the editing of the fields.' ); ?></strong></li>
      </ul>
      </div>
    <?php
  }
  ?>

  <p><strong>This is a recovery tool. Do not use this as part of your workflow for importing and exporting ACF fieldsets.</strong></p>
  <form method="POST">
  <table class="widefat">
    <thead>
      <th>Import</th>
      <th>Name</th>
      <th>Possible Existing Matches</th>
    </thead>
    <tbody>
    <?php
    foreach( $acf_local->groups as $key => $field_group ): ?>
    <tr>
      <td><input type="checkbox" name="fieldsets[]" value="<?php echo esc_attr($key); ?>" /></td>
      <td><?php echo $field_group['title']; ?></td>
      <td><?php
          $sql = "SELECT ID, post_title FROM $wpdb->posts WHERE post_title LIKE '%{$field_group['title']}%' AND post_type='".ACFPR_GROUP_POST_TYPE."'";
          // Set post status
          $post_status = apply_filters( 'acf_recovery\query\post_status', '' );
          if ( ! empty( $post_status ) ) {
			   $sql .= ' AND post_status="'.esc_sql($post_status).'"';
          }
          $matches = $wpdb->get_results($sql);
          if(empty($matches)) {
            echo '<em>none</em>';
          } else {
            $links = array();
            foreach($matches as $match) {
              $links[] = '<a href="'.get_edit_post_link($match->ID).'">'.$match->post_title.'</a>';
            }
            echo implode(', ', $links);
          }
      ?></td>
    </tr>
    <?php
    endforeach;
    ?>
    </tbody>
  </table>
    <?php wp_nonce_field( 'acf_php_recovery' ); ?>
    <input type="hidden" name="acf_php_recovery_action" value="import" />
    <p class="submit">
      <input type="submit" value="Import" class="button-primary" />
    </p>
  </form>

  <h3>Registered Field Groups</h3>
  <pre class="">
  <?php echo var_export( $acf_local->groups ); ?>
  </pre>

  </div>
  <?php
}



// Add settings link on plugin page
function acf_php_recovery_settings_link($links) {
  $settings_link = '<a href="edit.php?post_type=acf&page=acf-php-import">ACF PHP Recovery</a>';
  array_unshift($links, $settings_link);
  return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'acf_php_recovery_settings_link' );


<?php
/*
  Plugin Name: ACF PHP Recovery Tool
  Description: Converts your PHP export back-in as editable fields for when you lose the original database and XML.
  Author: Seamus Leahy
  Version: 0.2
 */

function acf_php_recovery_menu() {
  add_submenu_page('edit.php?post_type=acf', __('ACF PHP Recovery','acf_php_recovery'), __('PHP Recovery','acf_php_recovery'), 'manage_options','acf-php-import', 'acf_php_recovery_page' );
}
add_action('admin_menu', 'acf_php_recovery_menu', 100);

function acf_php_recovery_page() {
  global $acf_register_field_group, $wpdb;

  // process the form
  if(isset($_POST['acf_php_recovery_action']) && $_POST['acf_php_recovery_action'] == 'import' && isset($_POST['fieldsets']) && check_admin_referer( 'acf_php_recovery' ) ) {
    $import_fieldsets = $_POST['fieldsets'];

    $imported = array();

    foreach($acf_register_field_group as $fieldset) {
      if(in_array($fieldset['id'], $import_fieldsets)) {
        // Create a record in the post table
        $post = array(
          'post_title' => $fieldset['title'] . ' (Recovered)',
          'post_type' => 'acf',
          'menu_order' => $fieldset['menu_order']
        );

        $post_id = wp_insert_post($post);

        // Meta values
        // Location: Rules and 'All or Any'
        foreach($fieldset['location'] as $group_id => $group) {
          if( is_array( $group ) ) {
            foreach( $group as $rule_id => $rule ) {
              $rule['order_no'] = $rule_id;
              $rule['group_no'] = $group_id;
              add_post_meta( $post_id, 'rule', $rule, false );
            }
          }
        }


        // Options: position, layout, hide_on_screen
        foreach($fieldset['options'] as $key => $val) {
          add_post_meta( $post_id, $key, $val, true);
        }

        // TODO the location/rules

        // Fields
        $order_no = 0; // Keep track of the ordering of the field for display purposes
        foreach( $fieldset['fields'] as $field ) {
          if( isset($field['order_no']) ) {
            $order_no = max( $order_no, $field['order_no'] );
          } else {
            $field['order_no'] = $order_no++;
          }
          add_post_meta( $post_id, $field['key'], $field, true);
        }

        // For displaying the success message
        $imported[] = array(
          'title' => $post['post_title'],
          'id' => $post_id,
        );
      }
    }
  }


  // output
  ?>
  <div class="wrap">
  <h2>ACF PHP Recovery Tool</h2>

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
    foreach( $acf_register_field_group as $field_group): ?>
    <tr>
      <td><input type="checkbox" name="fieldsets[]" value="<?php echo $field_group['id']?>" /></td>
      <td><?php echo $field_group['title']; ?></td>
      <td><?php $matches = $wpdb->get_results("SELECT ID, post_title FROM $wpdb->posts WHERE post_title LIKE '%{$field_group['title']}%' AND post_type='acf'"); 
          if(empty($matches)) {
            echo '<em>none</em>';
          } else {
            $links = array();
            foreach($matches as $match) {
              $links[] = '<a href='.get_edit_post_link($match->ID).'">'.$match->post_title.'</a>';
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
  <?php echo var_export($GLOBALS['acf_register_field_group']); ?>
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


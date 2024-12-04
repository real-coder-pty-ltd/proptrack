<?php
/**
 * The admin-specific functionality of the plugin.
 */
// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

function registerPropTrackSettings()
{
    add_submenu_page('options-general.php', 'PropTrack Settings', 'PropTrack Settings', 'manage_options', 'proptrack', 'addProptrackSettings');
}

function addProptrackSettings()
{
    $settings = [
        'title' => 'PropTrack Settings',
        'description' => 'Here you can set all your settings for the PropTrack Integration',
        'fields' => [
            [
                'title' => 'PropTrack Client Key',
                'description' => 'Enter your PropTrack Client API key.',
                'name' => 'proptrack_client',
                'value' => get_option('proptrack_client'),
            ],
            [
                'title' => 'PropTrack Client Secret',
                'description' => 'Enter your PropTrack Client Secret.',
                'name' => 'proptrack_secret',
                'value' => get_option('proptrack_secret'),
            ],
            [
                'title' => 'Google Maps Autocomplete API Key',
                'description' => 'You\'ll need a Google Maps API key with Places enabled.',
                'name' => 'proptrack_gmaps_api_key',
                'value' => get_option('proptrack_gmaps_api_key'),
            ],
            [
                'title' => 'Google Maps Server Side API key',
                'description' => 'It is HIGHLY recommended to create a separate key solely for use here. It only needs access to the Distance Matrix Endpoint. Do not restrict its access. Make sure to keep this safe, as it will allow for unrestricted acccess to the Distance Matrix API.',
                'name' => 'proptrack_gmaps_server_api_key',
                'value' => get_option('proptrack_gmaps_server_api_key'),
            ],
        ],
    ];
    ?>

<div class="wrap">
    <h1><?php echo $settings['title']; ?></h1>
    <p><?php echo $settings['description']; ?></p>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST">
        <table class="form-table" role="presentation">
            <?php foreach ($settings['fields'] as $field) { ?>
            <tr>
                <th scope="row">
                    <label for="<?php echo $field['name']; ?>"><?php echo $field['title']; ?></label>
                </th>
                <td>
                    <input type="text" name="<?php echo $field['name']; ?>" id="<?php echo $field['name']; ?>"
                        value="<?php echo $field['value']; ?>" size="45">
                    <p class="description"><?php echo $field['description']; ?></p>
                </td>
            </tr>
            <?php } ?>
            <tr>
                <th scope="row">Enqueue Google Maps</th>
                <td>
                    <label for="proptrack_enqueue_gmaps">
                        <input name="proptrack_enqueue_gmaps" type="checkbox" id="proptrack_enqueue_gmaps"
                            <?php echo (get_option('proptrack_enqueue_gmaps')) ? 'checked="checked"' : ''; ?>>Output
                        Google Map code on page.</label>
                    <p class="description">Untick this if your site already has a Google Maps API key.</p>
                </td>
            </tr>
        </table>
        <input type="hidden" name="action" value="process_form"> <br><br>
        <input type="submit" name="submit" id="submit" class="update-button button button-primary" value="Update">
    </form>
</div>
<?php
}
add_action('admin_menu', 'registerPropTrackSettings');

function propTrackSubmitAdmin()
{
    $options = [
        'proptrack_client',
        'proptrack_secret',
        'proptrack_gmaps_api_key',
        'proptrack_gmaps_server_api_key',
        'proptrack_enqueue_gmaps',
    ];

    foreach ($options as $option) {

        if (isset($_POST[$option])) {
            $value = sanitize_text_field($_POST[$option]);
            update_option($option, $value);

            continue;
        }

        if (! $value) {
            delete_option($option);

            continue;
        }

    }
    wp_redirect($_SERVER['HTTP_REFERER']);
}

add_action('admin_post_nopriv_process_form', 'propTrackSubmitAdmin');
add_action('admin_post_process_form', 'propTrackSubmitAdmin');

/**
 * Add custom fields for state/postcode to the location taxonomy.
 * To get this data, you can do this:
 * $postcode = get_term_meta($term_id, 'postcode', true);
 * $state = get_term_meta($term_id, 'state', true);
 */
add_action('location_add_form_fields', 'add_custom_fields_to_location_taxonomy');
add_action('location_edit_form_fields', 'edit_custom_fields_in_location_taxonomy', 10, 2);

function add_custom_fields_to_location_taxonomy($taxonomy)
{
    ?>
<div class="form-field">
    <label for="postcode">Postcode</label>
    <input type="text" name="postcode" id="postcode" value="">
    <p class="description">Enter the postcode for this location.</p>
</div>
<div class="form-field">
    <label for="state">State</label>
    <input type="text" name="state" id="state" value="">
    <p class="description">Enter the state for this location.</p>
</div>
<?php
}

function edit_custom_fields_in_location_taxonomy($term, $taxonomy)
{
    $postcode = get_term_meta($term->term_id, 'postcode', true);
    $state = get_term_meta($term->term_id, 'state', true);
    ?>
<tr class="form-field">
    <th scope="row" valign="top"><label for="postcode">Postcode</label></th>
    <td>
        <input type="text" name="postcode" id="postcode" value="<?php echo esc_attr($postcode); ?>">
        <p class="description">Enter the postcode for this location.</p>
    </td>
</tr>
<tr class="form-field">
    <th scope="row" valign="top"><label for="state">State</label></th>
    <td>
        <input type="text" name="state" id="state" value="<?php echo esc_attr($state); ?>">
        <p class="description">Enter the state for this location.</p>
    </td>
</tr>
<?php
}

add_action('created_location', 'save_custom_fields_for_location_taxonomy');
add_action('edited_location', 'save_custom_fields_for_location_taxonomy');

function save_custom_fields_for_location_taxonomy($term_id)
{
    if (isset($_POST['postcode'])) {
        update_term_meta($term_id, 'postcode', sanitize_text_field($_POST['postcode']));
    }

    if (isset($_POST['state'])) {
        update_term_meta($term_id, 'state', sanitize_text_field($_POST['state']));
    }
}

add_filter('manage_edit-location_columns', 'add_custom_columns_to_location_taxonomy');
add_filter('manage_location_custom_column', 'populate_custom_columns_in_location_taxonomy', 10, 3);

function add_custom_columns_to_location_taxonomy($columns)
{
    $columns['postcode'] = 'Postcode';
    $columns['state'] = 'State';

    return $columns;
}

function populate_custom_columns_in_location_taxonomy($content, $column_name, $term_id)
{
    if ($column_name === 'postcode') {
        $content = get_term_meta($term_id, 'postcode', true);
    } elseif ($column_name === 'state') {
        $content = get_term_meta($term_id, 'state', true);
    }

    return $content;
}

add_filter('manage_edit-location_columns', 'remove_description_column_from_location');

function remove_description_column_from_location($columns)
{
    if (isset($columns['description'])) {
        unset($columns['description']);
    }

    return $columns;
}
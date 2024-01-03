<?php
/**
 * Plugin Name: Export Posts to HTML
 * Description: A WordPress plugin to export posts as HTML.
 * Version: 1.0
 * Author: Taha
 */

// Add link under Tools menu
function add_export_html_menu() {
    add_submenu_page(
        'tools.php',                  // parent menu slug
        'Export Posts to HTML',       // page title
        'Export Posts to HTML',       // menu title
        'manage_options',             // capability
        'export-posts-to-html',       // menu slug
        'export_html_page'            // callback function to display the page
    );
}
add_action('admin_menu', 'add_export_html_menu');

function enqueue_codemirror() {

    wp_enqueue_style('codemirror-css', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/codemirror.min.css');
    wp_enqueue_script('codemirror-js', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/codemirror.min.js', array('jquery'), '', true);
    wp_enqueue_script('codemirror-html', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/mode/htmlmixed/htmlmixed.min.js', array('codemirror-js'), '', true);
    wp_enqueue_script('codemirror-xml', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/mode/xml/xml.min.js', array('codemirror-js'), '', true);
    wp_enqueue_script('codemirror-javascript', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/mode/javascript/javascript.min.js', array('codemirror-js'), '', true);
    wp_enqueue_script('codemirror-addon-edit', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/addon/edit/closebrackets.min.js', array('codemirror-js'), '', true);
    wp_enqueue_script('codemirror-addon-search', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/addon/search/search.min.js', array('codemirror-js'), '', true);

    // Enqueue codemirror-init script
    wp_enqueue_script('codemirror-init', plugin_dir_url(__FILE__) . 'codemirror-init.js', array('codemirror-js'), '', true);

    // Localize codemirror-init script
    wp_localize_script('codemirror-init', 'codemirror_params', array(
        'default_template_content' => get_option('html_template_content', ''), 
    ));
}
add_action('admin_enqueue_scripts', 'enqueue_codemirror');

// Save Template
add_action('wp_ajax_save_html_template', 'save_html_template');

function save_html_template() {
    if (isset($_POST['html_template_content'])) {
        update_option('html_template_content', wp_unslash($_POST['html_template_content']));
        echo 'success';
    } else {
        echo 'error';
    }
    wp_die();
}

// Display editor on the Export Posts to HTML page
function export_html_page() {

    $default_template_content = get_option('html_template_content', '');
    
    ?>
    <div class="wrap">
        <h1>Export Posts to HTML</h1>
        <h2>Edit Template</h2>
        <form method="post" action="">
            <textarea id="html_template_content" style="height: 350px;"><?php echo esc_textarea($default_template_content); ?></textarea>
            <p>Use Sortcodes like <code>[POST_TITLE]</code> and <code>[POST_CONTENT]</code></p>
            <p><input type="submit" name="save_template" class="button-primary" value="Save Template"></p>
        </form>
    </div>
    <?php
}


// Add "Export as HTML" link on Posts list page
function add_export_link($actions, $post) {
    $actions['export_html'] = '<a href="' . admin_url('admin-ajax.php?action=export_post_html&post_id=' . $post->ID) . '" target="_blank">Export as HTML</a>';
    return $actions;
}
add_filter('post_row_actions', 'add_export_link', 10, 2);


// AJAX request to export post as HTML
function export_post_html() {
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

    // Get post content and template from the database or user input
    $post = get_post($post_id);
    $post_content = $post->post_content;
    $html_template = get_option('html_template_content', '');

    // Replace placeholders in the template with actual post data
    $html_content = str_replace('[POST_TITLE]', esc_html(get_the_title($post_id)), $html_template);
    $html_content = str_replace('[POST_CONTENT]', $post_content, $html_content);

    // Generate a unique filename
    $filename = 'post_' . $post_id . '_export.html';

    // Send the file to the browser for download
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $html_content;
    exit;
}
add_action('wp_ajax_export_post_html', 'export_post_html');


// Add "Export as HTML" option in Bulk Actions
function add_bulk_actions($actions) {
    $actions['export_bulk_html'] = 'Export as HTML';
    return $actions;
}
add_filter('bulk_actions-edit-post', 'add_bulk_actions');


// Handle bulk export action
function handle_bulk_action($redirect_to, $doaction, $post_ids) {
    if ($doaction === 'export_bulk_html') {
        $zip = new ZipArchive();
        $zip_filename = 'posts_export.zip';

        if ($zip->open($zip_filename, ZipArchive::CREATE) === true) {
            foreach ($post_ids as $post_id) {
                $post_content = get_post_field('post_content', $post_id);
                $html_template_title = get_option('html_template_title', '');
                $html_template_content = get_option('html_template_content', '');

                // Replace placeholders in the template with actual post data
                $html_content = str_replace('[POST_TITLE]', get_the_title($post_id), $html_template_title);
                $html_content = str_replace('[POST_CONTENT]', $post_content, $html_template_content);

                // Add each post as a file to the zip archive
                $zip->addFromString('post_' . $post_id . '_export.html', $html_content);
            }

            $zip->close();

            // Send the zip file to the browser for download
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
            readfile($zip_filename);

            // Delete the zip file after sending
            unlink($zip_filename);
            exit;
        }
    }

    return $redirect_to;
}
add_filter('handle_bulk_actions-edit-post', 'handle_bulk_action', 10, 3);

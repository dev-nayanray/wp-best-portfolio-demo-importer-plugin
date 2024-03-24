<?php 
class wpbestportfolio_Theme_Demo_Importer {
    public function __construct() {
        // Initialize hooks
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'handle_demo_import'));
    }

    // Register admin menu
    public function register_admin_menu() {
        add_menu_page(
            'Demo Importer',
            'Demo Importer',
            'manage_options',
            'wp-best-portfolio-demo-importer',
            array($this, 'render_demo_importer_page'),
            'dashicons-admin-tools',
            30
        );
    }

    // Render demo importer page
    public function render_demo_importer_page() {
        ?>
        <div class="wrap">
            <h2>Wp Best Portfolio Demo Impoter</h2>
            <p>Welcome to the Demo Importer for Wp Best Portfolio Theme.</p>
            <form method="post" action="">
                <?php wp_nonce_field('wpbpdi_demo_import_nonce', 'wpbpdi_demo_import_nonce'); ?>
                <label for="demo_key">Enter Purchase Key:</label>
                <input type="text" name="demo_key" id="demo_key" />
                <input type="submit" class="button button-primary" value="Import Demo Content" name="wpbpdi_demo_import_submit">
            </form>
        </div>
        <?php
    }

    // Handle form submission
    public function handle_demo_import() {
        if (isset($_POST['wpbpdi_demo_import_submit'])) {
            // Verify nonce
            if (!isset($_POST['wpbpdi_demo_import_nonce']) || !wp_verify_nonce($_POST['wpbpdi_demo_import_nonce'], 'wpbpdi_demo_import_nonce')) {
                return;
            }

            // Check user capability
            if (!current_user_can('manage_options')) {
                return;
            }

            // Check if the provided key matches the default key
            $provided_key = isset($_POST['demo_key']) ? sanitize_text_field($_POST['demo_key']) : '';
            $default_key = '123456';

            if ($provided_key !== $default_key) {
                // If keys do not match, show error message and redirect to homepage
                wp_die('Invalid demo key. Redirecting to homepage...');
                wp_redirect(home_url());
                exit;
            }

            // Call function to import demo content
            $this->import_demo_content();
        }
    }

    // Function to import demo content
    public function import_demo_content() {
        // Your import logic goes here
        // Example: Parse XML content and import posts, pages, etc.
        $demo_content = file_get_contents(plugin_dir_path(__FILE__) . 'demo-content/demo-content.xml');

        // Check if XML content exists
        if (empty($demo_content)) {
            wp_die('Demo content file is empty.');
            wp_redirect(home_url());
            exit;
        }

        // Example: Parse XML and import content
        $xml = simplexml_load_string($demo_content);

        if (!$xml) {
            wp_die('Error parsing XML.');
            wp_redirect(home_url());
            exit;
        }

        // Example: Import posts from XML
        foreach ($xml->posts->post as $post_data) {
            // Extract post data from XML
            $post_title = (string) $post_data->title;
            $post_content = (string) $post_data->content;
            $post_image_url = (string) $post_data->image_url; // Assuming the image URL is provided in the XML
            $post_category = (string) $post_data->category; // Assuming the category name is provided in the XML

            // Check if the category exists, if not create it
            $category_id = 0;
            $existing_category = get_term_by('name', $post_category, 'category');
            if ($existing_category) {
                $category_id = $existing_category->term_id;
            } else {
                $new_category = wp_insert_term($post_category, 'category');
                if (!is_wp_error($new_category)) {
                    $category_id = $new_category['term_id'];
                }
            }

            // Insert the post
            $post_args = array(
                'post_title'    => $post_title,
                'post_content'  => $post_content,
                'post_status'   => 'publish',
                'post_category' => array($category_id), // Assign category to the post
                // Add more parameters as needed
            );

            $post_id = wp_insert_post($post_args);

            // Handle post meta, such as featured image
            if ($post_id && !empty($post_image_url)) {
                // Download and attach the image as the post's featured image
                $image_id = $this->attach_image_from_url($post_image_url, $post_id);
                if (!is_wp_error($image_id)) {
                    set_post_thumbnail($post_id, $image_id);
                }
            }

            // Additional post meta handling goes here
        }

        // Display success message
        ?>
        <div class="updated">
            <p>Demo content imported successfully!</p>
        </div>
        <?php
    }

    // Function to download and attach image from URL
    private function attach_image_from_url($image_url, $post_id) {
        $image_name = basename($image_url);
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($image_url);

        if ($image_data) {
            $file = $upload_dir['path'] . '/' . $image_name;
            file_put_contents($file, $image_data);

            $wp_filetype = wp_check_filetype($image_name, null);
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title'     => sanitize_file_name($image_name),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $file, $post_id);

            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $file);
            wp_update_attachment_metadata($attach_id, $attach_data);

            return $attach_id;
        }

        return new WP_Error('error', 'Image download failed');
    }

    // Function to register custom post types
    public function register_custom_post_types() {
        // Register portfolio custom post type
        register_post_type('portfolio', array(
            'labels' => array(
                'name' => __('Portfolio'),
                'singular_name' => __('Portfolio Item'),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'portfolio'),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            // Add more arguments as needed
        ));

        // Register product custom post type
        register_post_type('product', array(
            'labels' => array(
                'name' => __('Products'),
                'singular_name' => __('Product'),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'product'),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            // Add more arguments as needed
        ));

        // Register team custom post type
        register_post_type('team', array(
            'labels' => array(
                'name' => __('Team'),
                'singular_name' => __('Team Member'),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'team'),
            'supports' => array('title', 'editor', 'thumbnail'),
            // Add more arguments as needed
        ));

        // Register testimonial custom post type
        register_post_type('testimonial', array(
            'labels' => array(
                'name' => __('Testimonials'),
                'singular_name' => __('Testimonial'),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'testimonial'),
            'supports' => array('title', 'editor', 'thumbnail'),
            // Add more arguments as needed
        ));

        // Additional custom post types can be registered similarly
    }

}

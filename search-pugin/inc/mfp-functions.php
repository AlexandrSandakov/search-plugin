<?php
/*
 * Add my new menu to the Admin Control Panel
 */
// Hook the 'admin_menu' action hook, run the function named 'mfp_Add_My_Admin_Link()'
add_action('admin_menu', 'mfp_Add_My_Admin_Link');
// Add a new top level menu link to the ACP
function mfp_Add_My_Admin_Link()
{
    add_menu_page(
        'My First Page', // Title of the page
        'Filtering Plugin', // Text to show on the menu link
        'manage_options', // Capability requirement to see the link
        'inc/mfp-first-acp-page.php', // The 'slug' - file to display when clicking the link
        'myplguin_admin_page',
    );
    add_submenu_page(
        'inc/mfp-first-acp-page.php',
        'My Sub Level',
        'Filtered Post',
        'manage_options',
        'inc/mfp-first-acp-sub-page.php',
        'my_submenu_page_callback',
    );
}

function my_plugin_enqueue_styles()
{
    wp_enqueue_style('my-plugin-styles', plugin_dir_url(__FILE__) . 'plugin-styles.css');
}
add_action('admin_enqueue_scripts', 'my_plugin_enqueue_styles');

function myplguin_admin_page()
{
?>
    <div class="wrap">
        <h2>Welcome To My Plugin</h2>
        <p>This plusgin looking for year in titles of tags h </p>
    </div>
<?php
}

// Callback function to display the submenu page content
function my_submenu_page_callback()
{
    // Call the find_posts_by_year function
    find_posts_by_year();
}

function custom_posts_where($where, $query)
{
    global $wpdb;
    $pattern = '\b[0-9]{4}\b';
    $pattern_h = '<h[^>]*>(.*?\b\d{4}\b.*?)<\/h[^>]*>';
    $where .= $wpdb->prepare(" AND ($wpdb->posts.post_title REGEXP %s OR $wpdb->posts.post_content REGEXP %s)", $pattern, $pattern_h);

    return $where;
}

function find_posts_by_year()
{
    global $wpdb;

    add_filter('posts_where', 'custom_posts_where', 10, 2);

    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

    $args = array(
        'post_type'      => 'post',
        'posts_per_page' => 15,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'paged'          => $paged,
        'suppress_filters' => false, // Allow filtering by custom SQL
    );

    $query = new WP_Query($args);

    remove_filter('posts_where', 'custom_posts_where', 10, 2);

    // Process the query results
    if ($query->have_posts()) {
        echo '<div class="post-list">';
        while ($query->have_posts()) {
            $query->the_post();

            // Check if the year is in the header
            $found_in_title = preg_match('/\b[0-9]{4}\b/', get_the_title());
            // Check if the year is in the h1
            $found_in_content = preg_match('/<h[^>]*>(.*?\b\d{4}\b.*?)<\/h[^>]*>/i', get_the_content());

            // Display post content, title, or other desired information
            $html = '<div class="post">
            <a class="post_edit" href="' . get_edit_post_link($query->post->ID) . '">
            <h1 class="post_title">' . get_the_title($query->post->ID) . '</h1>
            ' . __('', 'textdomain') . '</a>';

            // Display the found_in_title value
            if ($found_in_title) {
                $html .= '<span class="found_in_title">Found in title</span>';
            }

            // Display the found_in_content value
            if ($found_in_content) {
                $html .= '<span class="found_in_content">Found in content</span>';
            }

            $html .= '</div>';
            echo $html;
        }
        echo '</div>';

        echo '<div class="pagination">';
        echo paginate_links(array(
            'total'   => $query->max_num_pages,
            'current' => $paged,
            'prev_text' => __('&laquo; Previous', 'textdomain'),
            'next_text' => __('Next &raquo;', 'textdomain'),
        ));
        echo '</div>';

        wp_reset_postdata();
    } else {
        echo 'No posts found.';
    }
}

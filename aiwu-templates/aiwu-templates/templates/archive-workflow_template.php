<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php do_action('astra_header'); ?>

<?php
// Get filter parameters
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$difficulty = isset($_GET['difficulty']) ? sanitize_text_field($_GET['difficulty']) : '';
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;

// Build query args
$args = [
    'post_type' => 'workflow_template',
    'posts_per_page' => 9,
    'paged' => $paged,
    'post_status' => 'publish'
];

if ($search) {
    $args['s'] = $search;
}

// Use tax_query for taxonomies
$tax_query = [];

if ($category) {
    $tax_query[] = [
        'taxonomy' => 'template_category',
        'field' => 'slug',
        'terms' => $category
    ];
}

if ($difficulty) {
    $tax_query[] = [
        'taxonomy' => 'template_difficulty',
        'field' => 'slug',
        'terms' => $difficulty
    ];
}

if (!empty($_GET['integrations'])) {
    $tax_query[] = [
        'taxonomy' => 'template_integration',
        'field' => 'slug',
        'terms' => array_map('sanitize_text_field', (array)$_GET['integrations']),
        'operator' => 'IN'
    ];
}

if (!empty($tax_query)) {
    $args['tax_query'] = $tax_query;
}

$templates = new WP_Query($args);
$total_found = $templates->found_posts;

// Get all terms for filters
$all_categories = get_terms(['taxonomy' => 'template_category', 'hide_empty' => false]);
$all_difficulties = get_terms(['taxonomy' => 'template_difficulty', 'hide_empty' => false]);
$all_integrations = get_terms(['taxonomy' => 'template_integration', 'hide_empty' => false, 'orderby' => 'name']);
?>

<div class="aiwu-templates-container">
    <div class="aiwu-templates-inner">
        <div class="aiwu-templates-header">
            <h1 class="aiwu-templates-title">Workflow <span>Templates</span></h1>
            <p class="aiwu-templates-subtitle">Ready-to-use automation templates you can preview, customize, and deploy in seconds.</p>
        </div>

        <form method="get" action="<?php echo get_post_type_archive_link('workflow_template'); ?>" class="aiwu-filters-bar">
            <div class="aiwu-filters-left">
                <div class="aiwu-search-wrapper">
                    <span class="aiwu-search-icon">🔍</span>
                    <input type="text" name="s" class="aiwu-search-input" placeholder="Search..." value="<?php echo esc_attr($search); ?>">
                </div>
                <select name="category" class="aiwu-filter-select">
                    <option value="">All Categories</option>
                    <?php foreach ($all_categories as $cat): ?>
                        <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($category, $cat->slug); ?>>
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="difficulty" class="aiwu-filter-select">
                    <option value="">All Difficulty</option>
                    <?php foreach ($all_difficulties as $diff): ?>
                        <option value="<?php echo esc_attr($diff->slug); ?>" <?php selected($difficulty, $diff->slug); ?>>
                            <?php echo esc_html($diff->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="integrations[]" multiple class="aiwu-filter-select aiwu-filter-integrations">
                    <option value="">All Integrations</option>
                    <?php
                    $selected_integrations = isset($_GET['integrations']) ? (array)$_GET['integrations'] : [];
                    foreach ($all_integrations as $int):
                    ?>
                        <option value="<?php echo esc_attr($int->slug); ?>" <?php echo in_array($int->slug, $selected_integrations) ? 'selected' : ''; ?>>
                            <?php echo esc_html($int->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="aiwu-clear-btn aiwu-apply-btn">Apply</button>
                <a href="<?php echo get_post_type_archive_link('workflow_template'); ?>" class="aiwu-clear-btn">Clear all</a>
            </div>
            <div class="aiwu-results-count">
                Showing <?php echo $total_found; ?> template<?php echo $total_found !== 1 ? 's' : ''; ?>
            </div>
        </form>

        <?php if ($templates->have_posts()): ?>
            <div class="aiwu-templates-grid">
                <?php while ($templates->have_posts()): $templates->the_post();
                    $categories = wp_get_post_terms(get_the_ID(), 'template_category');
                    $difficulties = wp_get_post_terms(get_the_ID(), 'template_difficulty');
                    $integrations = wp_get_post_terms(get_the_ID(), 'template_integration', ['orderby' => 'name']);
                    $desc = get_post_meta(get_the_ID(), '_template_description', true);
                ?>
                    <a href="<?php the_permalink(); ?>" class="aiwu-template-card">
                        <?php if (!empty($integrations)): ?>
                            <div class="aiwu-integrations-preview">
                                <?php
                                $visible = array_slice($integrations, 0, 3);
                                $remaining = count($integrations) - 3;

                                foreach ($visible as $integration):
                                    $icon_id = get_term_meta($integration->term_id, 'icon_image_id', true);
                                    $icon_url = $icon_id ? wp_get_attachment_url($icon_id) : '';
                                ?>
                                    <div class="aiwu-integration-icon" title="<?php echo esc_attr($integration->name); ?>">
                                        <?php if ($icon_url): ?>
                                            <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($integration->name); ?>">
                                        <?php else:
                                            $initials = aiwu_get_integration_initials($integration->name);
                                        ?>
                                            <span class="aiwu-integration-initials"><?php echo esc_html($initials); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                                <?php if ($remaining > 0): ?>
                                    <div class="aiwu-integration-more">+<?php echo $remaining; ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="aiwu-template-content">
                            <h2 class="aiwu-template-name"><?php the_title(); ?></h2>
                            <?php if ($desc): ?>
                                <p class="aiwu-template-desc"><?php echo esc_html($desc); ?></p>
                            <?php endif; ?>
                            <div class="aiwu-template-labels">
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <span class="aiwu-template-category"><?php echo esc_html($cat->name); ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if (!empty($difficulties)): ?>
                                    <?php foreach ($difficulties as $diff): ?>
                                        <span class="aiwu-difficulty-badge aiwu-difficulty-<?php echo esc_attr(strtolower($diff->slug)); ?>">
                                            <?php echo esc_html($diff->name); ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>

            <?php
            // Pagination
            $total_pages = $templates->max_num_pages;
            if ($total_pages > 1):
            ?>
                <div class="aiwu-pagination">
                    <?php
                    $base_url = add_query_arg([
                        's' => $search,
                        'category' => $category,
                        'difficulty' => $difficulty,
                        'integrations' => isset($_GET['integrations']) ? $_GET['integrations'] : []
                    ], get_post_type_archive_link('workflow_template'));
                    
                    // Previous button
                    if ($paged > 1):
                        $prev_url = add_query_arg('paged', $paged - 1, $base_url);
                        echo '<a href="' . esc_url($prev_url) . '" class="aiwu-page-btn">←</a>';
                    else:
                        echo '<span class="aiwu-page-btn" disabled>←</span>';
                    endif;
                    
                    // Page numbers
                    if ($paged > 2):
                        $first_url = add_query_arg('paged', 1, $base_url);
                        echo '<a href="' . esc_url($first_url) . '" class="aiwu-page-btn">1</a>';
                        if ($paged > 3):
                            echo '<span class="aiwu-page-dots">...</span>';
                        endif;
                    endif;
                    
                    for ($i = max(1, $paged - 1); $i <= min($total_pages, $paged + 1); $i++):
                        $page_url = add_query_arg('paged', $i, $base_url);
                        $active_class = $i === $paged ? ' active' : '';
                        echo '<a href="' . esc_url($page_url) . '" class="aiwu-page-btn' . $active_class . '">' . $i . '</a>';
                    endfor;
                    
                    if ($paged < $total_pages - 1):
                        if ($paged < $total_pages - 2):
                            echo '<span class="aiwu-page-dots">...</span>';
                        endif;
                        $last_url = add_query_arg('paged', $total_pages, $base_url);
                        echo '<a href="' . esc_url($last_url) . '" class="aiwu-page-btn">' . $total_pages . '</a>';
                    endif;
                    
                    // Next button
                    if ($paged < $total_pages):
                        $next_url = add_query_arg('paged', $paged + 1, $base_url);
                        echo '<a href="' . esc_url($next_url) . '" class="aiwu-page-btn">→</a>';
                    else:
                        echo '<span class="aiwu-page-btn" disabled>→</span>';
                    endif;
                    ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="aiwu-no-results show">
                <div class="aiwu-no-results-icon">🔍</div>
                <div class="aiwu-no-results-text">No templates found</div>
                <div class="aiwu-no-results-hint">Try adjusting your filters or search query</div>
            </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>
        
        <!-- CTA Section -->
        <div class="aiwu-cta-section">
            <div class="aiwu-cta-content">
                <h2 class="aiwu-cta-title">Built with AIWU Workflow Builder</h2>
                <p class="aiwu-cta-description">
                    These templates are created with AIWU Workflow Builder - a free visual automation tool for WordPress. 
                    Copy any template and customize it for your needs in minutes, no coding required.
                </p>
                <div class="aiwu-cta-features">
                    <div class="aiwu-cta-feature">
                        <div class="aiwu-cta-feature-icon">∞</div>
                        <div class="aiwu-cta-feature-text">
                            <div class="aiwu-cta-feature-title">Free Forever</div>
                            <div class="aiwu-cta-feature-desc">No monthly fees, unlimited workflows</div>
                        </div>
                    </div>
                    <div class="aiwu-cta-feature">
                        <div class="aiwu-cta-feature-icon">⚡</div>
                        <div class="aiwu-cta-feature-text">
                            <div class="aiwu-cta-feature-title">Visual Builder</div>
                            <div class="aiwu-cta-feature-desc">Drag-and-drop, no code needed</div>
                        </div>
                    </div>
                    <div class="aiwu-cta-feature">
                        <div class="aiwu-cta-feature-icon">🔑</div>
                        <div class="aiwu-cta-feature-text">
                            <div class="aiwu-cta-feature-title">Your API Keys</div>
                            <div class="aiwu-cta-feature-desc">Use OpenAI, Claude, or any provider</div>
                        </div>
                    </div>
                </div>
                <a href="https://aiwuplugin.com/ai-workflow-automation-builder-for-wordpress/" class="aiwu-cta-button" target="_blank" rel="noopener">
                    Explore Workflow Builder
                    <span class="aiwu-cta-arrow">→</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php do_action('astra_footer'); ?>

<?php wp_footer(); ?>
</body>
</html>

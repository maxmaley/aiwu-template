<?php
/**
 * Plugin Name: AIWU Workflow Templates
 * Description: Custom post type for workflow templates with filtering and search
 * Version: 1.0.0
 * Author: Max
 */

if (!defined('ABSPATH')) exit;

class AIWU_Templates {
    
    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomies']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes']);
        add_filter('template_include', [$this, 'load_templates']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_filter('astra_page_layout', [$this, 'set_full_width']);
        add_filter('astra_get_content_layout', [$this, 'set_full_width']);
        
        // SEO hooks
        add_action('wp_head', [$this, 'add_meta_tags']);
        add_action('wp_head', [$this, 'add_schema_markup']);
        add_filter('document_title_parts', [$this, 'custom_title']);
        add_filter('get_the_archive_title', [$this, 'custom_archive_title']);
    }
    
    public function set_full_width($layout) {
        if (is_post_type_archive('workflow_template') || is_singular('workflow_template')) {
            return 'page-builder';
        }
        return $layout;
    }
    
    public function register_post_type() {
        register_post_type('workflow_template', [
            'labels' => [
                'name' => 'Workflow Templates',
                'singular_name' => 'Template',
                'add_new' => 'Add New Template',
                'edit_item' => 'Edit Template',
                'view_item' => 'View Template',
            ],
            'public' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'workflow-templates'],
            'supports' => ['title', 'thumbnail'],
            'menu_icon' => 'dashicons-networking',
            'show_in_rest' => true,
        ]);
    }
    
    public function register_taxonomies() {
        // Category taxonomy
        register_taxonomy('template_category', 'workflow_template', [
            'hierarchical' => true,
            'labels' => [
                'name' => 'Categories',
                'singular_name' => 'Category',
                'search_items' => 'Search Categories',
                'all_items' => 'All Categories',
                'parent_item' => 'Parent Category',
                'parent_item_colon' => 'Parent Category:',
                'edit_item' => 'Edit Category',
                'update_item' => 'Update Category',
                'add_new_item' => 'Add New Category',
                'new_item_name' => 'New Category Name',
                'menu_name' => 'Categories',
            ],
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'template-category'],
            'show_in_rest' => true,
        ]);
        
        // Difficulty taxonomy
        register_taxonomy('template_difficulty', 'workflow_template', [
            'hierarchical' => false,
            'labels' => [
                'name' => 'Difficulty Levels',
                'singular_name' => 'Difficulty',
                'search_items' => 'Search Difficulties',
                'all_items' => 'All Difficulties',
                'edit_item' => 'Edit Difficulty',
                'update_item' => 'Update Difficulty',
                'add_new_item' => 'Add New Difficulty',
                'new_item_name' => 'New Difficulty Name',
                'menu_name' => 'Difficulty',
            ],
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'difficulty'],
            'show_in_rest' => true,
        ]);
    }
    
    public function add_meta_boxes() {
        add_meta_box('template_details', 'Template Details', [$this, 'render_details_box'], 'workflow_template', 'normal', 'high');
        add_meta_box('template_integrations', 'Integration Icons', [$this, 'render_integrations_box'], 'workflow_template', 'normal', 'high');
        add_meta_box('template_steps', 'Setup Steps', [$this, 'render_steps_box'], 'workflow_template', 'normal', 'high');
        add_meta_box('template_tips', 'Pro Tips', [$this, 'render_tips_box'], 'workflow_template', 'normal', 'default');
    }
    
    public function render_details_box($post) {
        wp_nonce_field('aiwu_template_meta', 'aiwu_template_nonce');

        $description = get_post_meta($post->ID, '_template_description', true);
        $preview_image = get_post_meta($post->ID, '_template_preview_image', true);
        $file_id = get_post_meta($post->ID, '_template_file_id', true);
        ?>
        <p>
            <label><strong>Description:</strong></label><br>
            <textarea name="template_description" rows="3" style="width:100%"><?php echo esc_textarea($description); ?></textarea>
        </p>
        <p>
            <label><strong>Preview Image URL:</strong></label><br>
            <input type="url" name="template_preview_image" value="<?php echo esc_url($preview_image); ?>" style="width:100%">
            <small>Optional: Add a preview image URL or use Featured Image</small>
        </p>
        <p>
            <label><strong>Template File:</strong></label><br>
            <input type="hidden" name="template_file_id" id="template-file-id" value="<?php echo esc_attr($file_id); ?>">
            <button type="button" id="upload-template-file" class="button">
                <?php echo !empty($file_id) ? 'Change Template File' : 'Upload Template File'; ?>
            </button>
            <button type="button" id="remove-template-file" class="button" style="<?php echo empty($file_id) ? 'display:none' : ''; ?>">Remove File</button>
            <div id="template-file-info" style="margin-top:10px">
                <?php
                if (!empty($file_id)) {
                    $file_url = wp_get_attachment_url($file_id);
                    $file_name = basename(get_attached_file($file_id));
                    $file_size = size_format(filesize(get_attached_file($file_id)));
                    echo '<div style="padding:10px;background:#f0f0f0;border-left:3px solid #0073aa">';
                    echo '<strong>' . esc_html($file_name) . '</strong><br>';
                    echo '<small>Size: ' . esc_html($file_size) . '</small>';
                    echo '</div>';
                }
                ?>
            </div>
            <small>Optional: Upload a template file that users can download</small>
        </p>

        <script>
        jQuery(document).ready(function($) {
            // Upload template file
            $('#upload-template-file').on('click', function(e) {
                e.preventDefault();

                const mediaUploader = wp.media({
                    title: 'Choose Template File',
                    button: { text: 'Use this file' },
                    multiple: false
                });

                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#template-file-id').val(attachment.id);

                    const fileSize = attachment.filesizeHumanReadable || '';
                    const fileName = attachment.filename || attachment.title;

                    $('#template-file-info').html(
                        '<div style="padding:10px;background:#f0f0f0;border-left:3px solid #0073aa">' +
                        '<strong>' + fileName + '</strong><br>' +
                        '<small>Size: ' + fileSize + '</small>' +
                        '</div>'
                    );

                    $('#upload-template-file').text('Change Template File');
                    $('#remove-template-file').show();
                });

                mediaUploader.open();
            });

            // Remove template file
            $('#remove-template-file').on('click', function(e) {
                e.preventDefault();
                if (confirm('Remove this file?')) {
                    $('#template-file-id').val('');
                    $('#template-file-info').empty();
                    $('#upload-template-file').text('Upload Template File');
                    $(this).hide();
                }
            });
        });
        </script>
        <?php
    }

    public function render_integrations_box($post) {
        $integrations = get_post_meta($post->ID, '_template_integrations', true);
        if (!is_array($integrations)) $integrations = [];
        ?>
        <div id="integrations-container">
            <p style="margin-bottom:15px;color:#666;">
                Upload small icon images (recommended: 64x64px, square format) for integrations used in this template.
            </p>
            <div class="integrations-list" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:15px;">
                <?php foreach ($integrations as $index => $icon_id):
                    $icon_url = wp_get_attachment_image_url($icon_id, 'thumbnail');
                    if ($icon_url):
                ?>
                    <div class="integration-item" style="position:relative;width:64px;height:64px;border:2px solid #ddd;border-radius:8px;overflow:hidden;">
                        <img src="<?php echo esc_url($icon_url); ?>" style="width:100%;height:100%;object-fit:cover;">
                        <input type="hidden" name="integrations[]" value="<?php echo esc_attr($icon_id); ?>">
                        <button type="button" class="remove-integration" style="position:absolute;top:2px;right:2px;background:#f00;color:#fff;border:none;border-radius:50%;width:20px;height:20px;cursor:pointer;font-size:12px;line-height:1;padding:0;">×</button>
                    </div>
                <?php endif; endforeach; ?>
            </div>
            <button type="button" id="add-integration" class="button button-primary">+ Add Integration Icon</button>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Add integration icon
            $('#add-integration').on('click', function(e) {
                e.preventDefault();

                const mediaUploader = wp.media({
                    title: 'Choose Integration Icon',
                    button: { text: 'Add Icon' },
                    multiple: true,
                    library: { type: 'image' }
                });

                mediaUploader.on('select', function() {
                    const selection = mediaUploader.state().get('selection');
                    selection.each(function(attachment) {
                        const data = attachment.toJSON();
                        const html = `
                            <div class="integration-item" style="position:relative;width:64px;height:64px;border:2px solid #ddd;border-radius:8px;overflow:hidden;">
                                <img src="${data.url}" style="width:100%;height:100%;object-fit:cover;">
                                <input type="hidden" name="integrations[]" value="${data.id}">
                                <button type="button" class="remove-integration" style="position:absolute;top:2px;right:2px;background:#f00;color:#fff;border:none;border-radius:50%;width:20px;height:20px;cursor:pointer;font-size:12px;line-height:1;padding:0;">×</button>
                            </div>
                        `;
                        $('.integrations-list').append(html);
                    });
                });

                mediaUploader.open();
            });

            // Remove integration icon
            $(document).on('click', '.remove-integration', function(e) {
                e.preventDefault();
                $(this).closest('.integration-item').remove();
            });
        });
        </script>
        <?php
    }

    public function render_steps_box($post) {
        $steps = get_post_meta($post->ID, '_template_steps', true);
        if (!is_array($steps)) $steps = [];
        ?>
        <div id="steps-container">
            <?php foreach ($steps as $i => $step): ?>
                <div class="step-item" style="border:1px solid #ddd;padding:20px;margin-bottom:20px;background:#f9f9f9">
                    <h4 style="margin-top:0">
                        Step <?php echo $i + 1; ?> 
                        <button type="button" class="remove-step button" style="float:right">Remove</button>
                    </h4>
                    
                    <p>
                        <label><strong>Title:</strong></label><br>
                        <input type="text" name="steps[<?php echo $i; ?>][title]" value="<?php echo esc_attr($step['title'] ?? ''); ?>" style="width:100%;padding:8px">
                    </p>
                    
                    <p>
                        <label><strong>Description:</strong></label><br>
                        <?php
                        wp_editor(
                            $step['description'] ?? '',
                            'step_description_' . $i,
                            [
                                'textarea_name' => 'steps[' . $i . '][description]',
                                'textarea_rows' => 5,
                                'media_buttons' => false,
                                'teeny' => true,
                                'quicktags' => true,
                            ]
                        );
                        ?>
                    </p>
                    
                    <p>
                        <label><strong>Step Image (optional):</strong></label><br>
                        <input type="hidden" name="steps[<?php echo $i; ?>][image_id]" class="step-image-id" value="<?php echo esc_attr($step['image_id'] ?? ''); ?>">
                        <button type="button" class="upload-step-image button" data-step="<?php echo $i; ?>">
                            <?php echo !empty($step['image_id']) ? 'Change Image' : 'Upload Image'; ?>
                        </button>
                        <button type="button" class="remove-step-image button" data-step="<?php echo $i; ?>" style="<?php echo empty($step['image_id']) ? 'display:none' : ''; ?>">Remove Image</button>
                        <div class="step-image-preview" style="margin-top:10px">
                            <?php if (!empty($step['image_id'])): 
                                $img = wp_get_attachment_image($step['image_id'], 'medium');
                                if ($img) echo $img;
                            endif; ?>
                        </div>
                    </p>
                    
                    <p>
                        <label><strong>Config Items (one per line, format: Label: Value):</strong></label><br>
                        <textarea name="steps[<?php echo $i; ?>][config]" rows="4" style="width:100%;padding:8px;font-family:monospace"><?php echo esc_textarea($step['config'] ?? ''); ?></textarea>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
        
        <button type="button" id="add-step" class="button button-primary">+ Add Step</button>
        
        <script>
        jQuery(document).ready(function($) {
            let stepIndex = <?php echo count($steps); ?>;
            
            // Add new step
            $('#add-step').on('click', function() {
                const container = $('#steps-container');
                const stepHtml = `
                    <div class="step-item" style="border:1px solid #ddd;padding:20px;margin-bottom:20px;background:#f9f9f9">
                        <h4 style="margin-top:0">
                            Step ${stepIndex + 1}
                            <button type="button" class="remove-step button" style="float:right">Remove</button>
                        </h4>
                        <p>
                            <label><strong>Title:</strong></label><br>
                            <input type="text" name="steps[${stepIndex}][title]" style="width:100%;padding:8px">
                        </p>
                        <p>
                            <label><strong>Description:</strong></label><br>
                            <textarea name="steps[${stepIndex}][description]" rows="5" style="width:100%;padding:8px"></textarea>
                        </p>
                        <p>
                            <label><strong>Step Image (optional):</strong></label><br>
                            <input type="hidden" name="steps[${stepIndex}][image_id]" class="step-image-id" value="">
                            <button type="button" class="upload-step-image button" data-step="${stepIndex}">Upload Image</button>
                            <button type="button" class="remove-step-image button" data-step="${stepIndex}" style="display:none">Remove Image</button>
                            <div class="step-image-preview" style="margin-top:10px"></div>
                        </p>
                        <p>
                            <label><strong>Config Items (one per line, format: Label: Value):</strong></label><br>
                            <textarea name="steps[${stepIndex}][config]" rows="4" style="width:100%;padding:8px;font-family:monospace"></textarea>
                        </p>
                    </div>
                `;
                container.append(stepHtml);
                stepIndex++;
            });
            
            // Remove step
            $(document).on('click', '.remove-step', function() {
                if (confirm('Remove this step?')) {
                    $(this).closest('.step-item').remove();
                }
            });
            
            // Upload image
            $(document).on('click', '.upload-step-image', function(e) {
                e.preventDefault();
                const button = $(this);
                const stepIndex = button.data('step');
                const stepItem = button.closest('.step-item');
                
                const mediaUploader = wp.media({
                    title: 'Choose Step Image',
                    button: { text: 'Use this image' },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    stepItem.find('.step-image-id').val(attachment.id);
                    stepItem.find('.step-image-preview').html('<img src="' + attachment.url + '" style="max-width:300px;height:auto">');
                    button.text('Change Image');
                    stepItem.find('.remove-step-image').show();
                });
                
                mediaUploader.open();
            });
            
            // Remove image
            $(document).on('click', '.remove-step-image', function(e) {
                e.preventDefault();
                const stepItem = $(this).closest('.step-item');
                stepItem.find('.step-image-id').val('');
                stepItem.find('.step-image-preview').empty();
                stepItem.find('.upload-step-image').text('Upload Image');
                $(this).hide();
            });
        });
        </script>
        <?php
    }
    
    public function render_tips_box($post) {
        $tips = get_post_meta($post->ID, '_template_tips', true);
        if (!is_array($tips)) $tips = [];
        ?>
        <div id="tips-container">
            <?php foreach ($tips as $i => $tip): ?>
                <div class="tip-item" style="margin-bottom:10px">
                    <input type="text" name="tips[]" value="<?php echo esc_attr($tip); ?>" style="width:85%">
                    <button type="button" class="remove-tip button">Remove</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-tip" class="button button-primary">+ Add Tip</button>
        
        <script>
        document.getElementById('add-tip').addEventListener('click', function() {
            const container = document.getElementById('tips-container');
            const div = document.createElement('div');
            div.className = 'tip-item';
            div.style.marginBottom = '10px';
            div.innerHTML = '<input type="text" name="tips[]" style="width:85%"> <button type="button" class="remove-tip button">Remove</button>';
            container.appendChild(div);
        });
        
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-tip')) {
                e.target.closest('.tip-item').remove();
            }
        });
        </script>
        <?php
    }
    
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['aiwu_template_nonce']) || !wp_verify_nonce($_POST['aiwu_template_nonce'], 'aiwu_template_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['template_description'])) {
            update_post_meta($post_id, '_template_description', sanitize_textarea_field($_POST['template_description']));
        }

        if (isset($_POST['template_preview_image'])) {
            update_post_meta($post_id, '_template_preview_image', esc_url_raw($_POST['template_preview_image']));
        }

        if (isset($_POST['template_file_id'])) {
            $file_id = absint($_POST['template_file_id']);
            if ($file_id > 0) {
                update_post_meta($post_id, '_template_file_id', $file_id);
            } else {
                delete_post_meta($post_id, '_template_file_id');
            }
        }

        if (isset($_POST['integrations'])) {
            $integrations = array_map('absint', $_POST['integrations']);
            $integrations = array_filter($integrations);
            update_post_meta($post_id, '_template_integrations', $integrations);
        } else {
            delete_post_meta($post_id, '_template_integrations');
        }

        if (isset($_POST['steps'])) {
            $steps = array_map(function($step) {
                return [
                    'title' => sanitize_text_field($step['title'] ?? ''),
                    'description' => wp_kses_post($step['description'] ?? ''), // Allow safe HTML
                    'image_id' => absint($step['image_id'] ?? 0),
                    'config' => sanitize_textarea_field($step['config'] ?? ''),
                ];
            }, $_POST['steps']);
            update_post_meta($post_id, '_template_steps', $steps);
        }
        
        if (isset($_POST['tips'])) {
            $tips = array_map('sanitize_text_field', $_POST['tips']);
            update_post_meta($post_id, '_template_tips', array_filter($tips));
        }
    }
    
    public function load_templates($template) {
        if (is_post_type_archive('workflow_template')) {
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/archive-workflow_template.php';
            if (file_exists($plugin_template)) return $plugin_template;
        }
        
        if (is_singular('workflow_template')) {
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/single-workflow_template.php';
            if (file_exists($plugin_template)) return $plugin_template;
        }
        
        return $template;
    }
    
    public function enqueue_styles() {
        if (is_post_type_archive('workflow_template') || is_singular('workflow_template')) {
            wp_enqueue_style('aiwu-templates', plugin_dir_url(__FILE__) . 'assets/styles.css', [], '1.0.0');
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        if ($post_type === 'workflow_template' && ($hook === 'post.php' || $hook === 'post-new.php')) {
            wp_enqueue_media();
        }
    }
    
    // SEO Functions
    
    public function custom_title($title) {
        if (is_post_type_archive('workflow_template')) {
            $title['title'] = 'Workflow Templates - Automation Templates Library';
        } elseif (is_singular('workflow_template')) {
            $title['title'] = get_the_title() . ' - Workflow Template';
        }
        return $title;
    }
    
    public function custom_archive_title($title) {
        if (is_post_type_archive('workflow_template')) {
            return 'Workflow Templates';
        }
        return $title;
    }
    
    public function add_meta_tags() {
        if (is_post_type_archive('workflow_template')) {
            echo '<meta name="description" content="Browse our collection of ready-to-use workflow automation templates. Preview, customize, and deploy in seconds.">' . "\n";
            echo '<meta name="robots" content="index, follow">' . "\n";
            echo '<link rel="canonical" href="' . esc_url(get_post_type_archive_link('workflow_template')) . '">' . "\n";
        } elseif (is_singular('workflow_template')) {
            $description = get_post_meta(get_the_ID(), '_template_description', true);
            if ($description) {
                echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
            }
            echo '<meta name="robots" content="index, follow">' . "\n";
            echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '">' . "\n";
            
            // Open Graph
            echo '<meta property="og:title" content="' . esc_attr(get_the_title()) . ' - Workflow Template">' . "\n";
            if ($description) {
                echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
            }
            echo '<meta property="og:type" content="article">' . "\n";
            echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
            
            $thumb = get_the_post_thumbnail_url(get_the_ID(), 'large');
            if ($thumb) {
                echo '<meta property="og:image" content="' . esc_url($thumb) . '">' . "\n";
            }
        }
    }
    
    public function add_schema_markup() {
        if (is_singular('workflow_template')) {
            $description = get_post_meta(get_the_ID(), '_template_description', true);
            $categories = wp_get_post_terms(get_the_ID(), 'template_category', ['fields' => 'names']);
            $steps = get_post_meta(get_the_ID(), '_template_steps', true);
            
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'HowTo',
                'name' => get_the_title(),
                'description' => $description ?: 'Workflow automation template',
                'url' => get_permalink(),
            ];
            
            if (!empty($steps) && is_array($steps)) {
                $schema['step'] = [];
                foreach ($steps as $index => $step) {
                    if (!empty($step['title'])) {
                        $step_data = [
                            '@type' => 'HowToStep',
                            'position' => $index + 1,
                            'name' => $step['title'],
                            'text' => wp_strip_all_tags($step['description'] ?? '')
                        ];
                        
                        // Add step image if exists
                        if (!empty($step['image_id'])) {
                            $img_url = wp_get_attachment_image_url($step['image_id'], 'large');
                            if ($img_url) {
                                $step_data['image'] = $img_url;
                            }
                        }
                        
                        $schema['step'][] = $step_data;
                    }
                }
            }
            
            $thumb = get_the_post_thumbnail_url(get_the_ID(), 'large');
            if ($thumb) {
                $schema['image'] = $thumb;
            }
            
            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
        }
    }
}

new AIWU_Templates();
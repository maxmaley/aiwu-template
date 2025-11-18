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

<?php while (have_posts()) : the_post(); 
    $description = get_post_meta(get_the_ID(), '_template_description', true);
    $categories = wp_get_post_terms(get_the_ID(), 'template_category');
    $difficulties = wp_get_post_terms(get_the_ID(), 'template_difficulty');
    $preview_image = get_post_meta(get_the_ID(), '_template_preview_image', true);
    $steps = get_post_meta(get_the_ID(), '_template_steps', true);
    $tips = get_post_meta(get_the_ID(), '_template_tips', true);
    $thumb = get_the_post_thumbnail_url(get_the_ID(), 'full');
?>

<div class="aiwu-template-detail">
  <div class="aiwu-template-container">
    
    <!-- Breadcrumbs for SEO -->
    <nav class="aiwu-breadcrumbs" aria-label="Breadcrumb">
      <ol itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
          <a itemprop="item" href="<?php echo home_url(); ?>">
            <span itemprop="name">Home</span>
          </a>
          <meta itemprop="position" content="1" />
        </li>
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
          <a itemprop="item" href="<?php echo get_post_type_archive_link('workflow_template'); ?>">
            <span itemprop="name">Workflow Templates</span>
          </a>
          <meta itemprop="position" content="2" />
        </li>
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
          <span itemprop="name"><?php the_title(); ?></span>
          <meta itemprop="position" content="3" />
        </li>
      </ol>
    </nav>

    <div class="aiwu-template-hero">
      <div class="aiwu-template-header">
        <div class="aiwu-template-title-group">
          <h1 class="aiwu-template-title"><?php the_title(); ?></h1>
          <div class="aiwu-template-meta">
            <?php if (!empty($categories)): ?>
              <?php foreach ($categories as $cat): ?>
                <span class="aiwu-template-badge aiwu-badge-category"><?php echo esc_html($cat->name); ?></span>
              <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($difficulties)): ?>
              <?php foreach ($difficulties as $diff): ?>
                <span class="aiwu-template-badge aiwu-badge-difficulty"><?php echo esc_html($diff->name); ?></span>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <?php
          $integrations = wp_get_post_terms(get_the_ID(), 'template_integration', ['orderby' => 'name']);
          if (!empty($integrations)):
          ?>
            <div class="aiwu-integrations-list">
              <?php foreach ($integrations as $integration):
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
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($description): ?>
        <p class="aiwu-template-description">
          <?php echo esc_html($description); ?>
        </p>
      <?php endif; ?>

      <?php if ($thumb || $preview_image): ?>
        <div class="aiwu-template-preview">
          <img 
            src="<?php echo esc_url($thumb ?: $preview_image); ?>" 
            alt="<?php echo esc_attr(get_the_title()); ?> workflow template preview" 
            title="<?php echo esc_attr(get_the_title()); ?> automation workflow"
            class="aiwu-preview-image"
            loading="lazy">
        </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($steps) && is_array($steps)): ?>
    <div class="aiwu-template-steps">
      <h2 class="aiwu-steps-title">
        📋 How to Build This Workflow
      </h2>

      <?php foreach ($steps as $index => $step): ?>
        <div class="aiwu-step-item">
          <div class="aiwu-step-number"><?php echo $index + 1; ?></div>
          <div class="aiwu-step-content">
            <?php if (!empty($step['title'])): ?>
              <h3 class="aiwu-step-title"><?php echo esc_html($step['title']); ?></h3>
            <?php endif; ?>
            
            <?php if (!empty($step['description'])): ?>
              <div class="aiwu-step-description">
                <?php echo wp_kses_post($step['description']); ?>
              </div>
            <?php endif; ?>
            
            <?php if (!empty($step['image_id'])): 
                $step_img = wp_get_attachment_image($step['image_id'], 'large', false, [
                    'class' => 'aiwu-step-image',
                    'loading' => 'lazy'
                ]);
                if ($step_img):
            ?>
              <div class="aiwu-step-image-wrapper">
                <?php echo $step_img; ?>
              </div>
            <?php endif; endif; ?>
            
            <?php if (!empty($step['config'])): 
                $config_lines = explode("\n", $step['config']);
                $config_lines = array_filter(array_map('trim', $config_lines));
                if (!empty($config_lines)):
            ?>
              <div class="aiwu-step-config">
                <?php foreach ($config_lines as $line): 
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2):
                ?>
                  <div class="aiwu-config-item">
                    <span class="aiwu-config-label"><?php echo esc_html(trim($parts[0])); ?>:</span>
                    <span class="aiwu-config-value"><?php echo esc_html(trim($parts[1])); ?></span>
                  </div>
                <?php endif; endforeach; ?>
              </div>
            <?php endif; endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($tips) && is_array($tips)): ?>
    <div class="aiwu-template-tips">
      <h3 class="aiwu-tips-title">
        💡 Pro Tips
      </h3>
      <ul class="aiwu-tips-list">
        <?php foreach ($tips as $tip): 
          if (!empty($tip)):
        ?>
          <li class="aiwu-tip-item">
            <span class="aiwu-tip-icon">✓</span>
            <span><?php echo esc_html($tip); ?></span>
          </li>
        <?php endif; endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
    
    <!-- CTA Section -->
    <div class="aiwu-cta-section aiwu-cta-single">
        <div class="aiwu-cta-content">
            <h2 class="aiwu-cta-title">Ready to Build This Workflow?</h2>
            <p class="aiwu-cta-description">
                AIWU Workflow Builder lets you recreate this template in minutes without writing code. 
                Free visual automation tool that runs directly on your WordPress site.
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

<?php endwhile; ?>

<?php do_action('astra_footer'); ?>

<?php wp_footer(); ?>
</body>
</html>

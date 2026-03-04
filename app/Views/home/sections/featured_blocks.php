<?php
// app/Views/home/sections/featured_blocks.php

$config = $section->config ?? [];
$title = $section->title ?? 'Featured Blocks';
$blocks = $config['blocks'] ?? [];
$columns = (int)($config['columns'] ?? 3);
$bgColor = $config['background_color'] ?? '#ffffff';
$textColor = $config['text_color'] ?? '#333333';
$colClass = match($columns) {
    2 => 'col-md-6',
    4 => 'col-md-3',
    default => 'col-md-4'
};
?>
<section class="featured-blocks py-5" style="background-color: <?php echo $bgColor; ?>; color: <?php echo $textColor; ?>;">
    <div class="container">
        <?php if ($title): ?>
            <h2 class="text-center mb-5"><?php echo htmlspecialchars($title); ?></h2>
        <?php endif; ?>
        
        <div class="row g-4">
            <?php foreach ($blocks as $block): ?>
                <div class="<?php echo $colClass; ?>">
                    <div class="featured-block h-100">
                        <?php if (!empty($block['icon'])): ?>
                            <div class="mb-4">
                                <i class="<?php echo htmlspecialchars($block['icon']); ?> fa-3x" style="color: #667eea;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <h3 class="h4 mb-3"><?php echo htmlspecialchars($block['title'] ?? ''); ?></h3>
                        
                        <?php if (!empty($block['description'])): ?>
                            <p class="mb-4"><?php echo nl2br(htmlspecialchars($block['description'])); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($block['button_text']) && !empty($block['link'])): ?>
                            <a href="<?php echo htmlspecialchars($block['link']); ?>" class="btn btn-primary">
                                <?php echo htmlspecialchars($block['button_text']); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
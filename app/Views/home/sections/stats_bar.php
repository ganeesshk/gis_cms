<?php
// app/Views/home/sections/stats_bar.php

$config = $section->config ?? [];
$title = $section->title ?? 'Our Impact';
$stats = $config['stats'] ?? [];
$columns = (int)($config['columns'] ?? 4);
$bgColor = $config['background_color'] ?? '#667eea';
$textColor = $config['text_color'] ?? '#ffffff';
$colClass = match($columns) {
    2 => 'col-md-6',
    3 => 'col-md-4',
    default => 'col-md-3'
};
?>
<section class="stats-bar py-5" style="background-color: <?php echo $bgColor; ?>; color: <?php echo $textColor; ?>;">
    <div class="container">
        <?php if ($title): ?>
            <h2 class="text-center mb-5"><?php echo htmlspecialchars($title); ?></h2>
        <?php endif; ?>
        
        <div class="row g-4">
            <?php foreach ($stats as $stat): ?>
                <div class="<?php echo $colClass; ?>">
                    <div class="stat-item">
                        <?php if (!empty($stat['icon'])): ?>
                            <i class="<?php echo htmlspecialchars($stat['icon']); ?> fa-3x mb-3"></i>
                        <?php endif; ?>
                        <div class="stat-number mb-2"><?php echo htmlspecialchars($stat['value'] ?? '0'); ?></div>
                        <div class="stat-label"><?php echo htmlspecialchars($stat['label'] ?? ''); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
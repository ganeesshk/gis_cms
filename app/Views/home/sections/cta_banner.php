<?php
// app/Views/home/sections/cta_banner.php

$config = $section->config ?? [];
$title = $section->title ?? 'Ready to Get Started?';
$description = $config['description'] ?? '';
$buttonText = $config['button_text'] ?? 'Get Started';
$buttonLink = $config['button_link'] ?? '#';
$buttonStyle = $config['button_style'] ?? 'primary';
$alignment = $config['alignment'] ?? 'center';
$bgImage = $config['background_image'] ?? '';
$bgColor = $config['background_color'] ?? '#667eea';
$textColor = $config['text_color'] ?? '#ffffff';

$btnClass = match($buttonStyle) {
    'secondary' => 'btn-secondary',
    'outline' => 'btn-outline-light',
    default => 'btn-primary'
};

$textAlign = match($alignment) {
    'left' => 'text-start',
    'right' => 'text-end',
    default => 'text-center'
};
?>
<section class="cta-banner py-5" style="background-color: <?php echo $bgColor; ?>; color: <?php echo $textColor; ?>; <?php if ($bgImage): ?>background-image: url('<?php echo htmlspecialchars($bgImage); ?>'); background-size: cover; background-position: center;<?php endif; ?>">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 <?php echo $textAlign; ?>">
                <h2 class="display-5 mb-4"><?php echo htmlspecialchars($title); ?></h2>
                
                <?php if ($description): ?>
                    <p class="lead mb-5"><?php echo htmlspecialchars($description); ?></p>
                <?php endif; ?>
                
                <a href="<?php echo htmlspecialchars($buttonLink); ?>" class="btn <?php echo $btnClass; ?> btn-lg">
                    <?php echo htmlspecialchars($buttonText); ?>
                </a>
            </div>
        </div>
    </div>
</section>
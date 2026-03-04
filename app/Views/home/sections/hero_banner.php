<?php
// app/Views/home/sections/hero_banner.php

$config = $section->config ?? [];
$headline = $config['headline'] ?? 'Welcome to Our Website';
$subheadline = $config['subheadline'] ?? '';
$bgImage = $config['background_image'] ?? '';
$bgColor = $config['background_color'] ?? '#667eea';
$textColor = $config['text_color'] ?? '#ffffff';
$overlayOpacity = $config['overlay_opacity'] ?? 0.5;
$height = $config['height'] ?? '500px';
$btn1Text = $config['button_primary_text'] ?? 'Get Started';
$btn1Link = $config['button_primary_link'] ?? '#';
$btn2Text = $config['button_secondary_text'] ?? 'Learn More';
$btn2Link = $config['button_secondary_link'] ?? '#';
?>
<section class="hero-banner" style="height: <?php echo $height; ?>; background-color: <?php echo $bgColor; ?>; <?php if ($bgImage): ?>background-image: url('<?php echo htmlspecialchars($bgImage); ?>'); background-size: cover; background-position: center;<?php endif; ?>">
    <?php if ($bgImage): ?>
        <div class="hero-overlay" style="opacity: <?php echo $overlayOpacity; ?>;"></div>
    <?php endif; ?>
    
    <div class="hero-content" style="color: <?php echo $textColor; ?>;">
        <h1 class="display-3 mb-4"><?php echo htmlspecialchars($headline); ?></h1>
        <?php if ($subheadline): ?>
            <p class="lead mb-5"><?php echo htmlspecialchars($subheadline); ?></p>
        <?php endif; ?>
        
        <div class="hero-buttons">
            <?php if ($btn1Text): ?>
                <a href="<?php echo htmlspecialchars($btn1Link); ?>" class="btn btn-light btn-lg me-3"><?php echo htmlspecialchars($btn1Text); ?></a>
            <?php endif; ?>
            <?php if ($btn2Text): ?>
                <a href="<?php echo htmlspecialchars($btn2Link); ?>" class="btn btn-outline-light btn-lg"><?php echo htmlspecialchars($btn2Text); ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>
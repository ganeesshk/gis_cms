<?php
// app/Views/home/sections/contact_bar.php

$config = $section->config ?? [];
$title = $section->title ?? 'Get in Touch';
$address = $config['address'] ?? '';
$phone = $config['phone'] ?? '';
$email = $config['email'] ?? '';
$showSocial = $config['show_social'] ?? true;
$socialFacebook = $config['social_facebook'] ?? '';
$socialTwitter = $config['social_twitter'] ?? '';
$socialInstagram = $config['social_instagram'] ?? '';
$socialLinkedin = $config['social_linkedin'] ?? '';
$bgColor = $config['background_color'] ?? '#f8f9fa';
$textColor = $config['text_color'] ?? '#333333';
?>
<section class="contact-bar py-5" style="background-color: <?php echo $bgColor; ?>; color: <?php echo $textColor; ?>;">
    <div class="container">
        <?php if ($title): ?>
            <h2 class="text-center mb-5"><?php echo htmlspecialchars($title); ?></h2>
        <?php endif; ?>
        
        <div class="contact-info">
            <?php if ($address): ?>
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt fa-2x" style="color: #667eea;"></i>
                    <span><?php echo nl2br(htmlspecialchars($address)); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($phone): ?>
                <div class="contact-item">
                    <i class="fas fa-phone-alt fa-2x" style="color: #667eea;"></i>
                    <span><?php echo htmlspecialchars($phone); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($email): ?>
                <div class="contact-item">
                    <i class="fas fa-envelope fa-2x" style="color: #667eea;"></i>
                    <span><?php echo htmlspecialchars($email); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($showSocial && ($socialFacebook || $socialTwitter || $socialInstagram || $socialLinkedin)): ?>
            <div class="social-links">
                <?php if ($socialFacebook): ?>
                    <a href="<?php echo htmlspecialchars($socialFacebook); ?>" class="social-link" style="background: #3b5998; color: white;" target="_blank">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                <?php endif; ?>
                
                <?php if ($socialTwitter): ?>
                    <a href="<?php echo htmlspecialchars($socialTwitter); ?>" class="social-link" style="background: #1da1f2; color: white;" target="_blank">
                        <i class="fab fa-twitter"></i>
                    </a>
                <?php endif; ?>
                
                <?php if ($socialInstagram): ?>
                    <a href="<?php echo htmlspecialchars($socialInstagram); ?>" class="social-link" style="background: #e4405f; color: white;" target="_blank">
                        <i class="fab fa-instagram"></i>
                    </a>
                <?php endif; ?>
                
                <?php if ($socialLinkedin): ?>
                    <a href="<?php echo htmlspecialchars($socialLinkedin); ?>" class="social-link" style="background: #0077b5; color: white;" target="_blank">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
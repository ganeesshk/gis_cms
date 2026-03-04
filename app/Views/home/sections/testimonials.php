<?php
// app/Views/home/sections/testimonials.php

$config = $section->config ?? [];
$title = $section->title ?? 'What Our Clients Say';
$testimonials = $config['testimonials'] ?? [];
$autoplay = $config['autoplay'] ?? true;
$autoplaySpeed = (int)($config['autoplay_speed'] ?? 5000);
$showArrows = $config['show_arrows'] ?? true;
$showDots = $config['show_dots'] ?? true;
$carouselId = 'testimonialCarousel_' . uniqid();
?>
<section class="testimonials py-5">
    <div class="container">
        <?php if ($title): ?>
            <h2 class="text-center mb-5"><?php echo htmlspecialchars($title); ?></h2>
        <?php endif; ?>
        
        <?php if (!empty($testimonials)): ?>
            <div id="<?php echo $carouselId; ?>" class="carousel slide" data-bs-ride="<?php echo $autoplay ? 'carousel' : 'false'; ?>" data-bs-interval="<?php echo $autoplaySpeed; ?>">
                <?php if ($showDots): ?>
                    <div class="carousel-indicators">
                        <?php foreach ($testimonials as $index => $testimonial): ?>
                            <button type="button" data-bs-target="#<?php echo $carouselId; ?>" 
                                    data-bs-slide-to="<?php echo $index; ?>" 
                                    class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="carousel-inner">
                    <?php foreach ($testimonials as $index => $testimonial): ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="testimonial-card mx-auto" style="max-width: 800px;">
                                <?php if (!empty($testimonial['rating'])): ?>
                                    <div class="rating mb-3">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $testimonial['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="lead mb-4">"<?php echo htmlspecialchars($testimonial['content'] ?? ''); ?>"</p>
                                
                                <div class="d-flex align-items-center justify-content-center">
                                    <?php if (!empty($testimonial['avatar'])): ?>
                                        <img src="<?php echo htmlspecialchars($testimonial['avatar']); ?>" 
                                             alt="<?php echo htmlspecialchars($testimonial['name'] ?? ''); ?>"
                                             class="rounded-circle me-3"
                                             style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php endif; ?>
                                    <div class="text-start">
                                        <div class="fw-bold"><?php echo htmlspecialchars($testimonial['name'] ?? ''); ?></div>
                                        <?php if (!empty($testimonial['position'])): ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars($testimonial['position']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($showArrows): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <p class="text-muted">No testimonials added yet.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
// app/Views/home/sections/photo_gallery_preview.php

use App\Models\PhotoGallery;

$config = $section->config ?? [];
$title = $section->title ?? 'Photo Gallery';
$galleryId = $config['gallery_id'] ?? null;
$thumbnailCount = (int)($config['thumbnail_count'] ?? 6);
$layout = $config['layout'] ?? 'grid';
$columns = (int)($config['columns'] ?? 3);
$showTitle = $config['show_title'] ?? true;
$linkText = $config['link_text'] ?? 'View All Photos';
$linkUrl = $config['link_url'] ?? '/galleries/photo';

$gallery = $galleryId ? PhotoGallery::find($galleryId) : null;
$photos = $gallery ? $gallery->getPhotos($thumbnailCount) : [];

$colClass = match($columns) {
    2 => 'col-md-6',
    4 => 'col-md-3',
    default => 'col-md-4'
};
?>
<section class="photo-gallery-preview py-5">
    <div class="container">
        <?php if ($title): ?>
            <h2 class="text-center mb-5"><?php echo htmlspecialchars($title); ?></h2>
        <?php endif; ?>
        
        <?php if ($gallery && !empty($photos)): ?>
            <?php if ($showTitle): ?>
                <h3 class="h4 text-center mb-4"><?php echo htmlspecialchars($gallery->name); ?></h3>
                <?php if ($gallery->description): ?>
                    <p class="text-center text-muted mb-5"><?php echo htmlspecialchars($gallery->description); ?></p>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($layout === 'grid'): ?>
                <div class="row g-4">
                    <?php foreach ($photos as $photo): ?>
                        <div class="<?php echo $colClass; ?>">
                            <a href="<?php echo $linkUrl; ?>" class="text-decoration-none">
                                <div class="card h-100 shadow-sm">
                                    <img src="<?php echo htmlspecialchars($photo->getThumbnail('medium')); ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($photo->alt_text ?: $photo->title); ?>"
                                         style="height: 200px; object-fit: cover;">
                                    <?php if ($photo->title): ?>
                                        <div class="card-body">
                                            <h5 class="card-title text-dark"><?php echo htmlspecialchars($photo->title); ?></h5>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div id="galleryCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($photos as $index => $photo): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($photo->getThumbnail('large')); ?>" 
                                     class="d-block w-100" 
                                     alt="<?php echo htmlspecialchars($photo->alt_text ?: $photo->title); ?>"
                                     style="height: 400px; object-fit: cover;">
                                <?php if ($photo->title): ?>
                                    <div class="carousel-caption d-none d-md-block">
                                        <h5><?php echo htmlspecialchars($photo->title); ?></h5>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-5">
                <a href="<?php echo htmlspecialchars($linkUrl); ?>" class="btn btn-primary">
                    <?php echo htmlspecialchars($linkText); ?> <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <p class="text-muted">No gallery selected or gallery is empty.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
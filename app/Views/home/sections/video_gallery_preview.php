<?php
// app/Views/home/sections/video_gallery_preview.php

use App\Models\VideoGallery;

$config = $section->config ?? [];
$title = $section->title ?? 'Video Gallery';
$galleryId = $config['gallery_id'] ?? null;
$videoCount = (int)($config['video_count'] ?? 3);
$layout = $config['layout'] ?? 'grid';
$columns = (int)($config['columns'] ?? 3);
$showTitle = $config['show_title'] ?? true;
$linkText = $config['link_text'] ?? 'View All Videos';
$linkUrl = $config['link_url'] ?? '/galleries/video';

$gallery = $galleryId ? VideoGallery::find($galleryId) : null;
$videos = $gallery ? $gallery->getVideos($videoCount) : [];

$colClass = match($columns) {
    2 => 'col-md-6',
    4 => 'col-md-3',
    default => 'col-md-4'
};
?>
<section class="video-gallery-preview py-5">
    <div class="container">
        <?php if ($title): ?>
            <h2 class="text-center mb-5"><?php echo htmlspecialchars($title); ?></h2>
        <?php endif; ?>
        
        <?php if ($gallery && !empty($videos)): ?>
            <?php if ($showTitle): ?>
                <h3 class="h4 text-center mb-4"><?php echo htmlspecialchars($gallery->name); ?></h3>
                <?php if ($gallery->description): ?>
                    <p class="text-center text-muted mb-5"><?php echo htmlspecialchars($gallery->description); ?></p>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($layout === 'grid'): ?>
                <div class="row g-4">
                    <?php foreach ($videos as $video): ?>
                        <div class="<?php echo $colClass; ?>">
                            <div class="card h-100 shadow-sm">
                                <div class="position-relative">
                                    <img src="<?php echo htmlspecialchars($video->thumbnail_url ?: 'https://img.youtube.com/vi/' . $video->youtube_id . '/mqdefault.jpg'); ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($video->title); ?>"
                                         style="height: 180px; object-fit: cover;">
                                    <div class="position-absolute top-50 start-50 translate-middle">
                                        <a href="https://www.youtube.com/watch?v=<?php echo $video->youtube_id; ?>" 
                                           class="btn btn-light rounded-circle p-3" 
                                           target="_blank">
                                            <i class="fab fa-youtube text-danger fa-2x"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($video->title); ?></h5>
                                    <?php if ($video->description): ?>
                                        <p class="card-text small text-muted"><?php echo htmlspecialchars(substr($video->description, 0, 100)) . '...'; ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="ratio ratio-16x9 mb-4">
                            <iframe src="https://www.youtube-nocookie.com/embed/<?php echo $videos[0]->youtube_id; ?>" 
                                    allowfullscreen></iframe>
                        </div>
                        <h4><?php echo htmlspecialchars($videos[0]->title); ?></h4>
                        <p><?php echo htmlspecialchars($videos[0]->description); ?></p>
                    </div>
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
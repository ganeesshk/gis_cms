<?php
// app/Views/home/sections/latest_pages.php

use App\Models\Page;

$config = $section->config ?? [];
$title = $section->title ?? 'Latest News';
$count = (int)($config['count'] ?? 3);
$layout = $config['layout'] ?? 'grid';
$columns = (int)($config['columns'] ?? 3);
$showExcerpt = $config['show_excerpt'] ?? true;
$showDate = $config['show_date'] ?? true;
$showAuthor = $config['show_author'] ?? false;
$showImage = $config['show_featured_image'] ?? true;

// Get latest published pages
$pages = Page::where(['status' => 'published', 'deleted_at' => null])
             ->orderBy('published_at', 'DESC')
             ->limit($count)
             ->get();

$colClass = match($columns) {
    2 => 'col-md-6',
    4 => 'col-md-3',
    default => 'col-md-4'
};
?>
<section class="latest-pages py-5">
    <div class="container">
        <?php if ($title): ?>
            <h2 class="text-center mb-5"><?php echo htmlspecialchars($title); ?></h2>
        <?php endif; ?>
        
        <?php if ($layout === 'grid'): ?>
            <div class="row g-4">
                <?php foreach ($pages as $page): ?>
                    <div class="<?php echo $colClass; ?>">
                        <div class="card h-100 shadow-sm">
                            <?php if ($showImage && $page->featured_image_path): ?>
                                <img src="<?php echo htmlspecialchars($page->featured_image_path); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($page->title); ?>"
                                     style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h3 class="h5 card-title">
                                    <a href="/<?php echo $page->slug; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($page->title); ?>
                                    </a>
                                </h3>
                                
                                <?php if ($showDate || $showAuthor): ?>
                                    <p class="small text-muted mb-2">
                                        <?php if ($showDate): ?>
                                            <i class="far fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($page->published_at)); ?>
                                        <?php endif; ?>
                                        <?php if ($showAuthor && $page->author): ?>
                                            <i class="far fa-user ms-2"></i> <?php echo htmlspecialchars($page->author->username); ?>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($showExcerpt && $page->excerpt): ?>
                                    <p class="card-text"><?php echo htmlspecialchars($page->excerpt); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-footer bg-transparent border-0 pb-3">
                                <a href="/<?php echo $page->slug; ?>" class="btn btn-sm btn-outline-primary">
                                    Read More <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($pages as $page): ?>
                    <a href="/<?php echo $page->slug; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?php echo htmlspecialchars($page->title); ?></h5>
                            <?php if ($showDate): ?>
                                <small><?php echo date('M j, Y', strtotime($page->published_at)); ?></small>
                            <?php endif; ?>
                        </div>
                        <?php if ($showExcerpt && $page->excerpt): ?>
                            <p class="mb-1"><?php echo htmlspecialchars($page->excerpt); ?></p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
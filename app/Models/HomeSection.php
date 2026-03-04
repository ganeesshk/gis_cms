<?php
// app/Models/HomeSection.php

namespace App\Models;

class HomeSection extends BaseModel
{
    protected static string $table = 'home_page_sections';
    
    protected array $fillable = [
        'section_type', 'title', 'config', 'sort_order', 'is_visible', 'updated_by'
    ];
    
    protected array $casts = [
        'config' => 'json',
        'is_visible' => 'boolean',
        'sort_order' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const TYPE_HERO_BANNER = 'hero_banner';
    const TYPE_FEATURED_BLOCKS = 'featured_blocks';
    const TYPE_LATEST_PAGES = 'latest_pages';
    const TYPE_PHOTO_GALLERY_PREVIEW = 'photo_gallery_preview';
    const TYPE_VIDEO_GALLERY_PREVIEW = 'video_gallery_preview';
    const TYPE_CUSTOM_HTML = 'custom_html';
    const TYPE_CONTACT_BAR = 'contact_bar';
    const TYPE_STATS_BAR = 'stats_bar';
    const TYPE_TESTIMONIALS = 'testimonials';
    const TYPE_CTA_BANNER = 'cta_banner';

    public function updater()
    {
        return User::find($this->updated_by);
    }

    public function getSectionTypeLabel()
    {
        $labels = [
            self::TYPE_HERO_BANNER => 'Hero Banner',
            self::TYPE_FEATURED_BLOCKS => 'Featured Blocks',
            self::TYPE_LATEST_PAGES => 'Latest Pages',
            self::TYPE_PHOTO_GALLERY_PREVIEW => 'Photo Gallery Preview',
            self::TYPE_VIDEO_GALLERY_PREVIEW => 'Video Gallery Preview',
            self::TYPE_CUSTOM_HTML => 'Custom HTML',
            self::TYPE_CONTACT_BAR => 'Contact Bar',
            self::TYPE_STATS_BAR => 'Stats Bar',
            self::TYPE_TESTIMONIALS => 'Testimonials',
            self::TYPE_CTA_BANNER => 'CTA Banner'
        ];
        
        return $labels[$this->section_type] ?? ucfirst(str_replace('_', ' ', $this->section_type));
    }

    public function getIcon()
    {
        $icons = [
            self::TYPE_HERO_BANNER => 'fas fa-image',
            self::TYPE_FEATURED_BLOCKS => 'fas fa-th-large',
            self::TYPE_LATEST_PAGES => 'fas fa-newspaper',
            self::TYPE_PHOTO_GALLERY_PREVIEW => 'fas fa-images',
            self::TYPE_VIDEO_GALLERY_PREVIEW => 'fas fa-video',
            self::TYPE_CUSTOM_HTML => 'fas fa-code',
            self::TYPE_CONTACT_BAR => 'fas fa-address-card',
            self::TYPE_STATS_BAR => 'fas fa-chart-bar',
            self::TYPE_TESTIMONIALS => 'fas fa-quote-right',
            self::TYPE_CTA_BANNER => 'fas fa-bullhorn'
        ];
        
        return $icons[$this->section_type] ?? 'fas fa-cog';
    }

    public function getDescription()
    {
        $descriptions = [
            self::TYPE_HERO_BANNER => 'Large banner with heading, text, and buttons',
            self::TYPE_FEATURED_BLOCKS => 'Grid of featured content blocks with images',
            self::TYPE_LATEST_PAGES => 'Automatically displays latest pages/news',
            self::TYPE_PHOTO_GALLERY_PREVIEW => 'Preview of a photo gallery with thumbnails',
            self::TYPE_VIDEO_GALLERY_PREVIEW => 'Preview of a video gallery',
            self::TYPE_CUSTOM_HTML => 'Custom HTML/JavaScript content',
            self::TYPE_CONTACT_BAR => 'Contact information and social media links',
            self::TYPE_STATS_BAR => 'Statistics and achievements counter',
            self::TYPE_TESTIMONIALS => 'Customer/client testimonials carousel',
            self::TYPE_CTA_BANNER => 'Call-to-action banner with button'
        ];
        
        return $descriptions[$this->section_type] ?? 'Custom section';
    }

    public function getDefaultConfig()
    {
        $defaults = [
            self::TYPE_HERO_BANNER => [
                'headline' => 'Welcome to Our Website',
                'subheadline' => 'This is a subtitle for the hero section',
                'background_image' => null,
                'background_color' => '#667eea',
                'text_color' => '#ffffff',
                'button_primary_text' => 'Get Started',
                'button_primary_link' => '#',
                'button_secondary_text' => 'Learn More',
                'button_secondary_link' => '#',
                'overlay_opacity' => 0.5,
                'height' => '500px'
            ],
            
            self::TYPE_FEATURED_BLOCKS => [
                'blocks' => [
                    [
                        'title' => 'Feature 1',
                        'description' => 'Description for feature 1',
                        'icon' => 'fas fa-star',
                        'image' => null,
                        'link' => '#',
                        'button_text' => 'Read More'
                    ],
                    [
                        'title' => 'Feature 2',
                        'description' => 'Description for feature 2',
                        'icon' => 'fas fa-heart',
                        'image' => null,
                        'link' => '#',
                        'button_text' => 'Read More'
                    ],
                    [
                        'title' => 'Feature 3',
                        'description' => 'Description for feature 3',
                        'icon' => 'fas fa-cog',
                        'image' => null,
                        'link' => '#',
                        'button_text' => 'Read More'
                    ]
                ],
                'columns' => 3,
                'background_color' => '#ffffff',
                'text_color' => '#333333'
            ],
            
            self::TYPE_LATEST_PAGES => [
                'title' => 'Latest News',
                'count' => 3,
                'show_excerpt' => true,
                'show_date' => true,
                'show_author' => false,
                'show_featured_image' => true,
                'category' => null,
                'layout' => 'grid', // grid or list
                'columns' => 3
            ],
            
            self::TYPE_PHOTO_GALLERY_PREVIEW => [
                'title' => 'Photo Gallery',
                'gallery_id' => null,
                'show_title' => true,
                'show_description' => false,
                'thumbnail_count' => 6,
                'layout' => 'grid',
                'columns' => 3,
                'link_text' => 'View All Photos',
                'link_url' => '/galleries/photo'
            ],
            
            self::TYPE_VIDEO_GALLERY_PREVIEW => [
                'title' => 'Video Gallery',
                'gallery_id' => null,
                'show_title' => true,
                'show_description' => false,
                'video_count' => 3,
                'layout' => 'grid',
                'columns' => 3,
                'link_text' => 'View All Videos',
                'link_url' => '/galleries/video'
            ],
            
            self::TYPE_CUSTOM_HTML => [
                'html' => '<div class="custom-section">Your HTML content here</div>',
                'css' => '',
                'js' => ''
            ],
            
            self::TYPE_CONTACT_BAR => [
                'title' => 'Get in Touch',
                'address' => '123 Main Street, City, Country',
                'phone' => '+1 234 567 890',
                'email' => 'info@example.com',
                'show_social' => true,
                'social_facebook' => '#',
                'social_twitter' => '#',
                'social_instagram' => '#',
                'social_linkedin' => '#',
                'background_color' => '#f8f9fa',
                'text_color' => '#333333'
            ],
            
            self::TYPE_STATS_BAR => [
                'stats' => [
                    ['label' => 'Happy Clients', 'value' => '1000', 'icon' => 'fas fa-users'],
                    ['label' => 'Projects Completed', 'value' => '500', 'icon' => 'fas fa-check-circle'],
                    ['label' => 'Years Experience', 'value' => '10', 'icon' => 'fas fa-calendar'],
                    ['label' => 'Awards Won', 'value' => '25', 'icon' => 'fas fa-trophy']
                ],
                'background_color' => '#667eea',
                'text_color' => '#ffffff',
                'columns' => 4
            ],
            
            self::TYPE_TESTIMONIALS => [
                'title' => 'What Our Clients Say',
                'testimonials' => [
                    [
                        'name' => 'John Doe',
                        'position' => 'CEO, Company Inc',
                        'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                        'avatar' => null,
                        'rating' => 5
                    ],
                    [
                        'name' => 'Jane Smith',
                        'position' => 'Marketing Director',
                        'content' => 'Excepteur sint occaecat cupidatat non proident.',
                        'avatar' => null,
                        'rating' => 5
                    ]
                ],
                'autoplay' => true,
                'autoplay_speed' => 5000,
                'show_arrows' => true,
                'show_dots' => true
            ],
            
            self::TYPE_CTA_BANNER => [
                'title' => 'Ready to Get Started?',
                'description' => 'Join us today and experience the difference',
                'button_text' => 'Sign Up Now',
                'button_link' => '#',
                'background_image' => null,
                'background_color' => '#667eea',
                'text_color' => '#ffffff',
                'button_style' => 'primary', // primary, secondary, outline
                'alignment' => 'center' // left, center, right
            ]
        ];
        
        return $defaults[$this->section_type] ?? [];
    }

    public function render()
    {
        $config = $this->config;
        $viewFile = __DIR__ . '/../Views/home/sections/' . $this->section_type . '.php';
        
        if (file_exists($viewFile)) {
            ob_start();
            include $viewFile;
            return ob_get_clean();
        }
        
        return '<div class="alert alert-warning">Section template not found: ' . $this->section_type . '</div>';
    }

    public static function getAvailableTypes()
    {
        return [
            self::TYPE_HERO_BANNER => 'Hero Banner',
            self::TYPE_FEATURED_BLOCKS => 'Featured Blocks',
            self::TYPE_LATEST_PAGES => 'Latest Pages',
            self::TYPE_PHOTO_GALLERY_PREVIEW => 'Photo Gallery Preview',
            self::TYPE_VIDEO_GALLERY_PREVIEW => 'Video Gallery Preview',
            self::TYPE_CUSTOM_HTML => 'Custom HTML',
            self::TYPE_CONTACT_BAR => 'Contact Bar',
            self::TYPE_STATS_BAR => 'Stats Bar',
            self::TYPE_TESTIMONIALS => 'Testimonials',
            self::TYPE_CTA_BANNER => 'CTA Banner'
        ];
    }

    public static function getActiveSections()
    {
        return self::where(['is_visible' => true])
                  ->orderBy('sort_order')
                  ->get();
    }

    public static function moveUp($id)
    {
        $section = self::find($id);
        if (!$section) return false;
        
        $prev = self::where(['sort_order' => $section->sort_order - 1])->first();
        if ($prev) {
            $prev->sort_order = $section->sort_order;
            $prev->save();
            
            $section->sort_order = $section->sort_order - 1;
            $section->save();
            
            return true;
        }
        
        return false;
    }

    public static function moveDown($id)
    {
        $section = self::find($id);
        if (!$section) return false;
        
        $next = self::where(['sort_order' => $section->sort_order + 1])->first();
        if ($next) {
            $next->sort_order = $section->sort_order;
            $next->save();
            
            $section->sort_order = $section->sort_order + 1;
            $section->save();
            
            return true;
        }
        
        return false;
    }
}
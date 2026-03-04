<?php
// app/Models/VideoGallery.php

namespace App\Models;

class VideoGallery extends BaseModel
{
    protected static string $table = 'video_galleries';
    
    protected array $fillable = [
        'name', 'slug', 'description', 'is_public', 'sort_order', 'created_by'
    ];
    
    protected array $casts = [
        'is_public' => 'boolean',
        'sort_order' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function creator()
    {
        return User::find($this->created_by);
    }

    public function videos()
    {
        return Video::where(['gallery_id' => $this->id])
                   ->orderBy('sort_order')
                   ->get();
    }

    public function getVideos($limit = null)
    {
        $query = Video::where(['gallery_id' => $this->id, 'is_visible' => true])
                     ->orderBy('sort_order');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    public function getVideoCount()
    {
        $db = $this->db;
        $sql = "SELECT COUNT(*) FROM videos WHERE gallery_id = :gallery_id AND is_visible = true";
        $stmt = $db->prepare($sql);
        $stmt->execute([':gallery_id' => $this->id]);
        return $stmt->fetchColumn();
    }

    public function getCoverUrl()
    {
        // Get first video as cover
        $firstVideo = $this->getVideos(1);
        if (!empty($firstVideo)) {
            return $firstVideo[0]->getThumbnailUrl();
        }
        
        return '/admin/assets/img/default-video.jpg';
    }

    public function duplicate($newName = null)
    {
        $db = $this->db;
        $db->beginTransaction();
        
        try {
            // Create new gallery
            $newGallery = new self();
            $newGallery->name = $newName ?? $this->name . ' (Copy)';
            $newGallery->slug = $this->slug . '-copy';
            $newGallery->description = $this->description;
            $newGallery->is_public = false; // New galleries are private by default
            $newGallery->sort_order = $this->sort_order + 1;
            $newGallery->created_by = $this->created_by;
            $newGallery->save();
            
            // Get all videos from original gallery
            $videos = $this->videos();
            
            // Copy videos
            foreach ($videos as $video) {
                $newVideo = new Video();
                $newVideo->gallery_id = $newGallery->id;
                $newVideo->youtube_url = $video->youtube_url;
                $newVideo->youtube_id = $video->youtube_id;
                $newVideo->title = $video->title;
                $newVideo->description = $video->description;
                $newVideo->duration_iso = $video->duration_iso;
                $newVideo->thumbnail_url = $video->thumbnail_url;
                $newVideo->custom_thumbnail_id = $video->custom_thumbnail_id;
                $newVideo->channel_name = $video->channel_name;
                $newVideo->sort_order = $video->sort_order;
                $newVideo->is_visible = $video->is_visible;
                $newVideo->added_by = $video->added_by;
                $newVideo->save();
            }
            
            $db->commit();
            return $newGallery;
            
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    public static function generateSlug($name, $id = null)
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        
        $db = self::getConnection();
        $sql = "SELECT COUNT(*) FROM video_galleries WHERE slug = :slug";
        $params = [':slug' => $slug];
        
        if ($id) {
            $sql .= " AND id != :id";
            $params[':id'] = $id;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }
        
        return $slug;
    }

    public static function getPublicGalleries()
    {
        return self::where(['is_public' => true, 'deleted_at' => null])
                  ->orderBy('sort_order')
                  ->get();
    }

    public static function getStats()
    {
        $db = self::getConnection();
        
        // Total galleries
        $sql = "SELECT COUNT(*) FROM video_galleries WHERE deleted_at IS NULL";
        $stmt = $db->query($sql);
        $total = $stmt->fetchColumn();
        
        // Public vs private
        $sql = "SELECT 
                SUM(CASE WHEN is_public = true THEN 1 ELSE 0 END) as public,
                SUM(CASE WHEN is_public = false THEN 1 ELSE 0 END) as private
                FROM video_galleries WHERE deleted_at IS NULL";
        $stmt = $db->query($sql);
        $visibility = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Total videos
        $sql = "SELECT COUNT(*) FROM videos v
                JOIN video_galleries vg ON vg.id = v.gallery_id
                WHERE vg.deleted_at IS NULL";
        $stmt = $db->query($sql);
        $totalVideos = $stmt->fetchColumn();
        
        // Average videos per gallery
        $avgVideos = $total > 0 ? round($totalVideos / $total, 1) : 0;
        
        return [
            'total' => $total,
            'public' => (int)$visibility['public'],
            'private' => (int)$visibility['private'],
            'total_videos' => $totalVideos,
            'avg_videos' => $avgVideos
        ];
    }
}
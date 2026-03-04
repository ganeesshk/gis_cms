<?php
// app/Models/Video.php

namespace App\Models;

class Video extends BaseModel
{
    protected static string $table = 'videos';
    
    protected array $fillable = [
        'gallery_id', 'youtube_url', 'youtube_id', 'title', 'description',
        'duration_iso', 'thumbnail_url', 'custom_thumbnail_id', 'channel_name',
        'sort_order', 'is_visible', 'added_by'
    ];
    
    protected array $casts = [
        'sort_order' => 'int',
        'is_visible' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function gallery()
    {
        return VideoGallery::find($this->gallery_id);
    }

    public function adder()
    {
        return User::find($this->added_by);
    }

    public function customThumbnail()
    {
        return $this->custom_thumbnail_id ? Media::find($this->custom_thumbnail_id) : null;
    }

    public function getThumbnailUrl()
    {
        if ($this->custom_thumbnail_id) {
            $media = $this->customThumbnail();
            if ($media && $media->isImage()) {
                return $media->getThumbnail('medium');
            }
        }
        
        return $this->thumbnail_url ?: 'https://img.youtube.com/vi/' . $this->youtube_id . '/mqdefault.jpg';
    }

    public function getEmbedUrl()
    {
        return 'https://www.youtube-nocookie.com/embed/' . $this->youtube_id;
    }

    public function getWatchUrl()
    {
        return 'https://www.youtube.com/watch?v=' . $this->youtube_id;
    }

    public function getDuration()
    {
        if (!$this->duration_iso) {
            return null;
        }
        
        // Convert ISO 8601 duration to readable format
        $interval = new \DateInterval($this->duration_iso);
        
        if ($interval->h > 0) {
            return $interval->format('%h:%I:%S');
        } else {
            return $interval->format('%i:%S');
        }
    }

    public function fetchYouTubeData()
    {
        // Use oEmbed to fetch video data (no API key required)
        $url = 'https://www.youtube.com/oembed?url=' . urlencode($this->youtube_url) . '&format=json';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            if (isset($data['title'])) {
                $this->title = $data['title'];
            }
            
            if (isset($data['author_name'])) {
                $this->channel_name = $data['author_name'];
            }
            
            if (isset($data['thumbnail_url'])) {
                $this->thumbnail_url = $data['thumbnail_url'];
            }
            
            return true;
        }
        
        return false;
    }

    public function moveUp()
    {
        $db = $this->db;
        $sql = "SELECT id, sort_order FROM videos 
                WHERE gallery_id = :gallery_id 
                AND sort_order < :sort_order
                ORDER BY sort_order DESC
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':gallery_id' => $this->gallery_id,
            ':sort_order' => $this->sort_order
        ]);
        
        $prev = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($prev) {
            // Swap sort orders
            $this->sort_order = $prev['sort_order'];
            $this->save();
            
            $prevVideo = Video::find($prev['id']);
            $prevVideo->sort_order = $this->sort_order + 1;
            $prevVideo->save();
            
            return true;
        }
        
        return false;
    }

    public function moveDown()
    {
        $db = $this->db;
        $sql = "SELECT id, sort_order FROM videos 
                WHERE gallery_id = :gallery_id 
                AND sort_order > :sort_order
                ORDER BY sort_order ASC
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':gallery_id' => $this->gallery_id,
            ':sort_order' => $this->sort_order
        ]);
        
        $next = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($next) {
            // Swap sort orders
            $this->sort_order = $next['sort_order'];
            $this->save();
            
            $nextVideo = Video::find($next['id']);
            $nextVideo->sort_order = $this->sort_order - 1;
            $nextVideo->save();
            
            return true;
        }
        
        return false;
    }

    public static function getMaxSortOrder($galleryId)
    {
        $db = self::getConnection();
        $sql = "SELECT MAX(sort_order) FROM videos WHERE gallery_id = :gallery_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':gallery_id' => $galleryId]);
        $max = $stmt->fetchColumn();
        return $max ? (int)$max + 1 : 0;
    }

    public static function extractYoutubeId($url)
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
        
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    public static function validateYoutubeUrl($url)
    {
        return self::extractYoutubeId($url) !== null;
    }
}
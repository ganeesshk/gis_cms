<?php
// app/Models/Photo.php

namespace App\Models;

class Photo extends BaseModel
{
    protected static string $table = 'photos';
    
    protected array $fillable = [
        'gallery_id', 'media_id', 'title', 'caption', 
        'alt_text', 'sort_order', 'is_visible', 'uploaded_by'
    ];
    
    protected array $casts = [
        'sort_order' => 'int',
        'is_visible' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function gallery()
    {
        return PhotoGallery::find($this->gallery_id);
    }

    public function media()
    {
        return Media::find($this->media_id);
    }

    public function uploader()
    {
        return User::find($this->uploaded_by);
    }

    public function getThumbnailUrl($size = 'medium')
    {
        $media = $this->media();
        if ($media && $media->isImage()) {
            return $media->getThumbnail($size);
        }
        return '/admin/assets/img/default-image.jpg';
    }

    public function getFullSizeUrl()
    {
        $media = $this->media();
        return $media ? $media->public_url : '#';
    }

    public function getTitle()
    {
        return $this->title ?: ($this->media() ? $this->media()->original_name : 'Untitled');
    }

    public function moveUp()
    {
        $db = $this->db;
        $sql = "SELECT id, sort_order FROM photos 
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
            
            $prevPhoto = Photo::find($prev['id']);
            $prevPhoto->sort_order = $this->sort_order + 1;
            $prevPhoto->save();
            
            return true;
        }
        
        return false;
    }

    public function moveDown()
    {
        $db = $this->db;
        $sql = "SELECT id, sort_order FROM photos 
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
            
            $nextPhoto = Photo::find($next['id']);
            $nextPhoto->sort_order = $this->sort_order - 1;
            $nextPhoto->save();
            
            return true;
        }
        
        return false;
    }

    public static function getMaxSortOrder($galleryId)
    {
        $db = self::getConnection();
        $sql = "SELECT MAX(sort_order) FROM photos WHERE gallery_id = :gallery_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':gallery_id' => $galleryId]);
        $max = $stmt->fetchColumn();
        return $max ? (int)$max + 1 : 0;
    }
}
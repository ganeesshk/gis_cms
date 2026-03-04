<?php
// app/Models/PhotoGallery.php

namespace App\Models;

class PhotoGallery extends BaseModel
{
    protected static string $table = 'photo_galleries';
    
    protected array $fillable = [
        'name', 'slug', 'description', 'cover_media_id', 
        'is_public', 'sort_order', 'created_by'
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

    public function cover()
    {
        return $this->cover_media_id ? Media::find($this->cover_media_id) : null;
    }

    public function photos()
    {
        return Photo::where(['gallery_id' => $this->id])
                   ->orderBy('sort_order')
                   ->get();
    }

    public function getPhotos($limit = null)
    {
        $query = Photo::where(['gallery_id' => $this->id, 'is_visible' => true])
                     ->orderBy('sort_order');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    public function getPhotoCount()
    {
        $db = $this->db;
        $sql = "SELECT COUNT(*) FROM photos WHERE gallery_id = :gallery_id AND is_visible = true";
        $stmt = $db->prepare($sql);
        $stmt->execute([':gallery_id' => $this->id]);
        return $stmt->fetchColumn();
    }

    public function getCoverUrl($size = 'medium')
    {
        $cover = $this->cover();
        if ($cover && $cover->isImage()) {
            return $cover->getThumbnail($size);
        }
        
        // Get first photo as cover
        $firstPhoto = $this->getPhotos(1);
        if (!empty($firstPhoto)) {
            return $firstPhoto[0]->getThumbnailUrl($size);
        }
        
        return '/admin/assets/img/default-gallery.jpg';
    }

    public function updateCover()
    {
        // Set first photo as cover if no cover set
        if (!$this->cover_media_id) {
            $firstPhoto = $this->getPhotos(1);
            if (!empty($firstPhoto)) {
                $this->cover_media_id = $firstPhoto[0]->media_id;
                $this->save();
            }
        }
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
            
            // Get all photos from original gallery
            $photos = $this->photos();
            
            // Copy photos
            foreach ($photos as $photo) {
                $newPhoto = new Photo();
                $newPhoto->gallery_id = $newGallery->id;
                $newPhoto->media_id = $photo->media_id;
                $newPhoto->title = $photo->title;
                $newPhoto->caption = $photo->caption;
                $newPhoto->alt_text = $photo->alt_text;
                $newPhoto->sort_order = $photo->sort_order;
                $newPhoto->is_visible = $photo->is_visible;
                $newPhoto->uploaded_by = $photo->uploaded_by;
                $newPhoto->save();
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
        $sql = "SELECT COUNT(*) FROM photo_galleries WHERE slug = :slug";
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
        $sql = "SELECT COUNT(*) FROM photo_galleries WHERE deleted_at IS NULL";
        $stmt = $db->query($sql);
        $total = $stmt->fetchColumn();
        
        // Public vs private
        $sql = "SELECT 
                SUM(CASE WHEN is_public = true THEN 1 ELSE 0 END) as public,
                SUM(CASE WHEN is_public = false THEN 1 ELSE 0 END) as private
                FROM photo_galleries WHERE deleted_at IS NULL";
        $stmt = $db->query($sql);
        $visibility = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Total photos
        $sql = "SELECT COUNT(*) FROM photos p
                JOIN photo_galleries pg ON pg.id = p.gallery_id
                WHERE pg.deleted_at IS NULL";
        $stmt = $db->query($sql);
        $totalPhotos = $stmt->fetchColumn();
        
        // Average photos per gallery
        $avgPhotos = $total > 0 ? round($totalPhotos / $total, 1) : 0;
        
        return [
            'total' => $total,
            'public' => (int)$visibility['public'],
            'private' => (int)$visibility['private'],
            'total_photos' => $totalPhotos,
            'avg_photos' => $avgPhotos
        ];
    }
}
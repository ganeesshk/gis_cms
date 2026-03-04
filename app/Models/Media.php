<?php
// app/Models/Media.php

namespace App\Models;

class Media extends BaseModel
{
    protected static string $table = 'media';
    
    protected array $fillable = [
        'original_name', 'stored_name', 'storage_path', 'public_url',
        'mime_type', 'file_size', 'width', 'height', 'duration_sec',
        'alt_text', 'title', 'caption', 'folder', 'uploaded_by'
    ];
    
    protected array $casts = [
        'file_size' => 'int',
        'width' => 'int',
        'height' => 'int',
        'duration_sec' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    const VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/ogg'];
    const AUDIO_TYPES = ['audio/mpeg', 'audio/ogg', 'audio/wav'];
    const DOCUMENT_TYPES = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

    public function uploader()
    {
        return User::find($this->uploaded_by);
    }

    public function thumbnails()
    {
        return MediaThumbnail::where(['media_id' => $this->id])
                            ->orderBy('width')
                            ->get();
    }

    public function isImage()
    {
        return in_array($this->mime_type, self::IMAGE_TYPES);
    }

    public function isVideo()
    {
        return in_array($this->mime_type, self::VIDEO_TYPES);
    }

    public function isAudio()
    {
        return in_array($this->mime_type, self::AUDIO_TYPES);
    }

    public function isDocument()
    {
        return in_array($this->mime_type, self::DOCUMENT_TYPES);
    }

    public function getIcon()
    {
        if ($this->isImage()) {
            return 'fas fa-image';
        } elseif ($this->isVideo()) {
            return 'fas fa-video';
        } elseif ($this->isAudio()) {
            return 'fas fa-music';
        } elseif ($this->isDocument()) {
            return 'fas fa-file-pdf';
        } else {
            return 'fas fa-file';
        }
    }

    public function getFormattedSize()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDimensions()
    {
        if ($this->width && $this->height) {
            return $this->width . ' × ' . $this->height;
        }
        return null;
    }

    public function getThumbnail($size = 'medium')
    {
        $thumbnails = $this->thumbnails();
        
        foreach ($thumbnails as $thumb) {
            if ($thumb->size_label === $size) {
                return $thumb->public_url;
            }
        }
        
        // Return original if no thumbnail
        return $this->public_url;
    }

    public function getFolderPath()
    {
        return $this->folder ?: '/';
    }

    public function getFolderName()
    {
        $folder = $this->getFolderPath();
        return $folder === '/' ? 'Root' : basename($folder);
    }

    public function getParentFolder()
    {
        if ($this->folder === '/' || empty($this->folder)) {
            return null;
        }
        
        return dirname($this->folder);
    }

    public function getFullPath()
    {
        return $this->storage_path . '/' . $this->stored_name;
    }

    // In Media.php, replace the delete() method with:

	public function delete(): bool
	{
		// Delete physical file
		$fullPath = $this->getFullPath();
		if (file_exists($fullPath)) {
			unlink($fullPath);
		}
		
		// Delete thumbnails
		foreach ($this->thumbnails() as $thumbnail) {
			$thumbPath = $thumbnail->getFullPath();
			if (file_exists($thumbPath)) {
				unlink($thumbPath);
			}
			$thumbnail->delete();
		}
		
		return parent::delete();
	}

    public static function getFolders()
    {
        $db = self::getConnection();
        $sql = "SELECT DISTINCT folder FROM media WHERE deleted_at IS NULL ORDER BY folder";
        $stmt = $db->query($sql);
        
        $folders = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $folders[] = $row['folder'];
        }
        
        return $folders;
    }

    public static function getFolderTree()
    {
        $folders = self::getFolders();
        $tree = ['/' => []];
        
        foreach ($folders as $folder) {
            if ($folder === '/') continue;
            
            $parts = explode('/', trim($folder, '/'));
            $current = &$tree;
            
            foreach ($parts as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
        }
        
        return $tree;
    }

    public static function getUsageStats()
    {
        $db = self::getConnection();
        
        // Total files
        $sql = "SELECT COUNT(*) FROM media WHERE deleted_at IS NULL";
        $stmt = $db->query($sql);
        $totalFiles = $stmt->fetchColumn();
        
        // Total size
        $sql = "SELECT SUM(file_size) FROM media WHERE deleted_at IS NULL";
        $stmt = $db->query($sql);
        $totalSize = $stmt->fetchColumn() ?: 0;
        
        // By type
        $sql = "SELECT 
                SUM(CASE WHEN mime_type LIKE 'image/%' THEN 1 ELSE 0 END) as images,
                SUM(CASE WHEN mime_type LIKE 'video/%' THEN 1 ELSE 0 END) as videos,
                SUM(CASE WHEN mime_type LIKE 'audio/%' THEN 1 ELSE 0 END) as audio,
                SUM(CASE WHEN mime_type LIKE 'application/%' THEN 1 ELSE 0 END) as documents
                FROM media WHERE deleted_at IS NULL";
        $stmt = $db->query($sql);
        $typeStats = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // This month
        $sql = "SELECT COUNT(*) FROM media 
                WHERE deleted_at IS NULL 
                AND created_at >= DATE_TRUNC('month', CURRENT_DATE)";
        $stmt = $db->query($sql);
        $thisMonth = $stmt->fetchColumn();
        
        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_formatted' => self::formatBytes($totalSize),
            'images' => (int)$typeStats['images'],
            'videos' => (int)$typeStats['videos'],
            'audio' => (int)$typeStats['audio'],
            'documents' => (int)$typeStats['documents'],
            'this_month' => $thisMonth
        ];
    }

    private static function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
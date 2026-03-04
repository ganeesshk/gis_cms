<?php
// app/Models/MediaThumbnail.php

namespace App\Models;

class MediaThumbnail extends BaseModel
{
    protected static string $table = 'media_thumbnails';
    
    protected array $fillable = [
        'media_id', 'size_label', 'width', 'height',
        'stored_name', 'public_url', 'file_size'
    ];
    
    protected array $casts = [
        'width' => 'int',
        'height' => 'int',
        'file_size' => 'int',
        'created_at' => 'datetime'
    ];

    public function media()
    {
        return Media::find($this->media_id);
    }

    public function getFullPath()
    {
        $media = $this->media();
        if (!$media) return null;
        
        return dirname($media->getFullPath()) . '/' . $this->stored_name;
    }
}
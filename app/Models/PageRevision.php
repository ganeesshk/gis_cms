<?php
// app/Models/PageRevision.php

namespace App\Models;

class PageRevision extends BaseModel
{
    protected static string $table = 'page_revisions';
    
    protected array $fillable = [
        'page_id', 'revision_number', 'title', 'content',
        'meta_description', 'changed_by', 'change_note'
    ];
    
    protected array $casts = [
        'revision_number' => 'int',
        'created_at' => 'datetime'
    ];

    public function page()
    {
        return Page::find($this->page_id);
    }

    public function author()
    {
        return User::find($this->changed_by);
    }

    public static function createFromPage(Page $page, $userId, $note = null)
    {
        $revision = new self();
        $revision->page_id = $page->id;
        $revision->title = $page->title;
        $revision->content = $page->content;
        $revision->meta_description = $page->meta_description;
        $revision->changed_by = $userId;
        $revision->change_note = $note;
        $revision->save();
        
        return $revision;
    }

    public function getExcerpt($length = 200)
    {
        $text = strip_tags($this->content);
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . '...';
    }
}
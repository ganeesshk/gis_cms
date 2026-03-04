<?php
// app/Models/HomeDraft.php

namespace App\Models;

class HomeDraft extends BaseModel
{
    protected static string $table = 'home_page_draft';
    
    protected array $fillable = [
        'section_type', 'title', 'config', 'sort_order', 'is_visible', 'saved_by'
    ];
    
    protected array $casts = [
        'config' => 'json',
        'is_visible' => 'boolean',
        'sort_order' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function saver()
    {
        return User::find($this->saved_by);
    }

    public function getSectionTypeLabel()
    {
        $labels = HomeSection::getAvailableTypes();
        return $labels[$this->section_type] ?? ucfirst(str_replace('_', ' ', $this->section_type));
    }

    public function getIcon()
    {
        $section = new HomeSection();
        $section->section_type = $this->section_type;
        return $section->getIcon();
    }

    public static function publish()
    {
        $db = self::getConnection();
        $db->beginTransaction();
        
        try {
            // Clear live sections
            $db->exec("DELETE FROM home_page_sections");
            
            // Copy from draft to live
            $drafts = self::where([])->orderBy('sort_order')->get();
            
            foreach ($drafts as $draft) {
                $section = new HomeSection();
                $section->section_type = $draft->section_type;
                $section->title = $draft->title;
                $section->config = $draft->config;
                $section->sort_order = $draft->sort_order;
                $section->is_visible = $draft->is_visible;
                $section->updated_by = $draft->saved_by;
                $section->save();
            }
            
            $db->commit();
            return true;
            
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    public static function discard()
    {
        return self::getConnection()->exec("DELETE FROM home_page_draft");
    }

    public static function syncFromLive()
    {
        self::discard();
        
        $live = HomeSection::where([])->orderBy('sort_order')->get();
        
        foreach ($live as $section) {
            $draft = new self();
            $draft->section_type = $section->section_type;
            $draft->title = $section->title;
            $draft->config = $section->config;
            $draft->sort_order = $section->sort_order;
            $draft->is_visible = $section->is_visible;
            $draft->saved_by = $section->updated_by;
            $draft->save();
        }
    }
}
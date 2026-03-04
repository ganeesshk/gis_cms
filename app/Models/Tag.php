<?php
// app/Models/Tag.php

namespace App\Models;

class Tag extends BaseModel
{
    protected static string $table = 'tags';
    
    protected array $fillable = ['name', 'slug'];

    public function pages()
    {
        $db = $this->db;
        $sql = "SELECT p.* FROM pages p
                JOIN page_tags pt ON pt.page_id = p.id
                WHERE pt.tag_id = :tag_id AND p.deleted_at IS NULL
                ORDER BY p.title";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':tag_id' => $this->id]);
        
        $pages = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $page = new Page();
            $page->attributes = $row;
            $page->original = $row;
            $pages[] = $page;
        }
        
        return $pages;
    }

    public function getPageCount()
    {
        $db = $this->db;
        $sql = "SELECT COUNT(*) FROM page_tags WHERE tag_id = :tag_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':tag_id' => $this->id]);
        return $stmt->fetchColumn();
    }

    public static function findOrCreate($name)
    {
        $slug = self::generateSlug($name);
        
        $tags = self::where(['slug' => $slug])->get();
        
        if (!empty($tags)) {
            return $tags[0];
        }
        
        $tag = new self();
        $tag->name = $name;
        $tag->slug = $slug;
        $tag->save();
        
        return $tag;
    }

    public static function generateSlug($name)
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }

    public static function getAllWithCounts()
    {
        $db = self::getConnection();
        $sql = "SELECT t.*, COUNT(pt.page_id) as page_count
                FROM tags t
                LEFT JOIN page_tags pt ON pt.tag_id = t.id
                GROUP BY t.id
                ORDER BY t.name";
        
        $stmt = $db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
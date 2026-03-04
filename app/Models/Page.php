<?php
// app/Models/Page.php

namespace App\Models;

class Page extends BaseModel
{
    protected static string $table = 'pages';
    
    protected array $fillable = [
        'title', 'slug', 'content', 'excerpt', 
        'meta_title', 'meta_description', 'meta_keywords',
        'og_image_path', 'featured_image_path',
        'status', 'template', 'sort_order', 'is_in_sitemap',
        'author_id', 'published_by', 'scheduled_at'
    ];
    
    protected array $casts = [
        'is_in_sitemap' => 'boolean',
        'sort_order' => 'int',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'unpublished_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_UNPUBLISHED = 'unpublished';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_TRASHED = 'trashed';

    public function author()
    {
        return User::find($this->author_id);
    }

    public function publisher()
    {
        return $this->published_by ? User::find($this->published_by) : null;
    }

    public function revisions()
    {
        return PageRevision::where(['page_id' => $this->id])
                          ->orderBy('revision_number', 'DESC')
                          ->get();
    }

    public function tags()
    {
        $db = $this->db;
        $sql = "SELECT t.* FROM tags t
                JOIN page_tags pt ON pt.tag_id = t.id
                WHERE pt.page_id = :page_id
                ORDER BY t.name";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':page_id' => $this->id]);
        
        $tags = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tag = new Tag();
            $tag->attributes = $row;
            $tag->original = $row;
            $tags[] = $tag;
        }
        
        return $tags;
    }

    public function addTag($tagId)
    {
        $db = $this->db;
        $sql = "INSERT INTO page_tags (page_id, tag_id) 
                VALUES (:page_id, :tag_id)
                ON CONFLICT DO NOTHING";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':page_id' => $this->id,
            ':tag_id' => $tagId
        ]);
    }

    public function removeTag($tagId)
    {
        $db = $this->db;
        $sql = "DELETE FROM page_tags WHERE page_id = :page_id AND tag_id = :tag_id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':page_id' => $this->id,
            ':tag_id' => $tagId
        ]);
    }

    public function syncTags(array $tagIds)
    {
        $db = $this->db;
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Remove all existing tags
            $sql = "DELETE FROM page_tags WHERE page_id = :page_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':page_id' => $this->id]);
            
            // Add new tags
            if (!empty($tagIds)) {
                $sql = "INSERT INTO page_tags (page_id, tag_id) VALUES ";
                $values = [];
                $params = [];
                
                foreach ($tagIds as $index => $tagId) {
                    $values[] = "(:page_id_{$index}, :tag_id_{$index})";
                    $params[":page_id_{$index}"] = $this->id;
                    $params[":tag_id_{$index}"] = $tagId;
                }
                
                $sql .= implode(', ', $values);
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
            }
            
            $db->commit();
            return true;
            
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    public function publish($userId)
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->published_by = $userId;
        $this->save();
        
        // Create revision
        $this->createRevision($userId, 'Published page');
        
        return true;
    }

    public function unpublish($userId)
    {
        $this->status = self::STATUS_UNPUBLISHED;
        $this->save();
        
        // Create revision
        $this->createRevision($userId, 'Unpublished page');
        
        return true;
    }

    public function schedule($datetime, $userId)
    {
        $this->status = self::STATUS_SCHEDULED;
        $this->scheduled_at = $datetime;
        $this->save();
        
        // Create revision
        $this->createRevision($userId, 'Scheduled page for ' . $datetime);
        
        return true;
    }

    public function trash()
    {
        $this->status = self::STATUS_TRASHED;
        return $this->delete(); // Soft delete
    }

    public function restore()
    {
        $this->deleted_at = null;
        $this->status = self::STATUS_DRAFT;
        return $this->save();
    }

    public function createRevision($userId, $note = null)
    {
        return PageRevision::createFromPage($this, $userId, $note);
    }

    public function revertToRevision($revisionId, $userId)
    {
        $revision = PageRevision::find($revisionId);
        
        if (!$revision || $revision->page_id != $this->id) {
            return false;
        }
        
        // Save current state as revision before reverting
        $this->createRevision($userId, 'Auto-save before revert to revision #' . $revision->revision_number);
        
        // Revert to revision
        $this->title = $revision->title;
        $this->content = $revision->content;
        $this->meta_description = $revision->meta_description;
        $this->save();
        
        return true;
    }

    public function getPreviewUrl()
    {
        return '/page-preview.php?id=' . $this->id . '&token=' . md5($this->updated_at);
    }

    public function getPublicUrl()
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return null;
        }
        
        return '/' . $this->slug;
    }

    public function getStatusBadge()
    {
        $badges = [
            self::STATUS_DRAFT => 'badge bg-secondary',
            self::STATUS_PUBLISHED => 'badge bg-success',
            self::STATUS_UNPUBLISHED => 'badge bg-warning',
            self::STATUS_SCHEDULED => 'badge bg-info',
            self::STATUS_TRASHED => 'badge bg-danger'
        ];
        
        $labels = [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_UNPUBLISHED => 'Unpublished',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_TRASHED => 'Trashed'
        ];
        
        $class = $badges[$this->status] ?? 'badge bg-secondary';
        $label = $labels[$this->status] ?? ucfirst($this->status);
        
        return '<span class="' . $class . '">' . $label . '</span>';
    }

    public static function getStatusOptions()
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_UNPUBLISHED => 'Unpublished',
            self::STATUS_SCHEDULED => 'Scheduled'
        ];
    }

    public static function findBySlug($slug)
    {
        $pages = self::where(['slug' => $slug, 'deleted_at' => null])->get();
        return $pages[0] ?? null;
    }

    public static function generateSlug($title, $id = null)
    {
        // Convert to lowercase and replace spaces with hyphens
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        
        // Check if slug exists
        $db = self::getConnection();
        $sql = "SELECT COUNT(*) FROM pages WHERE slug = :slug";
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
	/**
	 * Generate a unique slug
	 */
	public static function generateUniqueSlug($title, $id = null)
	{
		// Convert to lowercase and replace spaces with hyphens
		$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
		
		// Remove leading/trailing hyphens
		$slug = trim($slug, '-');
		
		// If slug is empty, generate a random one
		if (empty($slug)) {
			$slug = 'page-' . uniqid();
		}
		
		$db = self::getConnection();
		$originalSlug = $slug;
		$counter = 1;
		
		while (true) {
			// Check if slug exists
			$sql = "SELECT id FROM pages WHERE slug = :slug AND deleted_at IS NULL";
			$params = [':slug' => $slug];
			
			if ($id) {
				$sql .= " AND id != :id";
				$params[':id'] = $id;
			}
			
			$stmt = $db->prepare($sql);
			$stmt->execute($params);
			
			if (!$stmt->fetch()) {
				break; // Slug is unique
			}
			
			// Slug exists, append counter
			$slug = $originalSlug . '-' . $counter;
			$counter++;
		}
		
		return $slug;
	}
}
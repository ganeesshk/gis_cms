<?php
// app/Models/MenuItem.php

namespace App\Models;

class MenuItem extends BaseModel
{
    protected static string $table = 'menu_items';
    
    protected array $fillable = [
        'menu_id', 'parent_id', 'label', 'link_type', 
        'page_id', 'url', 'anchor', 'target', 
        'css_class', 'icon_class', 'sort_order', 'is_active'
    ];
    
    protected array $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    const LINK_TYPE_PAGE = 'page';
    const LINK_TYPE_URL = 'url';
    const LINK_TYPE_ANCHOR = 'anchor';
    const LINK_TYPE_SEPARATOR = 'separator';

    public function menu()
    {
        return Menu::find($this->menu_id);
    }

    public function parent()
    {
        return $this->parent_id ? MenuItem::find($this->parent_id) : null;
    }

    public function children()
    {
        return MenuItem::where(['parent_id' => $this->id])
                      ->orderBy('sort_order')
                      ->get();
    }

    public function page()
    {
        return $this->page_id ? Page::find($this->page_id) : null;
    }

    public function getUrl()
    {
        switch ($this->link_type) {
            case self::LINK_TYPE_PAGE:
                $page = $this->page();
                return $page ? '/' . $page->slug : '#';
                
            case self::LINK_TYPE_URL:
                return $this->url;
                
            case self::LINK_TYPE_ANCHOR:
                return '#' . $this->anchor;
                
            case self::LINK_TYPE_SEPARATOR:
                return '#';
                
            default:
                return '#';
        }
    }

    public function getTarget()
    {
        return $this->target === '_blank' ? '_blank' : '_self';
    }

    public function hasChildren()
    {
        $db = $this->db;
        $sql = "SELECT COUNT(*) FROM menu_items WHERE parent_id = :parent_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':parent_id' => $this->id]);
        return $stmt->fetchColumn() > 0;
    }

    public function getDepth()
    {
        $depth = 0;
        $current = $this;
        
        while ($current->parent_id) {
            $depth++;
            $current = MenuItem::find($current->parent_id);
            if (!$current) break;
        }
        
        return $depth;
    }

    public function isSeparator()
    {
        return $this->link_type === self::LINK_TYPE_SEPARATOR;
    }

    public function getIcon()
    {
        return $this->icon_class ? '<i class="' . htmlspecialchars($this->icon_class) . '"></i>' : '';
    }

    public function getClasses()
    {
        $classes = [];
        
        if ($this->css_class) {
            $classes[] = $this->css_class;
        }
        
        if ($this->hasChildren()) {
            $classes[] = 'has-children';
        }
        
        if (!$this->is_active) {
            $classes[] = 'inactive';
        }
        
        return implode(' ', $classes);
    }

    public function getLinkTypeLabel()
    {
        $labels = [
            self::LINK_TYPE_PAGE => 'Page',
            self::LINK_TYPE_URL => 'Custom URL',
            self::LINK_TYPE_ANCHOR => 'Anchor',
            self::LINK_TYPE_SEPARATOR => 'Separator'
        ];
        
        return $labels[$this->link_type] ?? ucfirst($this->link_type);
    }

    public function moveUp()
    {
        // Get previous item at same level
        $db = $this->db;
        $sql = "SELECT id, sort_order FROM menu_items 
                WHERE menu_id = :menu_id 
                AND parent_id " . ($this->parent_id ? "= :parent_id" : "IS NULL") . "
                AND sort_order < :sort_order
                ORDER BY sort_order DESC
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $params = [
            ':menu_id' => $this->menu_id,
            ':sort_order' => $this->sort_order
        ];
        
        if ($this->parent_id) {
            $params[':parent_id'] = $this->parent_id;
        }
        
        $stmt->execute($params);
        $prev = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($prev) {
            // Swap sort orders
            $this->sort_order = $prev['sort_order'];
            $this->save();
            
            $prevItem = MenuItem::find($prev['id']);
            $prevItem->sort_order = $this->sort_order + 1;
            $prevItem->save();
            
            return true;
        }
        
        return false;
    }

    public function moveDown()
    {
        // Get next item at same level
        $db = $this->db;
        $sql = "SELECT id, sort_order FROM menu_items 
                WHERE menu_id = :menu_id 
                AND parent_id " . ($this->parent_id ? "= :parent_id" : "IS NULL") . "
                AND sort_order > :sort_order
                ORDER BY sort_order ASC
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $params = [
            ':menu_id' => $this->menu_id,
            ':sort_order' => $this->sort_order
        ];
        
        if ($this->parent_id) {
            $params[':parent_id'] = $this->parent_id;
        }
        
        $stmt->execute($params);
        $next = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($next) {
            // Swap sort orders
            $this->sort_order = $next['sort_order'];
            $this->save();
            
            $nextItem = MenuItem::find($next['id']);
            $nextItem->sort_order = $this->sort_order - 1;
            $nextItem->save();
            
            return true;
        }
        
        return false;
    }

    public static function getMaxSortOrder($menuId, $parentId = null)
    {
        $db = self::getConnection();
        
        if ($parentId) {
            $sql = "SELECT MAX(sort_order) FROM menu_items 
                    WHERE menu_id = :menu_id AND parent_id = :parent_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':menu_id' => $menuId, ':parent_id' => $parentId]);
        } else {
            $sql = "SELECT MAX(sort_order) FROM menu_items 
                    WHERE menu_id = :menu_id AND parent_id IS NULL";
            $stmt = $db->prepare($sql);
            $stmt->execute([':menu_id' => $menuId]);
        }
        
        $max = $stmt->fetchColumn();
        return $max ? (int)$max + 1 : 0;
    }

    public static function getLinkTypes()
    {
        return [
            self::LINK_TYPE_PAGE => 'Page',
            self::LINK_TYPE_URL => 'Custom URL',
            self::LINK_TYPE_ANCHOR => 'Anchor',
            self::LINK_TYPE_SEPARATOR => 'Separator'
        ];
    }
}
<?php
// app/Models/Menu.php

namespace App\Models;

class Menu extends BaseModel
{
    protected static string $table = 'menus';
    
    protected array $fillable = [
        'name', 'location', 'description', 'is_active', 'created_by'
    ];
    
    protected array $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function items()
    {
        return MenuItem::where(['menu_id' => $this->id])
                      ->orderBy('sort_order')
                      ->get();
    }

    public function getTree()
    {
        $allItems = $this->items();
        
        // Build tree structure
        $tree = [];
        $indexed = [];
        
        // First pass: index by ID
        foreach ($allItems as $item) {
            $item->children = [];
            $indexed[$item->id] = $item;
        }
        
        // Second pass: build tree
        foreach ($indexed as $id => $item) {
            if ($item->parent_id && isset($indexed[$item->parent_id])) {
                $indexed[$item->parent_id]->children[] = $item;
            } else {
                $tree[] = $item;
            }
        }
        
        return $tree;
    }

    public function creator()
    {
        return User::find($this->created_by);
    }

    public function isUsed()
    {
        $db = $this->db;
        $sql = "SELECT COUNT(*) FROM menu_items WHERE menu_id = :menu_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':menu_id' => $this->id]);
        return $stmt->fetchColumn() > 0;
    }

    public function getItemCount()
    {
        $db = $this->db;
        $sql = "SELECT COUNT(*) FROM menu_items WHERE menu_id = :menu_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':menu_id' => $this->id]);
        return $stmt->fetchColumn();
    }

    public function duplicate($newName = null)
    {
        $db = $this->db;
        $db->beginTransaction();
        
        try {
            // Create new menu
            $newMenu = new self();
            $newMenu->name = $newName ?? $this->name . ' (Copy)';
            $newMenu->location = $this->location . '-copy';
            $newMenu->description = $this->description;
            $newMenu->is_active = false; // New menus are inactive by default
            $newMenu->created_by = $this->created_by;
            $newMenu->save();
            
            // Get all items from original menu
            $items = $this->items();
            
            // Map old IDs to new IDs for parent references
            $idMap = [];
            
            // First pass: create all items without parent
            foreach ($items as $item) {
                $newItem = new MenuItem();
                $newItem->menu_id = $newMenu->id;
                $newItem->label = $item->label;
                $newItem->link_type = $item->link_type;
                $newItem->page_id = $item->page_id;
                $newItem->url = $item->url;
                $newItem->anchor = $item->anchor;
                $newItem->target = $item->target;
                $newItem->css_class = $item->css_class;
                $newItem->icon_class = $item->icon_class;
                $newItem->sort_order = $item->sort_order;
                $newItem->is_active = $item->is_active;
                $newItem->save();
                
                $idMap[$item->id] = $newItem->id;
            }
            
            // Second pass: update parent relationships
            foreach ($items as $item) {
                if ($item->parent_id && isset($idMap[$item->parent_id])) {
                    $childId = $idMap[$item->id];
                    $child = MenuItem::find($childId);
                    $child->parent_id = $idMap[$item->parent_id];
                    $child->save();
                }
            }
            
            $db->commit();
            return $newMenu;
            
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    public static function getLocations()
    {
        return [
            'primary' => 'Primary Navigation',
            'secondary' => 'Secondary Navigation',
            'footer' => 'Footer Menu',
            'sidebar' => 'Sidebar Menu',
            'topbar' => 'Top Bar',
            'mobile' => 'Mobile Menu'
        ];
    }

    public function getLocationLabel()
    {
        $locations = self::getLocations();
        return $locations[$this->location] ?? ucfirst($this->location);
    }

    public static function getActiveMenus()
    {
        return self::where(['is_active' => true])
                  ->orderBy('name')
                  ->get();
    }

    public static function getMenuByLocation($location)
    {
        $menus = self::where(['location' => $location, 'is_active' => true])->get();
        return $menus[0] ?? null;
    }
}
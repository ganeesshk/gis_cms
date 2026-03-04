<?php
// app/Controllers/MenuController.php

namespace App\Controllers;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\AuditLog;
use App\Services\AuthService;
use App\Security\CSRF;

class MenuController
{
    private $auth;
    private $csrf;
    private $config;

    public function __construct(AuthService $auth, CSRF $csrf, array $config)
    {
        $this->auth = $auth;
        $this->csrf = $csrf;
        $this->config = $config;
    }

    /**
     * List all menus
     */
    public function index()
    {
        $user = $this->auth->getCurrentUser();
        
        $menus = Menu::where([])
                     ->orderBy('name')
                     ->get();
        
        // Load item counts and creator info
        foreach ($menus as $menu) {
            $menu->item_count = $menu->getItemCount();
            $menu->creator = $menu->creator();
        }
        
        return [
            'view' => 'menus/index.php',
            'data' => [
                'menus' => $menus,
                'user' => $user,
                'locations' => Menu::getLocations()
            ]
        ];
    }

    /**
     * Show create menu form
     */
    public function create()
    {
        $user = $this->auth->getCurrentUser();
        $csrfToken = $this->csrf->generate('menu_create');
        
        return [
            'view' => 'menus/create.php',
            'data' => [
                'user' => $user,
                'csrfToken' => $csrfToken,
                'locations' => Menu::getLocations()
            ]
        ];
    }

    /**
     * Store new menu
     */
    public function store()
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '', 'menu_create')) {
            $_SESSION['error'] = 'Invalid security token.';
            header('Location: /admin/menus/create.php');
            exit;
        }
        
        // Validate input
        $errors = $this->validateMenuInput($_POST);
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_data'] = $_POST;
            header('Location: /admin/menus/create.php');
            exit;
        }
        
        // Check if location is already taken
        $existing = Menu::where(['location' => $_POST['location']])->get();
        if (!empty($existing)) {
            $_SESSION['error'] = 'This location is already in use. Please choose another.';
            $_SESSION['form_data'] = $_POST;
            header('Location: /admin/menus/create.php');
            exit;
        }
        
        // Create menu
        $menu = new Menu();
        $menu->name = $_POST['name'];
        $menu->location = $_POST['location'];
        $menu->description = $_POST['description'] ?? '';
        $menu->is_active = isset($_POST['is_active']);
        $menu->created_by = $user->id;
        $menu->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'menu.create',
            'entity_type' => 'menu',
            'entity_id' => $menu->id,
            'entity_label' => $menu->name,
            'new_values' => json_encode($menu->toArray()),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'Menu created successfully.';
        header('Location: /admin/menus/edit.php?id=' . $menu->id);
        exit;
    }

    /**
     * Show edit menu form
     */
    public function edit($id)
    {
        $user = $this->auth->getCurrentUser();
        
        $menu = Menu::find($id);
        
        if (!$menu) {
            header('HTTP/1.0 404 Not Found');
            return ['view' => 'errors/404.php', 'data' => []];
        }
        
        $menuTree = $menu->getTree();
        $pages = $this->getPagesForSelect();
        $csrfToken = $this->csrf->generate('menu_edit_' . $id);
        
        return [
            'view' => 'menus/edit.php',
            'data' => [
                'menu' => $menu,
                'menuTree' => $menuTree,
                'pages' => $pages,
                'user' => $user,
                'csrfToken' => $csrfToken,
                'locations' => Menu::getLocations(),
                'linkTypes' => MenuItem::getLinkTypes()
            ]
        ];
    }

    /**
     * Update menu
     */
    public function update($id)
    {
        $user = $this->auth->getCurrentUser();
        
        $menu = Menu::find($id);
        
        if (!$menu) {
            $_SESSION['error'] = 'Menu not found.';
            header('Location: /admin/menus/');
            exit;
        }
        
        // Verify CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '', 'menu_edit_' . $id)) {
            $_SESSION['error'] = 'Invalid security token.';
            header('Location: /admin/menus/edit.php?id=' . $id);
            exit;
        }
        
        // Validate input
        $errors = $this->validateMenuInput($_POST, $id);
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_data'] = $_POST;
            header('Location: /admin/menus/edit.php?id=' . $id);
            exit;
        }
        
        // Check if location is already taken (by another menu)
        $existing = Menu::where(['location' => $_POST['location']])->get();
        foreach ($existing as $existingMenu) {
            if ($existingMenu->id != $id) {
                $_SESSION['error'] = 'This location is already in use by another menu.';
                $_SESSION['form_data'] = $_POST;
                header('Location: /admin/menus/edit.php?id=' . $id);
                exit;
            }
        }
        
        // Store old values for audit
        $oldValues = $menu->toArray();
        
        // Update menu
        $menu->name = $_POST['name'];
        $menu->location = $_POST['location'];
        $menu->description = $_POST['description'] ?? '';
        $menu->is_active = isset($_POST['is_active']);
        $menu->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'menu.update',
            'entity_type' => 'menu',
            'entity_id' => $menu->id,
            'entity_label' => $menu->name,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($menu->toArray()),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'Menu updated successfully.';
        header('Location: /admin/menus/edit.php?id=' . $menu->id);
        exit;
    }

    /**
     * Delete menu
     */
    public function delete($id)
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'menu_delete_' . $id)) {
            $_SESSION['error'] = 'Invalid security token.';
            header('Location: /admin/menus/');
            exit;
        }
        
        $menu = Menu::find($id);
        
        if (!$menu) {
            $_SESSION['error'] = 'Menu not found.';
            header('Location: /admin/menus/');
            exit;
        }
        
        // Check if system menu (can't delete)
        if ($menu->location === 'primary' || $menu->location === 'footer') {
            $_SESSION['error'] = 'System menus cannot be deleted.';
            header('Location: /admin/menus/');
            exit;
        }
        
        // Store for audit
        $oldValues = $menu->toArray();
        $name = $menu->name;
        
        // Delete menu (cascade will delete items)
        if ($menu->delete()) {
            // Log activity
            AuditLog::log([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'action' => 'menu.delete',
                'entity_type' => 'menu',
                'entity_id' => $id,
                'entity_label' => $name,
                'old_values' => json_encode($oldValues),
                'result' => 'success'
            ]);
            
            $_SESSION['success'] = 'Menu deleted successfully.';
        } else {
            $_SESSION['error'] = 'Failed to delete menu.';
        }
        
        header('Location: /admin/menus/');
        exit;
    }

    /**
     * Duplicate menu
     */
    public function duplicate($id)
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'menu_duplicate_' . $id)) {
            $_SESSION['error'] = 'Invalid security token.';
            header('Location: /admin/menus/');
            exit;
        }
        
        $menu = Menu::find($id);
        
        if (!$menu) {
            $_SESSION['error'] = 'Menu not found.';
            header('Location: /admin/menus/');
            exit;
        }
        
        // Duplicate menu
        $newMenu = $menu->duplicate($_POST['name'] ?? null);
        
        if ($newMenu) {
            // Log activity
            AuditLog::log([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'action' => 'menu.duplicate',
                'entity_type' => 'menu',
                'entity_id' => $newMenu->id,
                'entity_label' => $newMenu->name,
                'result' => 'success'
            ]);
            
            $_SESSION['success'] = 'Menu duplicated successfully.';
            header('Location: /admin/menus/edit.php?id=' . $newMenu->id);
        } else {
            $_SESSION['error'] = 'Failed to duplicate menu.';
            header('Location: /admin/menus/');
        }
        
        exit;
    }

    /**
     * Add menu item
     */
    public function addItem($menuId)
    {
        $user = $this->auth->getCurrentUser();
        
        $menu = Menu::find($menuId);
        
        if (!$menu) {
            http_response_code(404);
            echo json_encode(['error' => 'Menu not found']);
            exit;
        }
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'menu_item_add')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        // Get input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate
        $errors = $this->validateMenuItemInput($input);
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            exit;
        }
        
        // Get max sort order
        $parentId = $input['parent_id'] ?? null;
        $sortOrder = MenuItem::getMaxSortOrder($menuId, $parentId);
        
        // Create item
        $item = new MenuItem();
        $item->menu_id = $menuId;
        $item->parent_id = $parentId;
        $item->label = $input['label'];
        $item->link_type = $input['link_type'];
        
        if ($input['link_type'] === 'page' && !empty($input['page_id'])) {
            $item->page_id = $input['page_id'];
        } elseif ($input['link_type'] === 'url' && !empty($input['url'])) {
            $item->url = $input['url'];
        } elseif ($input['link_type'] === 'anchor' && !empty($input['anchor'])) {
            $item->anchor = $input['anchor'];
        }
        
        $item->target = $input['target'] ?? '_self';
        $item->css_class = $input['css_class'] ?? '';
        $item->icon_class = $input['icon_class'] ?? '';
        $item->sort_order = $sortOrder;
        $item->is_active = isset($input['is_active']) ? (bool)$input['is_active'] : true;
        $item->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'menu.item_add',
            'entity_type' => 'menu_item',
            'entity_id' => $item->id,
            'entity_label' => $item->label,
            'new_values' => json_encode($item->toArray()),
            'result' => 'success'
        ]);
        
        // Return item data
        echo json_encode([
            'success' => true,
            'item' => $this->formatMenuItem($item)
        ]);
        exit;
    }

    /**
     * Update menu item
     */
    public function updateItem($itemId)
    {
        $user = $this->auth->getCurrentUser();
        
        $item = MenuItem::find($itemId);
        
        if (!$item) {
            http_response_code(404);
            echo json_encode(['error' => 'Menu item not found']);
            exit;
        }
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'menu_item_edit')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        // Get input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate
        $errors = $this->validateMenuItemInput($input, $itemId);
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            exit;
        }
        
        // Store old values for audit
        $oldValues = $item->toArray();
        
        // Update item
        $item->label = $input['label'];
        $item->link_type = $input['link_type'];
        
        if ($input['link_type'] === 'page' && !empty($input['page_id'])) {
            $item->page_id = $input['page_id'];
            $item->url = null;
            $item->anchor = null;
        } elseif ($input['link_type'] === 'url' && !empty($input['url'])) {
            $item->url = $input['url'];
            $item->page_id = null;
            $item->anchor = null;
        } elseif ($input['link_type'] === 'anchor' && !empty($input['anchor'])) {
            $item->anchor = $input['anchor'];
            $item->page_id = null;
            $item->url = null;
        } elseif ($input['link_type'] === 'separator') {
            $item->page_id = null;
            $item->url = null;
            $item->anchor = null;
        }
        
        $item->target = $input['target'] ?? '_self';
        $item->css_class = $input['css_class'] ?? '';
        $item->icon_class = $input['icon_class'] ?? '';
        $item->is_active = isset($input['is_active']) ? (bool)$input['is_active'] : true;
        $item->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'menu.item_update',
            'entity_type' => 'menu_item',
            'entity_id' => $item->id,
            'entity_label' => $item->label,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($item->toArray()),
            'result' => 'success'
        ]);
        
        echo json_encode([
            'success' => true,
            'item' => $this->formatMenuItem($item)
        ]);
        exit;
    }

    /**
     * Delete menu item
     */
    public function deleteItem($itemId)
    {
        $user = $this->auth->getCurrentUser();
        
        $item = MenuItem::find($itemId);
        
        if (!$item) {
            http_response_code(404);
            echo json_encode(['error' => 'Menu item not found']);
            exit;
        }
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'menu_item_delete')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        // Store for audit
        $oldValues = $item->toArray();
        $label = $item->label;
        $menuId = $item->menu_id;
        
        // Delete item
        if ($item->delete()) {
            // Log activity
            AuditLog::log([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'action' => 'menu.item_delete',
                'entity_type' => 'menu_item',
                'entity_id' => $itemId,
                'entity_label' => $label,
                'old_values' => json_encode($oldValues),
                'result' => 'success'
            ]);
            
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete menu item']);
        }
        
        exit;
    }

    /**
     * Reorder menu items (drag and drop)
     */
    public function reorderItems($menuId)
    {
        $user = $this->auth->getCurrentUser();
        
        $menu = Menu::find($menuId);
        
        if (!$menu) {
            http_response_code(404);
            echo json_encode(['error' => 'Menu not found']);
            exit;
        }
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'menu_reorder')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        // Get order data
        $input = json_decode(file_get_contents('php://input'), true);
        $order = $input['order'] ?? [];
        
        if (empty($order)) {
            http_response_code(400);
            echo json_encode(['error' => 'No order data provided']);
            exit;
        }
        
        $db = \App\Config\Database::getConnection();
        $db->beginTransaction();
        
        try {
            $this->updateItemOrder($order);
            $db->commit();
            
            // Log activity
            AuditLog::log([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'action' => 'menu.reorder',
                'entity_type' => 'menu',
                'entity_id' => $menu->id,
                'entity_label' => $menu->name,
                'result' => 'success'
            ]);
            
            echo json_encode(['success' => true]);
            
        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to reorder items: ' . $e->getMessage()]);
        }
        
        exit;
    }

    /**
     * Recursively update item order from nested array
     */
    private function updateItemOrder($items, $parentId = null)
    {
        $sortOrder = 0;
        
        foreach ($items as $itemData) {
            $item = MenuItem::find($itemData['id']);
            
            if ($item) {
                $item->parent_id = $parentId;
                $item->sort_order = $sortOrder;
                $item->save();
                
                if (!empty($itemData['children'])) {
                    $this->updateItemOrder($itemData['children'], $item->id);
                }
            }
            
            $sortOrder++;
        }
    }

    /**
     * Get pages for select dropdown
     */
    private function getPagesForSelect()
    {
        $pages = Page::where(['status' => 'published', 'deleted_at' => null])
                     ->orderBy('title')
                     ->get();
        
        $options = [];
        foreach ($pages as $page) {
            $options[] = [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug
            ];
        }
        
        return $options;
    }

    /**
     * Format menu item for JSON response
     */
    private function formatMenuItem(MenuItem $item)
    {
        return [
            'id' => $item->id,
            'label' => $item->label,
            'link_type' => $item->link_type,
            'link_type_label' => $item->getLinkTypeLabel(),
            'url' => $item->getUrl(),
            'target' => $item->target,
            'target_label' => $item->target === '_blank' ? 'New Tab' : 'Same Tab',
            'css_class' => $item->css_class,
            'icon_class' => $item->icon_class,
            'is_active' => $item->is_active,
            'has_children' => $item->hasChildren(),
            'page' => $item->page() ? [
                'id' => $item->page()->id,
                'title' => $item->page()->title
            ] : null
        ];
    }

    /**
     * Validate menu input
     */
    private function validateMenuInput($data, $id = null)
    {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Menu name is required.';
        } elseif (strlen($data['name']) > 100) {
            $errors[] = 'Menu name must not exceed 100 characters.';
        }
        
        if (empty($data['location'])) {
            $errors[] = 'Menu location is required.';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $data['location'])) {
            $errors[] = 'Location can only contain lowercase letters, numbers, and hyphens.';
        }
        
        return $errors;
    }

    /**
     * Validate menu item input
     */
    private function validateMenuItemInput($data, $id = null)
    {
        $errors = [];
        
        if (empty($data['label'])) {
            $errors[] = 'Item label is required.';
        } elseif (strlen($data['label']) > 200) {
            $errors[] = 'Item label must not exceed 200 characters.';
        }
        
        $validTypes = [MenuItem::LINK_TYPE_PAGE, MenuItem::LINK_TYPE_URL, 
                      MenuItem::LINK_TYPE_ANCHOR, MenuItem::LINK_TYPE_SEPARATOR];
        
        if (empty($data['link_type']) || !in_array($data['link_type'], $validTypes)) {
            $errors[] = 'Invalid link type.';
        }
        
        // Validate based on type
        if ($data['link_type'] === MenuItem::LINK_TYPE_PAGE && empty($data['page_id'])) {
            $errors[] = 'Please select a page.';
        }
        
        if ($data['link_type'] === MenuItem::LINK_TYPE_URL && empty($data['url'])) {
            $errors[] = 'URL is required.';
        } elseif ($data['link_type'] === MenuItem::LINK_TYPE_URL && !filter_var($data['url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Please enter a valid URL (include http:// or https://).';
        }
        
        if ($data['link_type'] === MenuItem::LINK_TYPE_ANCHOR && empty($data['anchor'])) {
            $errors[] = 'Anchor name is required.';
        } elseif ($data['link_type'] === MenuItem::LINK_TYPE_ANCHOR && !preg_match('/^[a-zA-Z0-9\-_]+$/', $data['anchor'])) {
            $errors[] = 'Anchor can only contain letters, numbers, hyphens, and underscores.';
        }
        
        if (!empty($data['target']) && !in_array($data['target'], ['_self', '_blank'])) {
            $errors[] = 'Invalid target value.';
        }
        
        return $errors;
    }
}
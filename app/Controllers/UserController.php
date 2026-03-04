<?php
// app/Controllers/UserController.php

namespace App\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\AuditLog;
use App\Services\AuthService;
use App\Security\CSRF;
use App\Security\Password;

class UserController
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
     * List all users
     */
    public function index()
    {
        $user = $this->auth->getCurrentUser();
        
        // Only super admin and admin can view users
        if (!$user->isSuperAdmin() && !$user->hasPermission('users.view')) {
            $_SESSION['error'] = 'You do not have permission to view users';
            header('Location: /admin/dashboard.php');
            exit;
        }
        
        // Build query with filters
        $role = $_GET['role'] ?? 'all';
        $status = $_GET['status'] ?? 'all';
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        $query = User::where(['deleted_at' => null]);
        
        if ($role !== 'all') {
            $query->where('role_id', '=', $role);
        }
        
        if ($status !== 'all') {
            $isActive = $status === 'active';
            $query->where('is_active', '=', $isActive);
        }
        
        if ($search) {
            $query->where('username', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('full_name', 'ILIKE', "%{$search}%");
        }
        
        $totalUsers = $query->count();
        $users = $query->orderBy('created_at', 'DESC')
                      ->limit($perPage)
                      ->offset($offset)
                      ->get();
        
        // Load roles for each user
        foreach ($users as $userObj) {
            $userObj->role = $userObj->role();
        }
        
        $totalPages = ceil($totalUsers / $perPage);
        
        // Get all roles for filter
        $roles = Role::where([])->orderBy('name')->get();
        
        // Get stats
        $stats = User::getStats();
        
        return [
            'view' => 'users/index.php',
            'data' => [
                'user' => $user,
                'users' => $users,
                'roles' => $roles,
                'stats' => $stats,
                'currentRole' => $role,
                'currentStatus' => $status,
                'search' => $search,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalUsers' => $totalUsers
            ]
        ];
    }

    /**
     * Show create user form
     */
    public function create()
    {
        $user = $this->auth->getCurrentUser();
        
        // Only super admin and admin can create users
        if (!$user->isSuperAdmin() && !$user->hasPermission('users.create')) {
            $_SESSION['error'] = 'You do not have permission to create users';
            header('Location: /admin/users/');
            exit;
        }
        
        // Get all roles
        $roles = Role::where([])->orderBy('name')->get();
        
        $csrfToken = $this->csrf->generate('user_create');
        
        return [
            'view' => 'users/create.php',
            'data' => [
                'user' => $user,
                'roles' => $roles,
                'csrfToken' => $csrfToken
            ]
        ];
    }

    /**
     * Store new user
     */
    public function store()
    {
        $currentUser = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '', 'user_create')) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/users/create.php');
            exit;
        }
        
        // Validate input
        $errors = User::validate($_POST);
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_data'] = $_POST;
            header('Location: /admin/users/create.php');
            exit;
        }
        
        // Create user
        $user = new User();
        $user->username = $_POST['username'];
        $user->email = $_POST['email'];
        $user->full_name = $_POST['full_name'] ?? '';
        $user->role_id = $_POST['role_id'];
        $user->is_active = isset($_POST['is_active']);
        $user->force_password_change = isset($_POST['force_password_change']);
        
        if (!empty($_POST['password'])) {
            $user->setPassword($_POST['password']);
        }
        
        $user->created_by = $currentUser->id;
        $user->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $currentUser->id,
            'username' => $currentUser->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'user.create',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'entity_label' => $user->username,
            'new_values' => json_encode($user->toArray()),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'User created successfully';
        header('Location: /admin/users/edit.php?id=' . $user->id);
        exit;
    }

    /**
     * Show edit user form
     */
    public function edit($id)
    {
        $currentUser = $this->auth->getCurrentUser();
        
        $user = User::find($id);
        
        if (!$user) {
            $_SESSION['error'] = 'User not found';
            header('Location: /admin/users/');
            exit;
        }
        
        // Check permission
        if (!$currentUser->isSuperAdmin() && 
            !$currentUser->hasPermission('users.edit') && 
            $currentUser->id != $id) {
            $_SESSION['error'] = 'You do not have permission to edit this user';
            header('Location: /admin/users/');
            exit;
        }
        
        // Get all roles
        $roles = Role::where([])->orderBy('name')->get();
        
        // Get user activity
        $activity = $user->getActivitySummary();
        $recentLogs = $user->auditLogs();
        
        $csrfToken = $this->csrf->generate('user_edit_' . $id);
        
        return [
            'view' => 'users/edit.php',
            'data' => [
                'currentUser' => $currentUser,
                'user' => $user,
                'roles' => $roles,
                'activity' => $activity,
                'recentLogs' => $recentLogs,
                'csrfToken' => $csrfToken
            ]
        ];
    }

    /**
     * Update user
     */
    public function update($id)
    {
        $currentUser = $this->auth->getCurrentUser();
        
        $user = User::find($id);
        
        if (!$user) {
            $_SESSION['error'] = 'User not found';
            header('Location: /admin/users/');
            exit;
        }
        
        // Verify CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '', 'user_edit_' . $id)) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/users/edit.php?id=' . $id);
            exit;
        }
        
        // Validate input (skip password if not changing)
        $data = $_POST;
        if (empty($data['password'])) {
            unset($data['password']);
        }
        
        $errors = User::validate($data, $id);
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_data'] = $_POST;
            header('Location: /admin/users/edit.php?id=' . $id);
            exit;
        }
        
        // Store old values for audit
        $oldValues = $user->toArray();
        
        // Update user
        $user->username = $_POST['username'];
        $user->email = $_POST['email'];
        $user->full_name = $_POST['full_name'] ?? '';
        $user->is_active = isset($_POST['is_active']);
        
        // Only super admin can change roles
        if ($currentUser->isSuperAdmin() || $currentUser->hasPermission('users.edit_roles')) {
            $user->role_id = $_POST['role_id'];
        }
        
        // Handle password change
        if (!empty($_POST['password'])) {
            $user->setPassword($_POST['password']);
            $user->force_password_change = isset($_POST['force_password_change']);
            $user->password_changed_at = date('Y-m-d H:i:s');
        }
        
        $user->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $currentUser->id,
            'username' => $currentUser->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'user.update',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'entity_label' => $user->username,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($user->toArray()),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'User updated successfully';
        header('Location: /admin/users/edit.php?id=' . $user->id);
        exit;
    }

    /**
     * Delete user (soft delete)
     */
    public function delete($id)
    {
        $currentUser = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'user_delete_' . $id)) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/users/');
            exit;
        }
        
        $user = User::find($id);
        
        if (!$user) {
            $_SESSION['error'] = 'User not found';
            header('Location: /admin/users/');
            exit;
        }
        
        // Prevent deleting yourself
        if ($user->id == $currentUser->id) {
            $_SESSION['error'] = 'You cannot delete your own account';
            header('Location: /admin/users/');
            exit;
        }
        
        // Store for audit
        $oldValues = $user->toArray();
        $username = $user->username;
        
        // Soft delete
        $user->deleted_at = date('Y-m-d H:i:s');
        $user->is_active = false;
        $user->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $currentUser->id,
            'username' => $currentUser->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'user.delete',
            'entity_type' => 'user',
            'entity_id' => $id,
            'entity_label' => $username,
            'old_values' => json_encode($oldValues),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'User deleted successfully';
        header('Location: /admin/users/');
        exit;
    }

    /**
     * Restore deleted user
     */
    public function restore($id)
    {
        $currentUser = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'user_restore_' . $id)) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/users/');
            exit;
        }
        
        // Find including deleted
        $db = \App\Config\Database::getConnection();
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            $_SESSION['error'] = 'User not found';
            header('Location: /admin/users/');
            exit;
        }
        
        $user = new User();
        $user->attributes = $data;
        $user->original = $data;
        
        // Restore
        $user->deleted_at = null;
        $user->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $currentUser->id,
            'username' => $currentUser->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'user.restore',
            'entity_type' => 'user',
            'entity_id' => $id,
            'entity_label' => $user->username,
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'User restored successfully';
        header('Location: /admin/users/edit.php?id=' . $user->id);
        exit;
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus($id)
    {
        $currentUser = $this->auth->getCurrentUser();
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'user_status')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        $user = User::find($id);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
        // Prevent deactivating yourself
        if ($user->id == $currentUser->id) {
            http_response_code(400);
            echo json_encode(['error' => 'You cannot change your own status']);
            exit;
        }
        
        // Toggle status
        $user->is_active = !$user->is_active;
        $user->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $currentUser->id,
            'username' => $currentUser->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'user.toggle_status',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'entity_label' => $user->username,
            'new_values' => json_encode(['is_active' => $user->is_active]),
            'result' => 'success'
        ]);
        
        echo json_encode([
            'success' => true,
            'is_active' => $user->is_active
        ]);
        exit;
    }

    /**
     * Unlock user account
     */
    public function unlock($id)
    {
        $currentUser = $this->auth->getCurrentUser();
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'user_unlock')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        $user = User::find($id);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
        // Unlock account
        $user->locked_until = null;
        $user->failed_login_attempts = 0;
        $user->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $currentUser->id,
            'username' => $currentUser->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'user.unlock',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'entity_label' => $user->username,
            'result' => 'success'
        ]);
        
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Get user details for AJAX
     */
    public function getDetails($id)
    {
        $currentUser = $this->auth->getCurrentUser();
        
        $user = User::find($id);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
        $user->role = $user->role();
        $activity = $user->getActivitySummary();
        
        echo json_encode([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'role' => $user->role ? $user->role->name : 'Unknown',
            'is_active' => $user->is_active,
            'is_locked' => $user->isLocked(),
            'avatar' => $user->getAvatarUrl(),
            'last_login' => $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : 'Never',
            'joined' => $user->created_at->format('Y-m-d'),
            'activity' => $activity
        ]);
        exit;
    }

    /**
     * Export users to CSV
     */
    public function export()
    {
        $currentUser = $this->auth->getCurrentUser();
        
        // Only super admin can export
        if (!$currentUser->isSuperAdmin()) {
            $_SESSION['error'] = 'You do not have permission to export users';
            header('Location: /admin/users/');
            exit;
        }
        
        // Get all users
        $users = User::where(['deleted_at' => null])->orderBy('username')->get();
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add headers
        fputcsv($output, ['ID', 'Username', 'Email', 'Full Name', 'Role', 'Active', 'Last Login', 'Joined']);
        
        // Add data
        foreach ($users as $user) {
            $role = $user->role();
            fputcsv($output, [
                $user->id,
                $user->username,
                $user->email,
                $user->full_name,
                $role ? $role->name : 'Unknown',
                $user->is_active ? 'Yes' : 'No',
                $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : 'Never',
                $user->created_at->format('Y-m-d')
            ]);
        }
        
        fclose($output);
        exit;
    }
}
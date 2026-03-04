<?php
// app/Controllers/AuditController.php

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuthService;
use App\Security\CSRF;

class AuditController
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
     * List audit logs
     */
    public function index()
    {
        $user = $this->auth->getCurrentUser();
        
        // Only super admin and users with audit permission can view
        if (!$user->isSuperAdmin() && !$user->hasPermission('audit.view')) {
            $_SESSION['error'] = 'You do not have permission to view audit logs';
            header('Location: /admin/dashboard.php');
            exit;
        }
        
        // Get filters from request
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'action' => $_GET['action'] ?? null,
            'entity_type' => $_GET['entity_type'] ?? null,
            'entity_id' => $_GET['entity_id'] ?? null,
            'result' => $_GET['result'] ?? null,
            'search' => $_GET['search'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];
        
        // Pagination
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        // Get logs with filters
        $query = AuditLog::where([]);
        
        // Apply filters
        if (!empty($filters['user_id'])) {
            $query->where('user_id', '=', $filters['user_id']);
        }
        
        if (!empty($filters['action'])) {
            $query->where('action', 'LIKE', $filters['action'] . '%');
        }
        
        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', '=', $filters['entity_type']);
        }
        
        if (!empty($filters['entity_id'])) {
            $query->where('entity_id', '=', $filters['entity_id']);
        }
        
        if (!empty($filters['result'])) {
            $query->where('result', '=', $filters['result']);
        }
        
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where('username', 'ILIKE', $search)
                  ->orWhere('entity_label', 'ILIKE', $search)
                  ->orWhere('action', 'ILIKE', $search)
                  ->orWhere('ip_address', 'LIKE', $search);
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from'] . ' 00:00:00');
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }
        
        // Get total count for pagination
        $totalLogs = $query->count();
        
        // Get logs for current page
        $logs = $query->orderBy('created_at', 'DESC')
                      ->limit($perPage)
                      ->offset($offset)
                      ->get();
        
        $totalPages = ceil($totalLogs / $perPage);
        
        // Get filter options
        $users = User::where(['deleted_at' => null])->orderBy('username')->get();
        $actions = $this->getDistinctActions();
        $entityTypes = $this->getDistinctEntityTypes();
        $results = ['success', 'failure', 'warning'];
        
        // Get summary stats
        $summary = AuditLog::getSummary();
        
        $csrfToken = $this->csrf->generate('audit');
        
        return [
            'view' => 'audit/index.php',
            'data' => [
                'user' => $user,
                'logs' => $logs,
                'filters' => $filters,
                'users' => $users,
                'actions' => $actions,
                'entityTypes' => $entityTypes,
                'results' => $results,
                'summary' => $summary,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalLogs' => $totalLogs,
                'csrfToken' => $csrfToken
            ]
        ];
    }

    /**
     * View single log entry
     */
    public function view($id)
    {
        $user = $this->auth->getCurrentUser();
        
        $log = AuditLog::find($id);
        
        if (!$log) {
            header('HTTP/1.0 404 Not Found');
            return ['view' => 'errors/404.php', 'data' => []];
        }
        
        // Parse old/new values if they exist
        $oldValues = $log->old_values ? json_decode($log->old_values, true) : null;
        $newValues = $log->new_values ? json_decode($log->new_values, true) : null;
        
        return [
            'view' => 'audit/view.php',
            'data' => [
                'user' => $user,
                'log' => $log,
                'oldValues' => $oldValues,
                'newValues' => $newValues
            ]
        ];
    }

    /**
     * Export audit logs
     */
    public function export()
    {
        $user = $this->auth->getCurrentUser();
        
        // Only super admin can export
        if (!$user->isSuperAdmin()) {
            $_SESSION['error'] = 'You do not have permission to export audit logs';
            header('Location: /admin/audit/');
            exit;
        }
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'audit_export')) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/audit/');
            exit;
        }
        
        // Get filters from request
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'action' => $_GET['action'] ?? null,
            'entity_type' => $_GET['entity_type'] ?? null,
            'entity_id' => $_GET['entity_id'] ?? null,
            'result' => $_GET['result'] ?? null,
            'search' => $_GET['search'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];
        
        $format = $_GET['format'] ?? 'csv';
        
        if ($format === 'csv') {
            $csv = AuditLog::export($filters, 'csv');
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d_His') . '.csv"');
            header('Content-Length: ' . strlen($csv));
            
            echo $csv;
        } else {
            $logs = AuditLog::export($filters, 'json');
            
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d_His') . '.json"');
            
            echo json_encode($logs, JSON_PRETTY_PRINT);
        }
        
        exit;
    }

    /**
     * Get distinct actions for filter
     */
    private function getDistinctActions()
    {
        $db = \App\Config\Database::getConnection();
        $sql = "SELECT DISTINCT action FROM audit_logs ORDER BY action";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get distinct entity types for filter
     */
    private function getDistinctEntityTypes()
    {
        $db = \App\Config\Database::getConnection();
        $sql = "SELECT DISTINCT entity_type FROM audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get statistics for dashboard
     */
    public function stats()
    {
        $user = $this->auth->getCurrentUser();
        
        $days = (int)($_GET['days'] ?? 30);
        
        $actionStats = AuditLog::getActionStats($days);
        $userStats = AuditLog::getUserStats(10);
        $actionTypeStats = AuditLog::getActionTypeStats(10);
        
        echo json_encode([
            'action_stats' => $actionStats,
            'user_stats' => $userStats,
            'action_type_stats' => $actionTypeStats
        ]);
        exit;
    }

    /**
     * Cleanup old logs (Super Admin only)
     */
    public function cleanup()
    {
        $user = $this->auth->getCurrentUser();
        
        // Only super admin can cleanup
        if (!$user->isSuperAdmin()) {
            $_SESSION['error'] = 'Permission denied';
            header('Location: /admin/audit/');
            exit;
        }
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'audit_cleanup')) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/audit/');
            exit;
        }
        
        $days = (int)($_GET['days'] ?? 90);
        $deleted = AuditLog::cleanup($days);
        
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'action' => 'audit.cleanup',
            'entity_type' => 'audit',
            'new_values' => json_encode(['deleted' => $deleted, 'older_than' => $days]),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = "Cleaned up {$deleted} log entries older than {$days} days";
        header('Location: /admin/audit/');
        exit;
    }
}
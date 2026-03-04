<?php
// app/Models/AuditLog.php

namespace App\Models;

class AuditLog extends BaseModel
{
    protected static string $table = 'audit_logs';
    
    protected array $fillable = [
        'user_id', 'username', 'ip_address', 'user_agent', 'session_id',
        'action', 'entity_type', 'entity_id', 'entity_label',
        'old_values', 'new_values', 'result', 'error_message'
    ];
    
    protected array $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'created_at' => 'datetime'
    ];

    // Remove updated_at and deleted_at from being considered
    protected array $guarded = ['id', 'created_at'];

    public function save(): bool
    {
        if ($this->exists) {
            throw new \Exception('Audit logs cannot be modified');
        }
        return parent::save();
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \Exception('Audit logs cannot be updated');
    }

    public function delete(): bool
    {
        throw new \Exception('Audit logs cannot be deleted');
    }

    public static function destroy($ids)
    {
        throw new \Exception('Audit logs cannot be deleted');
    }

    // Relationships
    public function user()
    {
        return $this->user_id ? User::find($this->user_id) : null;
    }

    // Logging methods
    public static function log(array $data)
    {
        // Ensure required fields
        $data = array_merge([
            'user_id' => null,
            'username' => null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'session_id' => session_id(),
            'result' => 'success',
            'error_message' => null,
            'old_values' => null,
            'new_values' => null
        ], $data);

        // Don't try to insert updated_at or deleted_at
        unset($data['updated_at'], $data['deleted_at']);

        $log = new self();
        $log->fill($data);
        $log->save();

        return $log;
    }

    public static function logLogin($userId, $username, $success, $errorMessage = null)
    {
        return self::log([
            'user_id' => $userId,
            'username' => $username,
            'action' => 'login',
            'entity_type' => 'user',
            'entity_id' => $userId,
            'entity_label' => $username,
            'result' => $success ? 'success' : 'failure',
            'error_message' => $errorMessage
        ]);
    }

    public static function logLogout($userId, $username)
    {
        return self::log([
            'user_id' => $userId,
            'username' => $username,
            'action' => 'logout',
            'entity_type' => 'user',
            'entity_id' => $userId,
            'entity_label' => $username,
            'result' => 'success'
        ]);
    }

    public static function logPageAction($action, $pageId, $pageTitle, $userId, $username, $oldValues = null, $newValues = null)
    {
        return self::log([
            'user_id' => $userId,
            'username' => $username,
            'action' => 'page.' . $action,
            'entity_type' => 'page',
            'entity_id' => $pageId,
            'entity_label' => $pageTitle,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'result' => 'success'
        ]);
    }

    public static function logMediaAction($action, $mediaId, $mediaName, $userId, $username, $oldValues = null, $newValues = null)
    {
        return self::log([
            'user_id' => $userId,
            'username' => $username,
            'action' => 'media.' . $action,
            'entity_type' => 'media',
            'entity_id' => $mediaId,
            'entity_label' => $mediaName,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'result' => 'success'
        ]);
    }

    public static function logUserAction($action, $targetUserId, $targetUsername, $userId, $username, $oldValues = null, $newValues = null)
    {
        return self::log([
            'user_id' => $userId,
            'username' => $username,
            'action' => 'user.' . $action,
            'entity_type' => 'user',
            'entity_id' => $targetUserId,
            'entity_label' => $targetUsername,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'result' => 'success'
        ]);
    }

    public static function logSettingAction($action, $settingKey, $settingLabel, $userId, $username, $oldValues = null, $newValues = null)
    {
        return self::log([
            'user_id' => $userId,
            'username' => $username,
            'action' => 'setting.' . $action,
            'entity_type' => 'setting',
            'entity_id' => null,
            'entity_label' => $settingKey,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'result' => 'success'
        ]);
    }

    // Query methods
    public static function getRecent($limit = 100)
    {
        return self::where([])
                  ->orderBy('created_at', 'DESC')
                  ->limit($limit)
                  ->get();
    }

    public static function getByUser($userId, $limit = 100)
    {
        return self::where(['user_id' => $userId])
                  ->orderBy('created_at', 'DESC')
                  ->limit($limit)
                  ->get();
    }

    public static function getByAction($action, $limit = 100)
    {
        return self::where(['action' => $action])
                  ->orderBy('created_at', 'DESC')
                  ->limit($limit)
                  ->get();
    }

    public static function getByEntity($entityType, $entityId, $limit = 100)
    {
        return self::where(['entity_type' => $entityType, 'entity_id' => $entityId])
                  ->orderBy('created_at', 'DESC')
                  ->limit($limit)
                  ->get();
    }

    public static function getByDateRange($startDate, $endDate, $limit = null)
    {
        $query = self::where('created_at', '>=', $startDate)
                     ->where('created_at', '<=', $endDate)
                     ->orderBy('created_at', 'DESC');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    public static function search($filters, $limit = 100)
    {
        $query = self::where([]);
        
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
                  ->orWhere('action', 'ILIKE', $search);
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        return $query->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->get();
    }

    public static function getActionStats($days = 30)
    {
        $db = self::getConnection();
        
        $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN result = 'success' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN result = 'failure' THEN 1 ELSE 0 END) as failure,
                SUM(CASE WHEN result = 'warning' THEN 1 ELSE 0 END) as warning
                FROM audit_logs
                WHERE created_at >= NOW() - INTERVAL ':days DAYS'
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':days' => $days]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getUserStats($limit = 10)
    {
        $db = self::getConnection();
        
        $sql = "SELECT 
                user_id,
                username,
                COUNT(*) as action_count,
                MAX(created_at) as last_action
                FROM audit_logs
                WHERE user_id IS NOT NULL
                GROUP BY user_id, username
                ORDER BY action_count DESC
                LIMIT :limit";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':limit' => $limit]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getActionTypeStats($limit = 10)
    {
        $db = self::getConnection();
        
        $sql = "SELECT 
                action,
                COUNT(*) as count,
                MAX(created_at) as last_occurrence
                FROM audit_logs
                GROUP BY action
                ORDER BY count DESC
                LIMIT :limit";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':limit' => $limit]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getEntityStats($entityType, $limit = 10)
    {
        $db = self::getConnection();
        
        $sql = "SELECT 
                entity_id,
                entity_label,
                COUNT(*) as action_count,
                MAX(created_at) as last_action
                FROM audit_logs
                WHERE entity_type = :entity_type
                GROUP BY entity_id, entity_label
                ORDER BY action_count DESC
                LIMIT :limit";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':entity_type' => $entityType,
            ':limit' => $limit
        ]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getSummary()
    {
        $db = self::getConnection();
        
        // Total logs
        $sql = "SELECT COUNT(*) FROM audit_logs";
        $stmt = $db->query($sql);
        $total = $stmt->fetchColumn();
        
        // Logs by result
        $sql = "SELECT 
                SUM(CASE WHEN result = 'success' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN result = 'failure' THEN 1 ELSE 0 END) as failure,
                SUM(CASE WHEN result = 'warning' THEN 1 ELSE 0 END) as warning
                FROM audit_logs";
        $stmt = $db->query($sql);
        $byResult = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Logs today
        $sql = "SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURRENT_DATE";
        $stmt = $db->query($sql);
        $today = $stmt->fetchColumn();
        
        // Unique users
        $sql = "SELECT COUNT(DISTINCT user_id) FROM audit_logs WHERE user_id IS NOT NULL";
        $stmt = $db->query($sql);
        $uniqueUsers = $stmt->fetchColumn();
        
        // First log
        $sql = "SELECT MIN(created_at) FROM audit_logs";
        $stmt = $db->query($sql);
        $firstLog = $stmt->fetchColumn();
        
        // Last log
        $sql = "SELECT MAX(created_at) FROM audit_logs";
        $stmt = $db->query($sql);
        $lastLog = $stmt->fetchColumn();
        
        return [
            'total' => $total,
            'success' => (int)$byResult['success'],
            'failure' => (int)$byResult['failure'],
            'warning' => (int)$byResult['warning'],
            'today' => (int)$today,
            'unique_users' => (int)$uniqueUsers,
            'first_log' => $firstLog,
            'last_log' => $lastLog,
            'period_days' => $firstLog ? ceil((time() - strtotime($firstLog)) / 86400) : 0
        ];
    }

    public static function cleanup($days = 90)
    {
        $db = self::getConnection();
        $sql = "DELETE FROM audit_logs WHERE created_at < NOW() - INTERVAL ':days DAYS'";
        $stmt = $db->prepare($sql);
        $stmt->execute([':days' => $days]);
        return $stmt->rowCount();
    }

    public static function export($filters = [], $format = 'csv')
    {
        $logs = self::search($filters, 10000); // Reasonable limit for export
        
        if ($format === 'csv') {
            $output = fopen('php://temp', 'r+');
            
            // Headers
            fputcsv($output, [
                'ID', 'Timestamp', 'User', 'IP Address', 'Action', 
                'Entity Type', 'Entity ID', 'Entity', 'Result', 'Error Message'
            ]);
            
            // Data
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->username ?: 'System',
                    $log->ip_address,
                    $log->action,
                    $log->entity_type,
                    $log->entity_id,
                    $log->entity_label,
                    $log->result,
                    $log->error_message
                ]);
            }
            
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);
            
            return $csv;
        }
        
        return $logs;
    }
}
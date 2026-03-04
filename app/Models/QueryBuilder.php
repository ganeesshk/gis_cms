<?php
// app/Models/QueryBuilder.php

namespace App\Models;

use App\Config\Database;
use PDO;

class QueryBuilder
{
    private string $table;
    private string $modelClass;
    private array $wheres = [];
    private array $orders = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $params = [];
    private bool $withDeleted = false;
    private ?PDO $db;

    public function __construct(string $table, string $modelClass, array $initialWhere = [])
    {
        $this->table = $table;
        $this->modelClass = $modelClass;
        $this->db = Database::getConnection();
        
        if (!empty($initialWhere)) {
            foreach ($initialWhere as $column => $value) {
                $this->where($column, '=', $value);
            }
        }
    }

    public function where(string $column, string $operator, $value): self
    {
        $paramName = 'where_' . count($this->params);
        $this->wheres[] = [
            'type' => 'and',
            'column' => $column,
            'operator' => $operator,
            'param' => $paramName
        ];
        $this->params[$paramName] = $value;
        return $this;
    }

		public function orWhere(string $column, string $operator, $value): self
		{
			$paramName = 'where_' . count($this->params);
			$this->wheres[] = [
				'type' => 'or',
				'column' => $column,
				'operator' => $operator,
				'param' => $paramName
			];
			$this->params[$paramName] = $value;
			return $this;
		}

    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            return $this;
        }
        
        $paramNames = [];
        foreach ($values as $i => $value) {
            $paramName = 'in_' . $column . '_' . $i;
            $paramNames[] = ':' . $paramName;
            $this->params[$paramName] = $value;
        }
        
        $this->wheres[] = [
            'type' => 'and',
            'column' => $column,
            'operator' => 'IN',
            'value_list' => implode(', ', $paramNames)
        ];
        return $this;
    }

    public function whereNotIn(string $column, array $values): self
    {
        if (empty($values)) {
            return $this;
        }
        
        $paramNames = [];
        foreach ($values as $i => $value) {
            $paramName = 'not_in_' . $column . '_' . $i;
            $paramNames[] = ':' . $paramName;
            $this->params[$paramName] = $value;
        }
        
        $this->wheres[] = [
            'type' => 'and',
            'column' => $column,
            'operator' => 'NOT IN',
            'value_list' => implode(', ', $paramNames)
        ];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = "$column $direction";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function withTrashed(): self
    {
        $this->withDeleted = true;
        return $this;
    }

    public function onlyTrashed(): self
    {
        $this->withDeleted = true;
        $this->where('deleted_at', 'IS NOT', null);
        return $this;
    }

    private function buildWhereClause(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $clauses = [];
        foreach ($this->wheres as $where) {
            if ($where['operator'] === 'IN' || $where['operator'] === 'NOT IN') {
                $clause = $where['column'] . ' ' . $where['operator'] . ' (' . $where['value_list'] . ')';
            } elseif ($where['operator'] === 'IS' || $where['operator'] === 'IS NOT') {
                $clause = $where['column'] . ' ' . $where['operator'] . ' NULL';
            } else {
                $clause = $where['column'] . ' ' . $where['operator'] . ' :' . $where['param'];
            }
            
            if (empty($clauses)) {
                $clauses[] = $clause;
            } else {
                $clauses[] = strtoupper($where['type']) . ' ' . $clause;
            }
        }

        return 'WHERE ' . implode(' ', $clauses);
    }

    private function getSQL(): string
    {
        $sql = "SELECT * FROM {$this->table}";
        
        // Add soft delete condition if not withDeleted and table has deleted_at
        if (!$this->withDeleted && $this->hasSoftDelete()) {
            // Check if we already have a deleted_at condition
            $hasDeletedCondition = false;
            foreach ($this->wheres as $where) {
                if ($where['column'] === 'deleted_at') {
                    $hasDeletedCondition = true;
                    break;
                }
            }
            
            if (!$hasDeletedCondition) {
                $this->where('deleted_at', 'IS', null);
            }
        }
        
        $whereClause = $this->buildWhereClause();
        if (!empty($whereClause)) {
            $sql .= ' ' . $whereClause;
        }
        
        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }
        
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }
        
        return $sql;
    }

    private function hasSoftDelete(): bool
    {
        // Check if the model class has deleted_at column by checking if the property exists in the table
        try {
            $model = new $this->modelClass();
            return property_exists($model, 'deleted_at') || isset($model->deleted_at);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function get(): array
	{
		$sql = $this->getSQL();
		$stmt = $this->db->prepare($sql);
		
		foreach ($this->params as $param => $value) {
			$type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
			$stmt->bindValue(':' . $param, $value, $type);
		}
		
		$stmt->execute();
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$models = [];
		foreach ($results as $data) {
			$model = new $this->modelClass();
			// Set attributes directly without using magic methods
			foreach ($data as $key => $value) {
				$model->attributes[$key] = $value;
			}
			// Store original data for change tracking
			$model->original = $data;
			$models[] = $model;
		}
		
		return $models;
	}

    public function first(): ?object
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        
        // Store current wheels count and params
        $originalWheres = $this->wheres;
        $originalParams = $this->params;
        $originalWithDeleted = $this->withDeleted;
        
        // Add soft delete condition if needed
        if (!$this->withDeleted && $this->hasSoftDelete()) {
            $hasDeletedCondition = false;
            foreach ($this->wheres as $where) {
                if ($where['column'] === 'deleted_at') {
                    $hasDeletedCondition = true;
                    break;
                }
            }
            
            if (!$hasDeletedCondition) {
                $this->where('deleted_at', 'IS', null);
            }
        }
        
        $whereClause = $this->buildWhereClause();
        if (!empty($whereClause)) {
            $sql .= ' ' . $whereClause;
        }
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($this->params as $param => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $param, $value, $type);
        }
        
        $stmt->execute();
        $count = (int) $stmt->fetchColumn();
        
        // Restore original state
        $this->wheres = $originalWheres;
        $this->params = $originalParams;
        $this->withDeleted = $originalWithDeleted;
        
        return $count;
    }
}
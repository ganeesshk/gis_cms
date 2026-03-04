<?php
// app/Models/BaseModel.php (updated version)

namespace App\Models;

use App\Config\Database;
use PDO;
use InvalidArgumentException;

abstract class BaseModel
{
    protected static string $table;
    protected static string $primaryKey = 'id';
    public array $attributes = [];
    protected array $original = [];
    protected array $fillable = [];
    protected array $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected array $casts = [];
    protected ?PDO $db;
    private static array $tableColumns = [];

    public function __construct(array $attributes = [])
    {
        $this->db = Database::getConnection();
        $this->fill($attributes);
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    protected function isFillable(string $key): bool
    {
        return !in_array($key, $this->guarded) && 
               (empty($this->fillable) || in_array($key, $this->fillable));
    }

    public function setAttribute(string $key, $value): void
    {
        if (isset($this->casts[$key])) {
            $value = $this->castValue($value, $this->casts[$key]);
        }
        
        if (!isset($this->original[$key])) {
            $this->original[$key] = $value;
        }
        
        $this->attributes[$key] = $value;
    }

    // In BaseModel.php, replace the magic methods with these:

	// In BaseModel.php, replace the magic methods section with this:

	/**
	 * Magic getter to access attributes
	 */
	public function __get($name)
	{
		error_log("BaseModel::__get called for: {$name}");
		
		// Special case for 'attributes' property itself
		if ($name === 'attributes') {
			return $this->attributes;
		}
		
		// Check if the property exists in attributes
		if (array_key_exists($name, $this->attributes)) {
			$value = $this->attributes[$name];
			error_log("  Found value for {$name}: " . print_r($value, true));
			return $value;
		}
		
		// Check if there's a getter method
		$method = 'get' . str_replace('_', '', ucwords($name, '_')) . 'Attribute';
		if (method_exists($this, $method)) {
			return $this->$method();
		}
		
		error_log("  No value found for {$name}");
		return null;
	}

	/**
	 * Magic setter to set attributes
	 */
	public function __set($name, $value)
	{
		$this->setAttribute($name, $value);
	}

	/**
	 * Magic isset to check if attribute exists
	 */
	public function __isset($name)
	{
		return isset($this->attributes[$name]);
	}

	/**
	 * Magic unset to remove attribute
	 */
	public function __unset($name)
	{
		unset($this->attributes[$name]);
	}
    protected function castValue($value, string $type)
    {
        if ($value === null) return null;
        
        return match($type) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : (array)$value,
            'object' => is_string($value) ? json_decode($value) : (object)$value,
            'datetime' => $value instanceof \DateTimeInterface ? $value : new \DateTimeImmutable($value),
            default => $value
        };
    }

    protected function prepareValueForDb($key, $value)
    {
        // Handle null values
        if ($value === null) {
            return null;
        }
        
        // Handle boolean casting for PostgreSQL
        if (isset($this->casts[$key]) && $this->casts[$key] === 'boolean') {
            return $value ? 't' : 'f';
        }
        
        // Handle datetime
        if (isset($this->casts[$key]) && $this->casts[$key] === 'datetime') {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }
            return $value;
        }
        
        // Handle json
        if (isset($this->casts[$key]) && in_array($this->casts[$key], ['json', 'array', 'object'])) {
            return json_encode($value);
        }
        
        return $value;
    }

    /**
     * Get table columns cache
     */
    private function getTableColumns(): array
    {
        if (!isset(self::$tableColumns[static::$table])) {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_name = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([static::$table]);
            self::$tableColumns[static::$table] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        return self::$tableColumns[static::$table];
    }

    /**
     * Check if column exists in table
     */
    private function columnExists(string $column): bool
    {
        return in_array($column, $this->getTableColumns());
    }

    public function save(): bool
    {
        if ($this->exists()) {
            return $this->update();
        }
        return $this->insert();
    }

    protected function exists(): bool
    {
        return isset($this->attributes[static::$primaryKey]);
    }

    // In BaseModel.php, update the insert() method around line 140:

protected function insert(): bool
{
    $data = array_diff_key($this->attributes, array_flip(['id', 'created_at', 'updated_at', 'deleted_at']));
    
    // Only add timestamps if columns exist in the table
    $columns = $this->getTableColumns();
    
    if (in_array('created_at', $columns) && !isset($data['created_at'])) {
        $data['created_at'] = date('Y-m-d H:i:s');
    }
    
    // Prepare values for database
    foreach ($data as $key => $value) {
        $data[$key] = $this->prepareValueForDb($key, $value);
    }

    $columns_list = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));

    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s) RETURNING %s',
        static::$table,
        $columns_list,
        $placeholders,
        static::$primaryKey
    );

    $stmt = $this->db->prepare($sql);
    
    foreach ($data as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }

    $result = $stmt->execute();
    
    if ($result) {
        $id = $stmt->fetchColumn();
        if ($id) {
            $this->attributes[static::$primaryKey] = $id;
            $this->original = $this->attributes;
            return true;
        }
    }

    return false;
}

    protected function update(): bool
    {
        $changes = [];
        foreach ($this->attributes as $key => $value) {
            if ($key !== static::$primaryKey && 
                (!isset($this->original[$key]) || $this->prepareValueForDb($key, $this->original[$key]) !== $this->prepareValueForDb($key, $value))) {
                $changes[$key] = $value;
            }
        }

        if (empty($changes)) {
            return true;
        }

        // Only add updated_at if column exists
        if ($this->columnExists('updated_at')) {
            $changes['updated_at'] = date('Y-m-d H:i:s');
        }

        // Prepare values for database
        foreach ($changes as $key => $value) {
            $changes[$key] = $this->prepareValueForDb($key, $value);
        }

        $setClause = implode(', ', array_map(fn($col) => "$col = :$col", array_keys($changes)));
        
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :%s',
            static::$table,
            $setClause,
            static::$primaryKey,
            static::$primaryKey
        );

        $stmt = $this->db->prepare($sql);
        
        foreach ($changes as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':' . static::$primaryKey, $this->attributes[static::$primaryKey]);

        $result = $stmt->execute();
        
        if ($result) {
            $this->original = $this->attributes;
        }

        return $result;
    }

    public function delete(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        // Check if table has deleted_at column for soft delete
        if ($this->columnExists('deleted_at')) {
            $sql = sprintf(
                'UPDATE %s SET deleted_at = :deleted_at WHERE %s = :%s',
                static::$table,
                static::$primaryKey,
                static::$primaryKey
            );
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':deleted_at', date('Y-m-d H:i:s'));
            $stmt->bindValue(':' . static::$primaryKey, $this->attributes[static::$primaryKey]);
            return $stmt->execute();
        }

        // Hard delete
        $sql = sprintf(
            'DELETE FROM %s WHERE %s = :%s',
            static::$table,
            static::$primaryKey,
            static::$primaryKey
        );
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':' . static::$primaryKey, $this->attributes[static::$primaryKey]);
        return $stmt->execute();
    }

    public function forceDelete(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $sql = sprintf(
            'DELETE FROM %s WHERE %s = :%s',
            static::$table,
            static::$primaryKey,
            static::$primaryKey
        );
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':' . static::$primaryKey, $this->attributes[static::$primaryKey]);
        return $stmt->execute();
    }

    public static function find($id): ?static
    {
        $db = Database::getConnection();
        $sql = sprintf('SELECT * FROM %s WHERE %s = :id', static::$table, static::$primaryKey);
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }

        $model = new static();
        $model->attributes = $data;
        $model->original = $data;
        
        return $model;
    }

    public static function where(array $conditions): QueryBuilder
    {
        return new QueryBuilder(static::$table, static::class, $conditions);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function toJson(): string
    {
        return json_encode($this->attributes);
    }

    public static function getConnection(): PDO
    {
        return Database::getConnection();
    }
}
<?php
namespace App\Models;

use App\Core\Database;

/**
 * Base Model Class
 */
abstract class Model
{
    protected Database $db;
    protected string $table    = '';
    protected string $primaryKey = 'id';
    protected bool $softDelete = true;
    protected array $fillable  = [];
    protected array $hidden    = ['password', 'remember_token', 'reset_token'];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $del = $this->softDelete ? " AND deleted_at IS NULL" : '';
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? {$del}",
            [$id]
        ) ?: null;
    }

    public function findBy(string $column, $value): ?array
    {
        $del = $this->softDelete ? " AND deleted_at IS NULL" : '';
        $col = $this->db->sanitizeIdentifier($column);
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE {$col} = ? {$del}",
            [$value]
        ) ?: null;
    }

    public function all(string $orderBy = '', int $limit = 0): array
    {
        $del = $this->softDelete ? " WHERE deleted_at IS NULL" : '';
        $ord = $orderBy ? " ORDER BY " . $this->db->sanitizeIdentifier($orderBy) : '';
        $lim = $limit > 0 ? " LIMIT {$limit}" : '';
        return $this->db->fetchAll("SELECT * FROM {$this->table}{$del}{$ord}{$lim}");
    }

    public function paginate(string $where = '', array $params = [], int $page = 1, int $perPage = 25): array
    {
        $del = $this->softDelete ? ($where ? " AND deleted_at IS NULL" : " WHERE deleted_at IS NULL") : '';
        $baseQuery = "FROM {$this->table} " . ($where ? "WHERE $where" : '') . $del;
        return $this->db->paginate("SELECT * $baseQuery", "SELECT COUNT(*) $baseQuery", $params, $page, $perPage);
    }

    public function create(array $data): int
    {
        $data = $this->filterFillable($data);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert($this->table, $data);
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->filterFillable($data);
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update($this->table, $data, "{$this->primaryKey} = ?", [$id]);
    }

    public function delete(int $id): bool
    {
        if ($this->softDelete) {
            return $this->db->softDelete($this->table, $id, $this->primaryKey);
        }
        return $this->db->update($this->table, [], "{$this->primaryKey} = ?", [$id]);
    }

    public function count(string $where = '', array $params = []): int
    {
        $del = $this->softDelete ? ($where ? " AND deleted_at IS NULL" : " WHERE deleted_at IS NULL") : '';
        $q = "SELECT COUNT(*) FROM {$this->table} " . ($where ? "WHERE $where" : '') . $del;
        return (int)$this->db->fetchColumn($q, $params);
    }

    public function exists(string $column, $value, int $exceptId = 0): bool
    {
        $col = $this->db->sanitizeIdentifier($column);
        $del = $this->softDelete ? " AND deleted_at IS NULL" : '';
        $ex  = $exceptId > 0 ? " AND {$this->primaryKey} != ?" : '';
        $params = $exceptId > 0 ? [$value, $exceptId] : [$value];
        $r = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE {$col} = ?{$del}{$ex}",
            $params
        );
        return (int)$r > 0;
    }

    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) return $data;
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function hideFields(array $row): array
    {
        foreach ($this->hidden as $field) {
            unset($row[$field]);
        }
        return $row;
    }
}

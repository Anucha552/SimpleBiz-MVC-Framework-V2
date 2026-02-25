<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use App\Core\RawExpression;
use App\Core\Logger;

class QueryBuilder
{
    protected Database $db;
    protected string $table;
    protected Logger $logger;

    protected array|string $columns = ['*'];
    protected array $wheres = [];
    protected array $joins = [];
    protected array $groups = [];
    protected array $havings = [];
    protected array $orders = [];
    protected ?int $limit = null;
    protected ?int $offset = null;

    /**
     * Named bindings without colons: ['p1' => value]
     * These are passed to PDO::execute()
     */
    protected array $bindings = [];
    protected int $paramCounter = 1;

    public function __construct(Database $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;

        // สร้าง instance ของ Logger เพื่อใช้สำหรับบันทึกข้อมูลการดีบักหรือข้อผิดพลาดที่เกิดขึ้นใน QueryBuilder
        $this->logger = new Logger();
    }

    public function select(array|string $columns = '*'): self
    {
        if ($columns === '*') {
            $this->columns = ['*'];
        } elseif (is_array($columns)) {
            $this->columns = $columns;
        } else {
            $this->columns = array_map('trim', explode(',', (string)$columns));
        }
        return $this;
    }

    /**
     * Support: where(column, operator, value) and where(callable $callback)
     * callable receives a QueryBuilder instance scoped to same table.
     */
    public function where(callable|string $column, ?string $operator = null, mixed $value = null): self
    {
        // Nested where via callback
        if (is_callable($column)) {
            $nested = new static($this->db, $this->table);
            // continue parameter numbering to avoid collisions
            $nested->paramCounter = $this->paramCounter;
            $column($nested);
            $nestedWhere = $nested->buildWhereClause();
            if ($nestedWhere === '') {
                return $this;
            }
            // merge bindings and advance counter
            foreach ($nested->bindings as $k => $v) {
                $this->bindings[$k] = $v;
            }
            $this->paramCounter = $nested->paramCounter;
            $this->wheres[] = ['boolean' => 'AND', 'sql' => '(' . $nestedWhere . ')'];
            return $this;
        }

        // Standard where(column, operator, value)
        $col = $column;
        $op = $operator ?? '=';

        // NULL handling
        if ($value === null) {
            if ($op === '=' ) {
                $sql = $this->formatIdentifierOrRaw($col) . ' IS NULL';
                $this->wheres[] = ['boolean' => 'AND', 'sql' => $sql];
                return $this;
            }
            if ($op === '!=' || $op === '<>') {
                $sql = $this->formatIdentifierOrRaw($col) . ' IS NOT NULL';
                $this->wheres[] = ['boolean' => 'AND', 'sql' => $sql];
                return $this;
            }
        }

        // normal binding
        $placeholder = $this->makePlaceholder();
        $left = $this->formatIdentifierOrRaw($col);
        $this->wheres[] = ['boolean' => 'AND', 'sql' => sprintf('%s %s :%s', $left, $op, $placeholder)];
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    public function orWhere(string $column, string $operator, mixed $value): self
    {
        // NULL handling for OR
        if ($value === null) {
            if ($operator === '=') {
                $sql = $this->formatIdentifierOrRaw($column) . ' IS NULL';
                $this->wheres[] = ['boolean' => 'OR', 'sql' => $sql];
                return $this;
            }
            if ($operator === '!=' || $operator === '<>') {
                $sql = $this->formatIdentifierOrRaw($column) . ' IS NOT NULL';
                $this->wheres[] = ['boolean' => 'OR', 'sql' => $sql];
                return $this;
            }
        }

        $placeholder = $this->makePlaceholder();
        $left = $this->formatIdentifierOrRaw($column);
        $this->wheres[] = ['boolean' => 'OR', 'sql' => sprintf('%s %s :%s', $left, $operator, $placeholder)];
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            // no values => always-false condition
            $this->wheres[] = ['boolean' => 'AND', 'sql' => '0 = 1'];
            return $this;
        }
        $placeholders = [];
        foreach ($values as $v) {
            $ph = $this->makePlaceholder();
            $placeholders[] = ":$ph";
            $this->bindings[$ph] = $v;
        }
        $this->wheres[] = ['boolean' => 'AND', 'sql' => sprintf("%s IN (%s)", $this->formatIdentifierOrRaw($column), implode(', ', $placeholders))];
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = ['boolean' => 'AND', 'sql' => $this->formatIdentifierOrRaw($column) . " IS NULL"];
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = ['boolean' => 'AND', 'sql' => $this->formatIdentifierOrRaw($column) . " IS NOT NULL"];
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $tbl = $this->escapeIdentifier($table);
        $firstSql = $this->formatIdentifierOrRaw($first);
        $secondSql = $this->formatIdentifierOrRaw($second);
        $this->joins[] = ['type' => 'INNER', 'sql' => "JOIN $tbl ON $firstSql $operator $secondSql"];
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $tbl = $this->escapeIdentifier($table);
        $firstSql = $this->formatIdentifierOrRaw($first);
        $secondSql = $this->formatIdentifierOrRaw($second);
        $this->joins[] = ['type' => 'LEFT', 'sql' => "LEFT JOIN $tbl ON $firstSql $operator $secondSql"];
        return $this;
    }

    public function groupBy(array|string $columns): self
    {
        $this->groups = is_array($columns) ? $columns : explode(',', (string)$columns);
        return $this;
    }

    public function having(string $column, string $operator, mixed $value): self
    {
        $placeholder = $this->makePlaceholder();
        $left = $this->formatIdentifierOrRaw($column);
        $this->havings[] = ['boolean' => 'AND', 'sql' => sprintf('%s %s :%s', $left, $operator, $placeholder)];
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = $this->formatIdentifierOrRaw($column) . " $direction";
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

    public function get(): array
    {
        $sql = $this->toSql();
        $result = $this->db->fetchAll($sql, $this->bindings) ?: [];
        $this->clear();
        return $result;
    }

    public function first(): ?array
    {
        $this->limit(1);
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function find(int $id): ?array
    {
        $this->where('id', '=', $id);
        return $this->first();
    }

    public function insert(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $cols = array_keys($data);
        $escapedCols = array_map([$this, 'escapeIdentifier'], $cols);
        $placeholders = [];
        foreach ($data as $val) {
            if ($val instanceof RawExpression) {
                $placeholders[] = $val->getValue();
                continue;
            }
            $ph = $this->makePlaceholder();
            $placeholders[] = ":$ph";
            $this->bindings[$ph] = $val;
        }
        $sql = sprintf("INSERT INTO %s (%s) VALUES (%s)", $this->escapeIdentifier($this->table), implode(', ', $escapedCols), implode(', ', $placeholders));

        try {
            $affected = $this->db->execute($sql, $this->bindings);
            $ok = $affected !== false && $affected >= 0;
        } catch (\Throwable $e) {
            $ok = false;
        }
        $this->clear();
        return (bool)$ok;
    }

    public function insertGetId(array $data): int
    {
        $ok = $this->insert($data); // เรียกใช้เมธอด insert() เพื่อแทรกข้อมูลและตรวจสอบความสำเร็จ
        $id = 0;
        if ($ok) {
            $id = (int)$this->db->lastInsertId();
        }
        // insert() already calls clear(); ensure cleared
        $this->clear();
        return $id;
    }

    public function update(array $data): int
    {
        if (empty($data)) {
            return 0;
        }
        $sets = [];
        foreach ($data as $col => $val) {
            $escaped = $this->escapeIdentifier($col);
            if ($val instanceof RawExpression) {
                $sets[] = sprintf('%s = %s', $escaped, $val->getValue());
                continue;
            }
            $ph = $this->makePlaceholder();
            $sets[] = sprintf('%s = :%s', $escaped, $ph);
            $this->bindings[$ph] = $val;
        }
        $sql = sprintf("UPDATE %s SET %s", $this->escapeIdentifier($this->table), implode(', ', $sets));
        $whereSql = $this->buildWhereClause();
        if ($whereSql === '') {
            $this->clear();
            throw new \RuntimeException('Unsafe query: UPDATE without WHERE clause.');
        }
        if ($whereSql !== '') {
            $sql .= " WHERE " . $whereSql;
        }
        $count = $this->db->execute($sql, $this->bindings);
        $this->clear();
        return $count;
    }

    public function delete(): int
    {
        $sql = sprintf("DELETE FROM %s", $this->escapeIdentifier($this->table));
        $whereSql = $this->buildWhereClause();
        if ($whereSql === '') {
            $this->clear();
            throw new \RuntimeException('Unsafe query: DELETE without WHERE clause.');
        }
        if ($whereSql !== '') {
            $sql .= " WHERE " . $whereSql;
        }
        $count = $this->db->execute($sql, $this->bindings);
        $this->clear();
        return $count;
    }

    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->db->commit();
    }

    public function rollback(): bool
    {
        return $this->db->rollBack();
    }

    public function toSql(): string
    {
        $cols = [];
        foreach ($this->columns as $c) {
            if ($c instanceof RawExpression) {
                $cols[] = $c->getValue();
                continue;
            }
            if ($c === '*') {
                $cols[] = '*';
                continue;
            }
            $cols[] = $this->escapeIdentifier($c);
        }
        $sql = sprintf('SELECT %s FROM %s', implode(', ', $cols), $this->escapeIdentifier($this->table));

        if (!empty($this->joins)) {
            foreach ($this->joins as $j) {
                $sql .= ' ' . $j['sql'];
            }
        }

        $whereSql = $this->buildWhereClause();
        if ($whereSql !== '') {
            $sql .= ' WHERE ' . $whereSql;
        }

        if (!empty($this->groups)) {
            $grp = array_map([$this, 'formatIdentifierOrRaw'], $this->groups);
            $sql .= ' GROUP BY ' . implode(', ', $grp);
        }

        if (!empty($this->havings)) {
            $haveSql = $this->buildHavingClause();
            if ($haveSql !== '') {
                $sql .= ' HAVING ' . $haveSql;
            }
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

    public function clear(): void
    {
        $this->columns = ['*'];
        $this->wheres = [];
        $this->joins = [];
        $this->groups = [];
        $this->havings = [];
        $this->orders = [];
        $this->limit = null;
        $this->offset = null;
        $this->bindings = [];
        $this->paramCounter = 1;
    }

    /* ---------- Helpers ---------- */
    protected function makePlaceholder(): string
    {
        return 'p' . $this->paramCounter++;
    }

    protected function formatIdentifierOrRaw(string|RawExpression $value): string
    {
        if ($value instanceof RawExpression) {
            return $value->getValue();
        }
        return $this->escapeIdentifier($value);
    }

    /**
     * Escape identifiers (table/column) for MySQL using backticks.
     * Handles dot-separated identifiers and simple aliases like `column as alias`.
     */
    protected function escapeIdentifier(string $name): string
    {
        $name = trim($name);
        // preserve expressions wrapped as RawExpression elsewhere
        // handle alias with AS (case-insensitive)
        if (preg_match('/\s+AS\s+/i', $name)) {
            [$left, $alias] = preg_split('/\s+AS\s+/i', $name, 2);
            return $this->escapeIdentifier($left) . ' AS ' . $this->escapeIdentifier($alias);
        }
        // handle space alias like `col alias`
        if (preg_match('/\s+/', $name)) {
            [$left, $alias] = preg_split('/\s+/', $name, 2);
            return $this->escapeIdentifier($left) . ' AS ' . $this->escapeIdentifier($alias);
        }
        // wildcard
        if ($name === '*') {
            return '*';
        }
        // dot notation
        if (str_contains($name, '.')) {
            $parts = explode('.', $name);
            $parts = array_map(function ($p) {
                return '`' . str_replace('`', '``', $p) . '`';
            }, $parts);
            return implode('.', $parts);
        }
        return '`' . str_replace('`', '``', $name) . '`';
    }

    protected function buildWhereClause(): string
    {
        if (empty($this->wheres)) {
            return '';
        }
        $parts = [];
        foreach ($this->wheres as $i => $w) {
            $prefix = $i === 0 ? '' : ' ' . $w['boolean'] . ' ';
            $parts[] = $prefix . $w['sql'];
        }
        return implode('', $parts);
    }

    protected function buildHavingClause(): string
    {
        if (empty($this->havings)) {
            return '';
        }
        $parts = [];
        foreach ($this->havings as $i => $h) {
            $prefix = $i === 0 ? '' : ' ' . $h['boolean'] . ' ';
            $parts[] = $prefix . $h['sql'];
        }
        return implode('', $parts);
    }
}

// Example usage (with Database wrapper)
// $db = App\Core\Database::getInstance();
// $builder = new QueryBuilder($db, 'users');
// $users = $builder->where(function($q) {
//         $q->where('age', '>', 18)
//             ->orWhere('role', '=', 'admin');
// })->orderBy('name')->limit(10)->get();

// $id = $builder->insertGetId(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

// $affected = $builder->where('id', '=', $id)->update(['name' => 'Jane Q. Doe']);

// $rawNow = new RawExpression('NOW()');
// $builder->insert(['name' => 'Raw Time', 'created_at' => $rawNow]);

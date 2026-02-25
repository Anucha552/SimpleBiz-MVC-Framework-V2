<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use App\Core\RawExpression;

class ModelQueryBuilder extends QueryBuilder
{
    protected string $modelClass = '';

    public function __construct(
        Database $db,
        string $table,
        string $modelClass = '',
        bool $applySoftDelete = true
    ) {
        parent::__construct($db, $table);
        $this->modelClass = $modelClass;

        if (
            $applySoftDelete &&
            $this->modelClass !== '' &&
            $this->modelClass::usesSoftDeletes()
        ) {
            $sql = $this->formatIdentifierOrRaw('deleted_at') . ' IS NULL';
            $this->wheres[] = [
                'boolean' => 'AND',
                'sql' => $sql,
                'soft_delete' => true
            ];
        }
    }

    public function insert(array $data): bool
    {
        if ($this->modelClass !== '') {
            $data = $this->modelClass::prepareInsertData($data);
        }

        return parent::insert($data);
    }

    public function insertGetId(array $data): int
    {
        if ($this->modelClass !== '') {
            $data = $this->modelClass::prepareInsertData($data);
        }

        return parent::insertGetId($data);
    }

    public function update(array $data): int
    {
        if ($this->modelClass !== '') {
            $data = $this->modelClass::prepareUpdateData($data);
        }

        return parent::update($data);
    }

    public function delete(): int
    {
        if (
            $this->modelClass !== '' &&
            $this->modelClass::usesSoftDeletes()
        ) {
            return parent::update([
                'deleted_at' => new RawExpression('CURRENT_TIMESTAMP')
            ]);
        }

        return parent::delete();
    }

    public function restore(): int
    {
        if (
            $this->modelClass === '' ||
            !$this->modelClass::usesSoftDeletes()
        ) {
            return 0;
        }

        return parent::update([
            'deleted_at' => new RawExpression('NULL')
        ]);
    }

    public function withTrashed(): self
    {
        $this->removeDeletedAtConstraint();
        return $this;
    }

    public function onlyTrashed(): self
    {
        $this->removeDeletedAtConstraint();
        return $this->whereNotNull('deleted_at');
    }

    public function forceDelete(): int
    {
        return parent::delete();
    }

    protected function removeDeletedAtConstraint(): void
    {
        $newWheres = [];

        foreach ($this->wheres as $w) {
            if (!empty($w['soft_delete'])) {
                continue;
            }

            $newWheres[] = $w;
        }

        $this->wheres = $newWheres;
    }
}
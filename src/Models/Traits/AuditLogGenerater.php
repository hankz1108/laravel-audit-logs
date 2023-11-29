<?php

namespace Hankz\LaravelAuditLog\Models\Traits;

use Hankz\LaravelAuditLog\Constants\AuditLogGeneraterConstant;

trait AuditLogGenerater
{
    /**
     * 上次變更紀錄.
     *
     * @var array
     */
    private $lastAuditLogs = [];

    /**〞
     * 上次變更方式.
     *
     * @var int|null
     */
    private $lastChangeMethod = null;

    /**
     * update and save change log.
     */
    public function updateWithAuditLog(array $attributes = [], array $options = [])
    {
        if (! $this->exists) {
            return false;
        }

        $this->fill($attributes);

        $this->lastAuditLogs = $this->generateAuditLogs(AuditLogGeneraterConstant::METHOD_UPDATE);

        return $this->save($options);
    }

    /**
     * Get the first related model record matching the attributes or instantiate it and save change log.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateOrCreateWithAuditLog(array $attributes, array $values = [])
    {
        return tap($this->firstOrNew($attributes), function ($instance) use ($values) {
            $instance->fill($values);

            if ($instance->exists) {
                $instance->lastAuditLogs = $instance->generateAuditLogs(AuditLogGeneraterConstant::METHOD_UPDATE);
            } else {
                $instance->lastAuditLogs = $instance->generateAuditLogs(AuditLogGeneraterConstant::METHOD_CREATE);
            }

            $instance->save();
        });
    }

    public function getLastAuditLogs(): array
    {
        return $this->lastAuditLogs;
    }

    public function getLastChangeMethod(): ?int
    {
        return $this->lastChangeMethod;
    }

    /**
     * 設定上次變更方式.
     */
    public function setLastChangeMethod(int $lastChangeMethod): self
    {
        if (key_exists($lastChangeMethod, AuditLogGeneraterConstant::METHOD_MAP)) {
            $this->lastChangeMethod = $lastChangeMethod;
        }

        return $this;
    }

    /**
     * has change logs.
     *
     * @return bool
     */
    public function hasAuditLogs(): bool
    {
        return !empty($this->lastAuditLogs);
    }

    /**
     * 產生變更紀錄.
     * return format:
     * {
     *   'update' => [
     *     '<欄位名稱>' => [
     *       'title' => '<欄位標題>',
     *       'old' => '<舊值>',
     *       'new' => '<新值>',
     *     ],
     *     ...
     *   ]
     * }
     *
     * @return array
     */
    public function generateAuditLogs(int $lastChangeMethod = null): array
    {
        if (isset($lastChangeMethod)) {
            $this->setLastChangeMethod($lastChangeMethod);
        }

        $AuditLogs = [];

        $AuditLogColumns = $this->getAuditLogColumns();

        $changes = $this->getDirty();

        foreach ($changes as $column => $newValue) {
            if (!array_key_exists($column, $AuditLogColumns)) {
                continue;
            }

            $AuditLogs[$column]['title'] = $this->transformTitleToHumanable($column);
            $AuditLogs[$column]['new'] = $this->transformValueToHumanable($column, $newValue);

            if ($this->lastChangeMethod == AuditLogGeneraterConstant::METHOD_UPDATE) {
                $AuditLogs[$column]['old'] = $this->transformValueToHumanable($column, $this->getOriginal($column));
            }
        }

        if (empty($AuditLogs)) {
            return [];
        }

        if (!key_exists($this->lastChangeMethod, AuditLogGeneraterConstant::METHOD_MAP)) {
            return [];
        }

        return [
            AuditLogGeneraterConstant::METHOD_MAP[$this->lastChangeMethod] => $AuditLogs,
        ];
    }

    /**
     * 將欄位名稱轉換成可讀文字.
     *
     * @param string $field 欄位名稱
     * @param Callable|array $value 欄位轉換方式
     *
     * @return string
     */
    public function transformTitleToHumanable($column, $value = null)
    {
        $AuditLogColumns = $this->getAuditLogColumns();

        $transformer = data_get($AuditLogColumns, $column . '.title');

        if (is_string($transformer)) {
            return $transformer;
        }

        if (is_callable($transformer)) {
            return $transformer($value, $this);
        }

        if (is_array($transformer)) {
            return data_get($transformer, $value, $value);
        }

        return $column;
    }

    /**
     * 將欄位內容轉換成可讀文字.
     *
     * @param string $field 欄位名稱
     * @param Callable|array $value 欄位轉換方式
     *
     * @return string
     */
    public function transformValueToHumanable($column, $value)
    {
        $AuditLogColumns = $this->getAuditLogColumns();

        $transformer = data_get($AuditLogColumns, $column . '.value');

        if (is_callable($transformer)) {
            return $transformer($value, $this);
        }

        if (is_array($transformer)) {
            return data_get($transformer, $value, $value);
        }

        return $value;
    }

    /**
     * 取得需要紀錄的欄位.
     * return format:
     * [
     *   '<欄位名稱>' => [
     *     'title' => Callable|array|string,
     *     'value' => Callable|array
     *   ],
     *   ...
     * ]
     *
     * @return array
     */
    protected function getAuditLogColumns()
    {
        return $this->fillable;
    }
}
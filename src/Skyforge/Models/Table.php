<?php

namespace UntitledDevelopers\KockatoosAdminCore\Skyforge\Models;

class Table
{

    private string $name;

    /**
     * @var Column[]
     */
    private array $columns;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->columns = $this->getColumnsDataList();
    }

    private function getColumnsDataList(): array
    {
        $columns = \Schema::getColumnListing($this->name);

        return array_map(function ($column) {
            return new Column($column, \Schema::getColumnType($this->name, $column));
        }, $columns);
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumnsNames(): array
    {
        return array_map(function (Column $column) {
            return $column->getName();
        }, $this->columns);
    }

    public function getPrefixedColumns(): array
    {
        return array_map(function (Column $column) {
            return $this->name . '.' . $column->getName();
        }, $this->columns);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTableModelName(): string
    {
        return \Str::studly(\Str::singular($this->name));
    }

    /**
     * @return Column[]
     */
    public function getBooleanColumns(): array
    {
        return array_filter($this->columns, function (Column $column) {
            return $column->isBoolean();
        });
    }
}

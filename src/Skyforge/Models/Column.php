<?php

namespace UntitledDevelopers\KockatoosAdminCore\Skyforge\Models;

class Column
{
    private string $name;
    private string $type;


    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isBoolean(): bool
    {
        return $this->type === 'boolean' || $this->type === 'tinyint';
    }

    public function isString(): bool
    {
        return $this->type === 'string' || $this->type === 'text';
    }

    public function isForeignId(): bool
    {
        return $this->type === 'foreignId' || $this->type === 'unsignedBigInteger';
    }

}

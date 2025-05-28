<?php


namespace UntitledDevelopers\KockatoosAdminCore\Models;


use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;


class BaseModel extends Model
{
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

}

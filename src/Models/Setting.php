<?php

namespace Dominservice\LaravelConfig\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $value
 */
class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public $timestamps = false;
}

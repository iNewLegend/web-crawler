<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin Builder (Handles issues with autocomplete in IDEs)
 */
class Url extends Model
{
    protected $fillable = [
        'url',
        "url_hash",
        "text",
        'depth',
        "owner_ids",
    ];
}

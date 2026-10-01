<?php

declare(strict_types=1);

namespace DevExtreme\Data\Laravel\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Order extends Model
{
    use SoftDeletes;

    public $timestamps = false;
    protected $table = 'orders';
    protected $guarded = [];
}

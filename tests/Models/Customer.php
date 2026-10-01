<?php

declare(strict_types=1);

namespace DevExtreme\Data\Laravel\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Customer extends Model
{
    public $timestamps = false;
    protected $table = 'customers';
    protected $guarded = [];

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }
}

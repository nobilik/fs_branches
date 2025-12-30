<?php

namespace Modules\NobilikBranches\Entities;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'addresses';

    protected $fillable = [
        // Унифицированное поле GUID от внешнего сервиса и человекочитаемый текст
        'guid',
        'full_address',
        'meta',
    ];

    // На одном адресе может быть несколько объектов
    public function branches()
    {
        return $this->hasMany(Branch::class, 'address_id');
    }

}

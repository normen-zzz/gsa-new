<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Model;

class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'id_customer';
    protected $fillable = [
        'name',
        'status',
        'created_by',
    ];
}

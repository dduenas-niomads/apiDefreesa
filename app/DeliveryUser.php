<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeliveryUser extends Model
{
    protected $connection = 'mysql';
    const TABLE_NAME = 'delivery_users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name', 'lastname', 'email', 'password', 'active',
        'activation_token', 'forgot_password_token',
        'phone', 'document_number', 'address_info'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'activation_token', 'forgot_password_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'address_info' => 'array'
    ];
    protected $table = self::TABLE_NAME;
}

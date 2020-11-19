<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $connection = 'mysql';
    const TABLE_NAME = 'acl_partner_users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name', 'users_id','password','active',
        'activation_token', 'forgot_password_token',
        'phone', 'ruc', 'address_info'
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

    public function user()
    {
        return $this->belongsTo('App\User', 'users_id')
            ->whereNull('deleted_at');
    }

    protected $table = self::TABLE_NAME;
}

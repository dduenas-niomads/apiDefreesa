<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $connection = 'mysql';
    const TABLE_NAME = 'bs_orders';
    const STATE_ACTIVE = true;
    const STATE_INACTIVE = false;
    const STATUS_PROCEED = 2;
    const STATUS_NOT_PROCEED = 6;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        //Table Rows
        'id','users_id','details_info','total','total_info',
        'status','commentary','commentary_info','purchase_info',
        //Audit 
        'flag_active','created_at','updated_at','deleted_at',
    ];
    /**
     * Casting of attributes
     *
     * @var array
     */
    protected $casts = [
        'details_info' => 'array',
        'total_info' => 'array',
        'purchase_info' => 'array'
    ];    
    public function getFillable() {
        # code...
        return $this->fillable;
    }
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $table = self::TABLE_NAME;
}
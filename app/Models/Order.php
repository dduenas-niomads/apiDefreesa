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
        'bs_suppliers_id','status','commentary_info','purchase_info',
        'address_info','commentary','tips','type_document','document_number',
        'delivery_status',
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
        'purchase_info' => 'array',
        'address_info' => 'array'
    ];    
    public function getFillable() {
        # code...
        return $this->fillable;
    }
    public function supplier()
    {
        return $this->belongsTo('App\Models\Supplier', 'bs_suppliers_id')
            ->whereNull('deleted_at');
    }
    public function orderStatus()
    {
        return $this->belongsTo('App\Models\MsOrderStatus', 'status')
            ->whereNull('deleted_at');
    }
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $table = self::TABLE_NAME;
}
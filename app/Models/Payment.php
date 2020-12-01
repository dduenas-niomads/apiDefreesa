<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $connection = 'mysql';
    const TABLE_NAME = 'payments';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'id','users_id','total','bs_suppliers_id','acl_delivery_users_id',
        'status','delivery_status',
        //Audit 
        'flag_active','created_at','updated_at','deleted_at',
    ];

    public function getFillable() {
        # code...
        return $this->fillable;
    }
    public function supplier()
    {
        return $this->belongsTo('App\Models\Supplier', 'bs_suppliers_id')
            ->select('id', 'name', 'url_image', 'phone')
            ->whereNull('deleted_at');
    }
    public function customer()
    {
        return $this->belongsTo('App\User', 'users_id')
            ->select('id', 'name', 'phone')
            ->whereNull('deleted_at');
    }
    public function deliveryUser()
    {
        return $this->belongsTo('App\DeliveryUser', 'acl_delivery_users_id')
            ->select('id', 'name', 'phone', 'email', 'lastname')
            ->whereNull('deleted_at');
    }
    public function orderStatus()
    {
        return $this->belongsTo('App\Models\MsOrderStatus', 'status')
            ->select('id', 'name', 'description')
            ->whereNull('deleted_at');
    }

    protected $table = self::TABLE_NAME;
}

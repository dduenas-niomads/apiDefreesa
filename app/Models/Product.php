<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'mysql';
    const TABLE_NAME = 'bs_products';
    const STATE_ACTIVE = true;
    const STATE_INACTIVE = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        //Table Rows
        'id','bs_suppliers_id','bs_ms_products_categories_id','acl_partner_users_id',
        'currency','price','name','description','url_image',
        'flag_type_label',
        //Audit 
        'flag_active','created_at','updated_at','deleted_at',
    ];
    /**
     * Casting of attributes
     *
     * @var array
     */
    protected $casts = [
    ];

    public function category()
    {
        return $this->belongsTo('App\Models\MsProductCategory', 'bs_ms_products_categories_id')
            ->whereNull('deleted_at');
    }

    public function supplier()
    {
        return $this->belongsTo('App\Models\Supplier', 'bs_suppliers_id')
            ->whereNull('deleted_at')
            ->select('id', 'name');
    }

    public function partner()
    {
        return $this->belongsTo('App\Partner', 'acl_partner_users_id')
            ->whereNull('deleted_at');
    }
    
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
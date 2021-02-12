<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $connection = 'mysql';
    const TABLE_NAME = 'bs_suppliers';
    const STATE_ACTIVE = true;
    const STATE_INACTIVE = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        //Table Rows
        'id','bs_categories_id','name','description','url_image','acl_partner_users_id', 'bs_ms_region_id',
        'image_carrousel','phone','ruc','business_name','address','address_info','on_demand','on_demand_now',
        //Audit 
        'flag_active','created_at','updated_at','deleted_at',
    ];
    /**
     * Casting of attributes
     *
     * @var array
     */
    protected $casts = [
        'image_carrousel' => 'array',
        'address_info' => 'array',
    ];    
    public function getFillable() {
        # code...
        return $this->fillable;
    }
    public function category()
    {
        return $this->belongsTo('App\Models\Category', 'bs_categories_id')
            ->select('id', 'name')
            ->whereNull('deleted_at');
    }

    public function region()
    {
        return $this->belongsTo('App\Models\MsRegion', 'bs_ms_region_id')
            ->whereNull('deleted_at');
    }
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    public function partner()
    {
        return $this->belongsTo('App\Partner', 'acl_partner_users_id')
            ->whereNull('deleted_at');
    }

    protected $table = self::TABLE_NAME;
}
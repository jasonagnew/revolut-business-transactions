<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OauthKey extends Model
{
     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_keys';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'service', 
    	'access_token', 
    	'expires', 
        'refresh_token',
        'uid',
    	'created_at',
    	'updated_at',
   	];

    /**
     * Validation Rules
     *
     * @var array
     */
    public $validationRules = [];

}

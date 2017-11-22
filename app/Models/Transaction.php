<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'transaction_id', 
    	'type', 
    	'state', 
    	'created_at',
    	'updated_at',
    	'completed_at',
    	'legs'
   	];

    /**
     * Validation Rules
     *
     * @var array
     */
    public $validationRules = [
    	'transaction_id' => 'required|max:255', 
    	'type' => 'required|max:255', 
    	'state' => 'required|max:255', 
    	'created_at' => 'required|max:255',
    	'updated_at' => 'required|max:255',
    	'completed_at' => 'required|max:255',
    	'legs' => 'required|json|max:10240'
    ];

    /**
     * Get Legs
     */
    public function getLegs()
    {
        return json_decode($this->legs);
    }
}

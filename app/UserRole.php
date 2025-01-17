<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model {
	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'user_roles';
	
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['user_id', 'role_id'];
	
	public $timestamps = false;
	
	
	public function user()
	{
		return $this->belongsTo('\App\User', 'user_id');
	}
	
	public function role()
	{
		return $this->belongsTo('\App\Role', 'role_id');
	}
	
}

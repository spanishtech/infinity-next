<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model {
	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'role_permissions';
	
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['role_id', 'permission_id', 'value'];
	
	public $timestamps = false;
	
	
	public function permission()
	{
		return $this->belongsTo('\App\Permission', 'permission_id');
	}
	
	public function role()
	{
		return $this->belongsTo('\App\Role', 'role_id');
	}
	
}

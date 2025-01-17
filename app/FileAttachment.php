<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class FileAttachment extends Model {
	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'file_attachments';
	
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['post_id', 'file_id', 'filename', 'is_spoiler'];
	
	public $timestamps = false;
	
	
	public function post()
	{
		return $this->belongsTo('\App\Post', 'post_id');
	}
	
	public function storage()
	{
		return $this->belongsTo('\App\FileStorage', 'file_id');
	}
	
	
	/**
	 * Ties database triggers to the model.
	 *
	 * @return void
	 */
	public static function boot()
	{
		parent::boot();
		
		// Setup event bindings...
		
		// Fire events on post created.
		static::created(function(FileAttachment $attachment) {
			$attachment->storage->processAttachment($attachment);
		});
	}
	
	/**
	 * Returns a few posts for the front page.
	 *
	 * @param  int  $number  How many to pull.
	 * @param  boolean $sfwOnly  If we only want SFW boards.
	 * @return Collection  of static
	 */
	public static function getRecentImages($number = 16, $sfwOnly = true)
	{
		return static::distinct('file_id')
			->orderBy('attachment_id', 'desc')
			->whereHas('storage', function($query) {
				$query->where('has_thumbnail', '=', true);
			})
			->whereHas('post.board', function($query) use ($sfwOnly) {
				$query->where('is_indexed', '=', true);
				$query->where('is_overboard', '=', true);
				
				if ($sfwOnly)
				{
					$query->where('is_worksafe', '=', true);
				}
			})
			->with('storage')
			->with('post.board')
			->groupBy('file_id')
			->limit(20)
			->get();
	}
	
}

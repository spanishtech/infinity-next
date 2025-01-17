<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use File;
use Input;
use Sleuth;
use Storage;

class FileStorage extends Model {
	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'files';
	
	/**
	 * The database primary key.
	 *
	 * @var string
	 */
	protected $primaryKey = 'file_id';
	
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['hash', 'banned', 'filesize', 'mime', 'meta', 'first_uploaded_at', 'last_uploaded_at', 'upload_count', 'has_thumbnail'];
	
	public $timestamps = false;
	
	public function attachments()
	{
		return $this->hasMany('\App\FileAttachment', 'file_id');
	}
	
	public function assets()
	{
		return $this->hasMany('\App\BoardAsset', 'file_id');
	}
	
	public function posts()
	{
		return $this->belongsToMany("\App\Post", 'file_attachments', 'file_id', 'post_id')->withPivot('filename');
	}
	
	
	public static function getHash($hash)
	{
		return static::hash($hash)->get()->first();
	}
	
	public static function getHashPrefix($hash)
	{
		return implode(str_split(substr($hash, 0, 4)), "/");
	}
	
	/**
	 * Converts the byte size to a human-readable filesize.
	 *
	 * @author Jeffrey Sambells
	 * @param  int  $decimals
	 * @return string
	 */
	public function getHumanFilesize($decimals = 2)
	{
		$bytes  = $this->filesize;
		$size   = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		$factor = floor((strlen($bytes) - 1) / 3);
		
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . " " . @$size[$factor];
	}
	
	public function getDirectory()
	{
		$prefix = $this->getHashPrefix($this->hash);
		
		return "attachments/full/{$prefix}";
	}
	
	public function getDirectoryThumb()
	{
		$prefix = $this->getHashPrefix($this->hash);
		
		return "attachments/thumb/{$prefix}";
	}
	
	public function getAsFile()
	{
		return new File($this->getFullPath());
	}
	
	public function getAsFileThumb()
	{
		return new File($this->getFullPathThumb());
	}
	
	public function getFullPath()
	{
		return storage_path() . "/app/" . $this->getPath();
	}
	
	public function getFullPathThumb()
	{
		return storage_path() . "/app/" . $this->getPathThumb();
	}
	
	public function getPath()
	{
		return $this->getDirectory() . "/" . $this->hash;
	}
	
	public function getPathThumb()
	{
		return $this->getDirectoryThumb() . "/" . $this->hash;
	}
	
	public function isSpoiler()
	{
		return isset($this->pivot) && isset($this->pivot->is_spoiler) && !!$this->pivot->is_spoiler;
	}
	
	public function hasThumb()
	{
		return file_exists($this->getFullPathThumb());
	}
	
	public function scopeWhereCanDelete($query)
	{
		return $query->where('banned', false);
	}
	
	public function scopeWhereOprhan($query)
	{
		return $query->whereCanDelete()
			->has('assets',      '=', 0)
			->has('attachments', '=', 0);
	}
	
	public function scopeHash($query, $hash)
	{
		return $query->where('hash', $hash);
	}
	
	
	/**
	 * Will trigger a file deletion if the storage item is not used anywhere.
	 *
	 * @return boolean
	 */
	public function challengeExistence()
	{
		$count = $this->assets->count() + $this->attachments->count();
		
		if ($count === 0)
		{
			$this->forceDelete();
			return false;
		}
		
		return true;
	}
	
	/**
	 * A dumb way to guess the file type based on the mime
	 * 
	 * @return string
	 */
	
	public function guessExtension()
	{
		$mimes = explode("/", $this->mime);
		
		switch ($this->mime)
		{
			##
			# IMAGES
			##
			case "image/svg+xml" :
				return "svg";
			
			case "image/jpeg" :
			case "image/jpg" :
				return "jpg";
			
			case "image/gif" :
				return "gif";
			
			case "image/png" :
				return "png";
			
			##
			# DOCUMENTS
			##
			case "text/plain" :
				return "txt";
			
			case "application/epub+zip" :
				return "epub";
			
			case "application/pdf" :
				return "pdf";
			
			##
			# AUDIO
			##
			case "audio/mpeg" :
			case "audio/mp3" :
				return "mp3";
			
			case "audio/aac" :
				return "aac";
			
			case "audio/mp4" :
				return "mp3";
			
			case "audio/ogg" :
				return "ogg";
			
			case "audio/wave" :
				return "wav";
			
			case "audio/webm" :
				return "wav";
			
			##
			# VIDEO
			##
			case "video/3gp" :
				return "3gp";
			
			case "video/webm" :
				return "webm";
			
			case "video/mp4" :
				return "mp4";
			
			case "video/ogg" :
				return "ogg";
			
			case "video/x-flv" :
				return "flv";
		}
		
		return $mimes[1];
	}
	
	
	
	/**
	 * Supplies a clean URL for downloading an attachment on a board.
	 *
	 * @param  App\Board  $board
	 * @return string
	 */
	public function getDownloadURL(Board $board)
	{
		if (isset($this->pivot) && isset($this->pivot->filename))
		{
			return url("/{$board->board_uri}/file/{$this->hash}/") . "/" . $this->pivot->filename;
		}
		else
		{
			return url("/{$board->board_uri}/file/{$this->hash}/") . "/" . strtotime($this->first_uploaded_at) . "." . $this->guessExtension();
		}
	}
	
	/**
	 * Returns an XML valid attachment HTML string that handles missing thumbnail URLs.
	 *
	 * @return string as HTML
	 */
	public function getThumbnailHTML(Board $board)
	{
		$ext   = $this->guessExtension();
		$mime  = $this->mime;
		$url   = "/img/filetypes/{$ext}.svg";
		$type  = "other";
		$html  = "";
		$stock = true;
		$spoil = $this->isSpoiler();
		
		if ($this->isVideo())
		{
			if ($this->hasThumb())
			{
				$stock = false;
				$url   = $this->getThumbnailURL($board);
				$type  = "video";
			}
		}
		else if ($this->isAudio())
		{
			$stock = false;
			$type  = "audio";
			$url   = $this->getThumbnailURL($board);
		}
		else if ($this->isImage())
		{
			if ($this->hasThumb())
			{
				$stock = false;
				$url   = $this->getThumbnailURL($board);
				$type  = "img";
			}
		}
		else if ($this->isImageVector())
		{
			$stock = false;
			$url   = $this->getDownloadURL($board);
			$type  = "img";
		}
		
		$classes = [];
		$classes['type']  = "attachment-type-{$type}";
		$classes['ext']   = "attachent-ext-{$ext}";
		$classes['stock'] = $stock ? "thumbnail-stock" : "thumbnail-content";
		$classes['spoil'] = $spoil ? "thumbnail-spoiler" : "thumbnail-not-spoiler";
		$classHTML = implode(" ", $classes);
		
		return "<div class=\"attachment-wrapper {$classHTML}\"><img class=\"attachment-img {$classHTML}\" src=\"{$url}\" data-mime=\"{$mime}\" /></div>";
	}
	
	/**
	 * Supplies a clean thumbnail URL for embedding an attachment on a board.
	 *
	 * @param  App\Board  $board
	 * @return string
	 */
	public function getThumbnailURL(Board $board)
	{
		$baseURL = "/{$board->board_uri}/file/thumb/{$this->hash}/";
		$ext     = $this->guessExtension();
		
		if ($this->isSpoiler())
		{
			return $board->getSpoilerUrl();
		}
		
		if ($this->isAudio())
		{
			if (!$this->hasThumb())
			{
				return $board->getAudioArtURL();
			}
		}
		else if ($this->isImageVector())
		{
			// With the SVG filetype, we do not generate a thumbnail, so just serve the actual SVG.
			$baseURL ="/{$board->board_uri}/file/{$this->hash}/";
		}
		
		// Sometimes we supply a filename when fetching the filestorage as an attachment.
		if (isset($this->pivot) && isset($this->pivot->filename))
		{
			return url($baseURL . urlencode($this->pivot->filename));
		}
		
		return url($baseURL . strtotime($this->first_uploaded_at) . "." . $this->guessExtension());
	}
	
	/**
	 * Is this attachment an image?
	 *
	 * @return boolean
	 */
	public function isImage()
	{
		switch ($this->mime)
		{
			case "image/jpeg" :
			case "image/jpg" :
			case "image/gif" :
			case "image/png" :
				return true;
		}
		
		return false;
	}
	
	/**
	 * Is this attachment an image vector (SVG)?
	 *
	 * @reutrn boolean
	 */
	public function isImageVector()
	{
		return $this->mime === "image/svg+xml";
	}
	
	/**
	 * Is this attachment audio?
	 *
	 * @return boolean
	 */
	public function isAudio()
	{
		switch ($this->mime)
		{
			case "audio/mpeg" :
			case "audio/mp3" :
			case "audio/aac" :
			case "audio/mp4" :
			case "audio/ogg" :
			case "audio/wave" :
			case "audio/webm" :
				return true;
		}
		
		return false;
	}
	
	/**
	 * Is this attachment a video?
	 * Primarily used to split files on HTTP range requests.
	 *
	 * @return boolean
	 */
	public function isVideo()
	{
		switch ($this->guessExtension())
		{
			case "video/3gp" :
			case "video/webm" :
			case "video/mp4" :
			case "video/ogg" :
			case "video/x-flv" :
				return true;
		}
		
		return false;
	}
	
	
	/**
	 * Creates a new FileAttachment for a post using a direct upload.
	 *
	 * @param  UploadedFile  $file
	 * @param  Post  $post
	 * @return FileAttachment
	 */
	public static function createAttachmentFromUpload(UploadedFile $file, Post $post, $autosave = true)
	{
		$storage     = static::storeUpload($file);
		
		$uploadName  = urlencode($file->getClientOriginalName());
		$uploadExt   = pathinfo($uploadName, PATHINFO_EXTENSION);
		
		$fileName    = basename($uploadName, "." . $uploadExt);
		$fileExt     = $storage->guessExtension();
		
		$attachment  = new FileAttachment();
		$attachment->post_id    = $post->post_id;
		$attachment->file_id    = $storage->file_id;
		$attachment->filename   = urlencode("{$fileName}.{$fileExt}");
		$attachment->is_spoiler = !!Input::get('spoilers');
		
		if ($autosave)
		{
			$attachment->save();
			
			$storage->upload_count++;
			$storage->save();
		}
		
		return $attachment;
	}
	
	/**
	 * Creates a new FileAttachment for a post using a hash.
	 *
	 * @param  Post  $post
	 * @param  string  $filename
	 * @param  boolean  $spoiler
	 * @return FileAttachment
	 */
	public function createAttachmentWithThis(Post $post, $filename, $spoiler = false, $autosave = true)
	{
		$fileName    = pathinfo($filename, PATHINFO_FILENAME);
		$fileExt     = $this->guessExtension();
		
		$attachment  = new FileAttachment();
		$attachment->post_id    = $post->post_id;
		$attachment->file_id    = $this->file_id;
		$attachment->filename   = urlencode("{$fileName}.{$fileExt}");
		$attachment->is_spoiler = !!$spoiler;
		
		if ($autosave)
		{
			$attachment->save();
			
			$this->upload_count++;
			$this->save();
		}
		
		return $attachment;
	}
	
	/**
	 * Work to be done upon creating an attachment using this storage.
	 *
	 * @param  FileAttachment  $attachment  Defaults to null.
	 * @return FileStorage
	 */
	public function processAttachment(FileAttachment $attachment = null)
	{
		$this->last_uploaded_at = $this->freshTimestamp();
		// Not counting uploads unless it ends up on a post.
		// $this->upload_count    += 1;
		
		
		$this->processThumb();
		$this->save();
		
		return $this;
	}
	
	/**
	 * Turns an image into a thumbnail if possible, overwriting previous versions.
	 *
	 * @return void
	 */
	public function processThumb()
	{
		global $app;
		
		switch ($this->guessExtension())
		{
			case "mp3"  :
			case "mpga" :
			case "wav"  :
				if (!Storage::exists($this->getFullPathThumb()))
				{
					$ID3  = new \getID3();
					$meta = $ID3->analyze($this->getFullPath());
					
					if (isset($meta['comments']['picture']) && count($meta['comments']['picture']))
					{
						foreach ($meta['comments']['picture'] as $albumArt)
						{
							try
							{
								$imageManager = new ImageManager;
								$imageManager
									->make($albumArt['data'])
									->resize(
										$app['settings']('attachmentThumbnailSize'),
										$app['settings']('attachmentThumbnailSize'),
										function($constraint) {
											$constraint->aspectRatio();
											$constraint->upsize();
										}
									)
									->save($this->getFullPathThumb());
								
								$this->has_thumbnail = true;
							}
							catch (\Exception $error)
							{
								// Nothing.
							}
							
							break;
						}
					}
				}
				break;
			
			case "flv"  :
			case "mp4"  :
			case "webm" :
				if (!Storage::exists($this->getFullPathThumb()))
				{
					Storage::makeDirectory($this->getDirectoryThumb());
					
					$video    = $this->getFullPath();
					$image    = $this->getFullPathThumb();
					$interval = 0;
					$frames   = 1;
					
					$cmd = env('LIB_VIDEO', "ffmpeg") . " -i $video -deinterlace -an -ss $interval -f mjpeg -t 1 -r 1 -y $image 2>&1";
					
					exec($cmd, $output, $returnvalue);
					
					// Constrain thumbnail to proper dimensions.
					if (Storage::exists($image))
					{
						$imageManager = new ImageManager;
						$imageManager
							->make($this->getFullPath())
							->resize(
								$app['settings']('attachmentThumbnailSize'),
								$app['settings']('attachmentThumbnailSize'),
								function($constraint) {
									$constraint->aspectRatio();
									$constraint->upsize();
								}
							)
							->save($this->getFullPathThumb());
						
						$this->has_thumbnail = true;
					}
				}
				break;
			
			case "jpg"  :
			case "gif"  :
			case "png"  :
				if (!Storage::exists($this->getFullPathThumb()))
				{
					Storage::makeDirectory($this->getDirectoryThumb());
					
					$imageManager = new ImageManager;
					$imageManager
						->make($this->getFullPath())
						->resize(
							$app['settings']('attachmentThumbnailSize'),
							$app['settings']('attachmentThumbnailSize'),
							function($constraint) {
								$constraint->aspectRatio();
								$constraint->upsize();
							}
						)
						->save($this->getFullPathThumb());
					
					$this->has_thumbnail = true;
				}
				break;
		}
	}
	
	/**
	 * Handles an UploadedFile from form input. Stores, creates a model, and generates a thumbnail.
	 *
	 * @param  UploadedFile  $upload
	 * @return FileStorage
	 */
	public static function storeUpload(UploadedFile $upload)
	{
		$fileContent  = File::get($upload);
		$fileMD5      = md5((string) File::get($upload));
		$storage      = static::getHash($fileMD5);
		
		if (!($storage instanceof static))
		{
			$storage           = new static();
			$fileTime          = $storage->freshTimestamp();
			
			$storage->hash     = $fileMD5;
			$storage->banned   = false;
			$storage->filesize = $upload->getSize();
			$storage->mime     = $upload->getClientMimeType();
			$storage->first_uploaded_at = $fileTime;
			$storage->upload_count = 0;
			
			if (!isset($upload->case))
			{
				$upload->case = Sleuth::check($upload->getRealPath());
			}
			
			if (is_object($upload->case))
			{
				$storage->mime = $upload->case->getMimeType();
				
				if ($upload->case->getMetaData())
				{
					$storage->meta = json_encode($upload->case->getMetaData());
				}
			}
		}
		else
		{
			$fileTime = $storage->freshTimestamp();
		}
		
		if (!Storage::exists($storage->getPath()))
		{
			Storage::put($storage->getPath(), $fileContent);
			Storage::makeDirectory($storage->getDirectoryThumb());
		}
		
		$storage->processAttachment();
		
		return $storage;
	}
}

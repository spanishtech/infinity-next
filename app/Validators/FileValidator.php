<?php namespace App\Validators;

use App\FileStorage;
use Illuminate\Validation\Validator;

use DB;
use Sleuth;

class FileValidator extends Validator
{
	public function validateMd5($attribute, $value, $parameters)
	{
		return !!preg_match('/^[a-f0-9]{32}$/i', $value);
	}
	
	public function validateFileName($attribute, $value, $parameters)
	{
		return preg_match("/^[^\/\?\*:;{}\\\]+\.[^\/\?\*:;{}\\\]+$/", $value) && !preg_match("/^(nul|prn|con|lpt[0-9]|com[0-9])(\.|$)/i", $value);
	}
	
	public function validateFileIntegrity($attribute, $file, $parameters)
	{
		if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile)
		{
			$detective = Sleuth::check($file->getRealPath());
			
			if ($detective !== false)
			{
				$file->sleuth = $detective;
				return true;
			}
		}
		
		return false;
	}
	
	public function validateFileNew($attribute, $value, $parameters)
	{
		return (int) DB::table( with(new FileStorage)->getTable() )->where('hash', $value)->pluck('upload_count') == 0;
	}
	
	public function validateFileOld($attribute, $value, $parameters)
	{
		return (int) DB::table( with(new FileStorage)->getTable() )->where('hash', $value)->pluck('upload_count') > 0;
	}
}

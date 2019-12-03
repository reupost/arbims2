<?php
/**
 * Implementation of various file system operations
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010 Uwe Steinmann
 * @version    Release: 4.3.8
 */

/**
 * Class to represent a user in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010 Uwe Steinmann
 * @version    Release: 4.3.8
 */
class SeedDMS_Core_File {
	static function renameFile($old, $new) { /* begin function */
		return @rename($old, $new);
	} /* end function */

	static function removeFile($file) { /* begin function */
		return @unlink($file);
	} /* end function */

	static function copyFile($source, $target) { /* begin function */
		return @copy($source, $target);
	} /* end function */

	static function moveFile($source, $target) { /* begin function */
		if (!@copyFile($source, $target))
			return false;
		return @removeFile($source);
	} /* end function */

	static function fileSize($file) { /* begin function */
		if(!$a = fopen($file, 'r'))
			return false;
		fseek($a, 0, SEEK_END);
		$filesize = ftell($a);
		fclose($a);
		return $filesize;
	} /* end function */

	static function format_filesize($size, $sizes = array('Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB')) { /* begin function */
        if ($size == 0) return('0 Bytes');
        $i = floor(log($size, 1024));
		//RR this is broken
        //return (round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $sizes[$i]);
        return (round($size/pow(1024, $i), 2) . ' ' . $sizes[$i]);
	} /* end function */

	static function parse_filesize($str) { /* begin function */
		preg_replace('/\s\s+/', ' ', $str);
		if(strtoupper(substr($str, -1)) == 'B') {
			$value = (int) substr($str, 0, -2);
			$unit = substr($str, -2, 1);
		} else {
			$value = (int) substr($str, 0, -1);
			$unit = substr($str, -1);
		}
		switch(strtoupper($unit)) {
			case 'G':
				return $value * 1024 * 1024 * 1024;
				break;
			case 'M':
				return $value * 1024 * 1024;
				break;
			case 'K':
				return $value * 1024;
				break;
			default;
				return $value;
				break;
		}
		return false;
	} /* end function */

	static function checksum($file) { /* begin function */
		return md5_file($file);
	} /* end function */

	static function renameDir($old, $new) { /* begin function */
		return @rename($old, $new);
	} /* end function */

	static function makeDir($path) { /* begin function */
		
		if( !is_dir( $path ) ){
			$res=@mkdir( $path , 0777, true);
			if (!$res) return false;
		}

		return true;

/* some old code 
		if (strncmp($path, DIRECTORY_SEPARATOR, 1) == 0) {
			$mkfolder = DIRECTORY_SEPARATOR;
		}
		else {
			$mkfolder = "";
		}
		$path = preg_split( "/[\\\\\/]/" , $path );
		for(  $i=0 ; isset( $path[$i] ) ; $i++ )
		{
			if(!strlen(trim($path[$i])))continue;
			$mkfolder .= $path[$i];

			if( !is_dir( $mkfolder ) ){
				$res=@mkdir( "$mkfolder" ,  0777);
				if (!$res) return false;
			}
			$mkfolder .= DIRECTORY_SEPARATOR;
		}

		return true;

		// patch from alekseynfor safe_mod or open_basedir

		global $settings;
		$path = substr_replace ($path, "/", 0, strlen($settings->_contentDir));
		$mkfolder = $settings->_contentDir;

		$path = preg_split( "/[\\\\\/]/" , $path );

		for(  $i=0 ; isset( $path[$i] ) ; $i++ )
		{
			if(!strlen(trim($path[$i])))continue;
			$mkfolder .= $path[$i];

			if( !is_dir( $mkfolder ) ){
				$res= @mkdir( "$mkfolder" ,  0777);
				if (!$res) return false;
			}
			$mkfolder .= DIRECTORY_SEPARATOR;
		}

		return true;
*/
	} /* end function */

	static function removeDir($path) { /* begin function */
		$handle = @opendir($path);
		while ($entry = @readdir($handle) )
		{
			if ($entry == ".." || $entry == ".")
				continue;
			else if (is_dir($path . $entry))
			{
				if (!self::removeDir($path . $entry . "/"))
					return false;
			}
			else
			{
				if (!@unlink($path . $entry))
					return false;
			}
		}
		@closedir($handle);
		return @rmdir($path);
	} /* end function */

	static function copyDir($sourcePath, $targetPath) { /* begin function */
		if (mkdir($targetPath, 0777)) {
			$handle = @opendir($sourcePath);
			while ($entry = @readdir($handle) ) {
				if ($entry == ".." || $entry == ".")
					continue;
				else if (is_dir($sourcePath . $entry)) {
					if (!self::copyDir($sourcePath . $entry . "/", $targetPath . $entry . "/"))
						return false;
				} else {
					if (!@copy($sourcePath . $entry, $targetPath . $entry))
						return false;
				}
			}
			@closedir($handle);
		}
		else
			return false;

		return true;
	} /* end function */

	static function moveDir($sourcePath, $targetPath) { /* begin function */
		if (!copyDir($sourcePath, $targetPath))
			return false;
		return removeDir($sourcePath);
	} /* end function */

	// code by Kioob (php.net manual)
	static function gzcompressfile($source,$level=false) { /* begin function */
		$dest=$source.'.gz';
		$mode='wb'.$level;
		$error=false;
		if($fp_out=@gzopen($dest,$mode)) {
			if($fp_in=@fopen($source,'rb')) {
				while(!feof($fp_in))
					@gzwrite($fp_out,fread($fp_in,1024*512));
				@fclose($fp_in);
			}
			else $error=true;
			@gzclose($fp_out);
		}
		else $error=true;

		if($error) return false;
		else return $dest;
	} /* end function */
}
?>

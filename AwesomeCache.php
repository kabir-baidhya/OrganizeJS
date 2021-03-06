<?php 
/**
 * @author Kabir <kabeer182010@gmail.com>
 * @version 0.20
 */

namespace OrganizeJS;

class CacheData
{

	private static $config = array(
		'directory' 	=>  'cache/',
		'cacheExpiry'	=> 86400,//24 hour
		'serialize'		=> true,
	);

	private $key = null;
	private $file = null;

	public function __construct($key)
	{
		$this->key = $key;
		$directory = static::$config['directory'];
		$this->file = $directory.$this->key;
		
		if (!file_exists($directory) && !is_dir($directory)) 
		{
		    mkdir($directory);
		} 
	}

	public function cachedData()
	{
		if(!$this->isCached()) return false;

		$contents = file_get_contents($this->file);

		$serializationEnabled = static::$config['serialize'];

		$data = $serializationEnabled ? unserialize($contents) : $contents;
		
		return $data;
	}

	public function putInCache($data)
	{
		if(!$data) return false;

		$serializationEnabled = static::$config['serialize'];

		$data = $serializationEnabled ? serialize($data) : $data;

		file_put_contents($this->file, $data);
	}
	
	public function isCached()
	{
		return file_exists($this->file);
	}

	public function isUsable()
	{
		return ( $this->duration() < static::$config['cacheExpiry'] );
	}

	public function duration()
	{
		if( !$this->isCached() ) return 0 ;

		$last_modified_time = filemtime($this->file);
		$duration = time() - $last_modified_time;
		return $duration;
	}

	public function isCachedAndUsable()
	{
		return ( $this->isCached() and $this->isUsable() );
	}

	public static function config($config)
	{
		static::$config = $config + static::$config;
	}

	public static function clearAll()
	{
		$dir = static::$config['directory'];

		$dh = opendir($dir);
		while($file = readdir($dh))
		{
		    if(!is_dir($file))
		    {
		    	@unlink($dir.$file);
		    }
		}
		closedir($dh);
	}

	public static function clear($key)
	{
		$dir = static::$config['directory'];

		if( $this->isCached() ) @unlink($dir.$file);
	}

}
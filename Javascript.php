<?php 
/**
 * @author Kabir <kabeer182010@gmail.com>
 * @version 0.10
 */
namespace OrganizeJS;

#region [Require Dependencies]
/*require_once 'AwesomeCache.php';
require_once 'JShrink-1.0.0\Minifier.php';			# https://github.com/tedious/JShrink/*/
#end region

use JShrink\Minifier;

class Javascript
{
	private static $config = array (
		'useStrict'=> false,
		'basePath' 	=>  'scripts/',
		'serverUrl'	=> '',
		'cache'		=> true,
		'cacheDir'	=> '_organize-js-cache/',
		'parameter'	=> 'organize-js-serve',
		'minify'	=> false,
	);

	private $namespace = null;
	private $scripts = array();
	private $output = null;
	private $variables = array();
	public $version = '1.0';

	private function __construct($namespace, array $scripts, $version)
	{
		$this->namespace = $namespace;
		$this->scripts = $scripts;
		$this->version = $version;
	}

	public function vars(array $variables)
	{
		$this->variables = $variables;
	}

	public function add($js)
	{
		if(is_array($js))
		{
			$this->scripts = array_merge($this->scripts, $js);
		}
		else if(is_string($js) and !in_array($js, $this->scripts))
		{
			$this->scripts[] = $js;
		}
		return $this;
	}

	private function merge()
	{
		$merged = '';
		foreach($this->scripts as $singleJS)
		{
			$path = static::$config['basePath'].$singleJS.'.js';
			if(file_exists($path))
			{
				ob_start();
				include_once($path);
				$merged .= "\n".ob_get_clean();
			} else throw new \Exception("OrganizeJS: {$path} not found");
		}

		$this->output = $merged;
	}

	public function output()
	{
		$javascript = '/** '."\n"
			. ' * '.$this->namespace."\n"
			. ' * organized by OrganizeJS'."\n"
			. ' */'."\n";
		//variables
		if(static::$config['useStrict'] === true) {
			$javascript .= "'use strict';\n";
		}

		$javascript .= '(function() { '."\n";
		$javascript .= 'var $vars = '. json_encode( (object) $this->variables ).";\n";
		$javascript .= $this->output."\n";
		$javascript .= '}) ();';
		return $javascript;
	}

	public function outputMinified()
	{
		return Minifier::minify($this->output());
	}

	public function build()
	{
		$this->merge();
		//
		$js = new CacheData($this->namespace);
		$cacheEnabled = static::$config['cache'];

		if( !$cacheEnabled or !$js->isCachedAndUsable() )
		{
			$content = static::$config['minify'] ? $this->outputMinified() : $this->output();
			$js->putInCache($content);
		}

		$url = static::$config['serverUrl'].'?'.http_build_query(array(
			static::$config['parameter'] =>  $this->namespace,
			'ver'			=> $this->version
		));
			
		return $url;
	}


	/* Static Methods */
	public static function init($config)
	{
		static::$config['serverUrl'] = $_SERVER['REQUEST_URI'];//default
		static::$config = $config + static::$config;
		
		# Initialize Cache
		CacheData::config(array(
			'directory' 	=>  static::$config['basePath'].static::$config['cacheDir'],
			'cacheExpiry'	=> 30 * 86400,// 30 days
			'serialize'		=> false,
		));
	}

	public static function organize($namespace, array $scripts = array(), $version = '1.0')
	{
		return new static($namespace, $scripts, $version);
	}

	public static function serve()
	{
		$parameter = static::$config['parameter'];
		if( $namespace = @$_GET[$parameter] )
		{
			$js = new CacheData($namespace);
			if($js->isCachedAndUsable() )
			{
				header('Content-Type: text/javascript');
				echo $js->cachedData();
			}
			else
			{
				header('HTTP/1.0 404 Not Found');
				echo "<h1>404 Not Found</h1>";
			}
			die();	
		}
	}
}


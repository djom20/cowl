<?php

include('csscompiler.php');

/*
	Class:
		<CSS>
	
	The goal of this plugin is to minimize the amount of needed requests for CSS-files. The goal is to keep the number of requests down to 2.
	
	This plugin uses the <CSSCompiler> to compile CSS files.
*/

class CSS extends Plugin
{
	// Property: <CSS::$cache>
	// The path to the cache, used in <Cache>-objects. Will always hold the value of <paths.css.cache>
	private $cache;
	
	// Property: <CSS::$url_dir>
	// The directory for which requests are pointed to. Will always hold the value of <paths.urls.css>
	private $url_dir;
	
	// Property: <CSS::$force_update>
	// A boolean value set to true if CSS files should be updated on every request, nifty for development enivroments. Holds the value of <plugins.css.force_update>.
	// However, if set to false, the compiled CSS file will still be updated if the source CSS is updated, but if any dependency is updated, it will not get updated.
	private $force_update;
	
	/*
		Constructor:
			<CSS::__construct>
		
		Initialize everything. Will also call <CSS::loadSiteCSS>.
	*/
	
	public function __construct()
	{
		list($dir, $url_dir, $cache, $force_update)
			= Current::$config->gets('paths.top', 'paths.urls.css', 'plugins.css.cache', 'plugins.css.force_update');
		
		CSSCompiler::setDir($dir);
		
		$this->url_dir = COWL_BASE . $url_dir;
		$this->cache = $cache;
		$this->force_update = $force_update;
		
		$this->loadSiteCSS();
	}
	
	/*
		Method:
			<CSS::loadSiteCSS>
		
		Add the site-wide css file to the request. Called /css/site.css
	*/
	
	public function loadSiteCSS()
	{
		$filename = Current::$config->get('plugins.css.base_css');
		
		// Append the URL for the site-specific CSS-file to the <Request> registry object.
		Current::$request->setInfo('css[]', $this->url_dir . 'site.css');
	}
	
	
	/*
		Method:
			<CSS::preStaticServer>
		
		This hook will be called when a CSS file has been requested
		
		Parameters:
			$args - The $argv array of the request.
	*/
	
	public function preStaticServe(StaticServer $server)
	{
		// If the type isn't css we don't touch it
		if ( $server->getType() != 'css' )
			return;
		
		$path = $server->getPath();
		$cache_path = $this->cache . '.' . preg_replace('#\W#', '', $path);
		
		// Compile and cache CSS file.
		$cache = new FileCache($cache_path, $path);
		$cache->setExtension('css');
		
		if ( $cache->isOutDated() || $this->force_update )
		{
			$contents = file_get_contents($path);
			
			$compiler = new CSSCompiler($contents);
			$updated = $compiler->compile();
			
			$cache->update($updated);
		}
		
		// Change the path to be the cached file instead
		$server->setPath($cache->getFile());
	}
}

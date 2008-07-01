<?php
/*
	Tumblr PHP API Class by Saymonz (saymonz.net)
	Published under Creative Commons by-sa license.
	Clearbricks by Olivier Meunier (clearbricks.org), all rights reserved.
*/

class tumblrCache extends tumblr {
	protected $cacheDir = null;
	protected $cacheTime = null;
	
	public function __construct($tumblrId,$cacheDir = null,$cacheTime = 3600) {
		if (isset($cacheDir) && is_dir($cacheDir) && isset($cacheTime) && is_int($cacheTime)) {
			$this->useCache = true;
			$this->cacheDir = $cacheDir;
			$this->cacheTime = $cacheTime;
		}
		parent::__construct($tumblrId);
	}
	
	protected function __cacheRead($params = array()) {
		$cacheFilePath = $this->__cacheFilePath($params);
		if (file_exists($cacheFilePath)) {
			if (time() - filemtime($cacheFilePath) < $this->cacheTime) {
				$this->xml = file_get_contents($cacheFilePath);
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	protected function __cacheWrite($params = array()) {
		file_put_contents($this->__cacheFilePath($params),$this->xml);
		return true;
	}
	
	protected function __cacheFilePath($params = array()) {
		if (!is_dir($this->cacheDir.'/'.$this->tumblrId)) {
			mkdir($this->cacheDir.'/'.$this->tumblrId);
		}
		$cacheFilePath = $this->tumblrId;
		$cacheFilePath .= '/';
		foreach ($params as $k => $v) {
			$cacheFilePath .= $k.'_'.$v.'.';
		}
		if (substr($cacheFilePath,-1) != '.') {
			$cacheFilePath .= '.';
		}
		$cacheFilePath .= 'xml';

		$cacheFilePath = $this->cacheDir.'/'.$cacheFilePath;
		return $cacheFilePath;
	}
}
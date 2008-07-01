<?php
/*
	Tumblr PHP API Class by Saymonz (saymonz.net)
	Published under Creative Commons by-sa license.
	Clearbricks by Olivier Meunier (clearbricks.org), all rights reserved.
*/

class tumblr {
	protected $tumblrId = '';
	protected $httpObj = null;
	protected $xml = '';
	protected $xmlArr = array();
	protected $tumblrPostTypes = array('regular','quote','photo','link','conversation','video','audio');
	protected $useCache = false;

	
	/* Public functions
	*********************/
	public function __construct($tumblrId = null) {
		$this->tumblrId = $tumblrId;
		$httpObj = &$this->httpObj;
		$httpObj = new netHttp($this->tumblrId.'.tumblr.com');
		$httpObj->setUserAgent('PHP Tumblr API Class');
		return true;
	}
	
	public function getPosts($start = 0,$num = 20,$type = null) {	
		$params = array();
		if ($num != 20 && $num != null) { $params['num'] = $num; }
		if ($type != null && in_array($type,$this->tumblrPostTypes)) { $params['type'] = $type; }
		if ($start != 0 && $start != null) { $params['start'] = $start; }
		
		$this->__apiRead($params);
		return true;
	}
	
	public function getPost($id = null) {
		$params = array();
		if ($id != null) { $params['id'] = $id; } else { return false; }
		
		$this->__apiRead($params);
		return true;
	}
	
	public function getAllPosts($type = null) {
		$params = array();
		if ($type != null && in_array($type,$this->tumblrPostTypes)) { $params['type'] = $type; }
		$params['num'] = 1;
		
		$this->__apiRead($params);
		$tempXmlObj = new SimpleXMLElement($this->xml);
		$total = (string) $tempXmlObj->posts->attributes()->total;
		unset($tempXmlObj);
		
		for ($x = 0; $total > $x; $x = $x + 50) {
			$this->getPosts($x,50,$type);
		}
		return true;
	}
	
	public function getXml() {
		return $this->xml;
	}
	
	public function getArray() {
		return $this->xmlArr;
	}
	
	public function sortArray($chrono = false) {
		$xmlArr = &$this->xmlArr;
		if ($chrono) {
			ksort($xmlArr['posts'],SORT_NUMERIC);
		} else {
			krsort($xmlArr['posts'],SORT_NUMERIC);
		}
		return true;
	}
	
	public function flush($tumblrId = null) {
		$_tumblrId = ($tumblrId == null) ? $this->tumblrId : $tumblrId;
		unset($this->tumblrId,$this->httpObj,$this->xml,$this->xmlArr);
		$this->__construct($_tumblrId);
		return true;
	}
	
	/* Protected functions
	*********************/
	protected function __apiRead($params) {
		$httpObj = &$this->httpObj;
		
		if (!$this->useCache || !$this->__cacheRead($params)) {
			$httpObj->get('/api/read',$params);
			if ($httpObj->getStatus() != 200) { return 'Fatal error : HTTP '.$httpObj->getStatus(); }
			$this->xml = $httpObj->getContent();
		}
		
		if ($this->useCache) {
			$this->__cacheWrite($params);
		}

		$this->__xml2array();
		return true;
	}
	
	protected function __xml2array() {
		$xmlArr = array();
		$xmlObj = new SimpleXMLElement($this->xml);
		$xmlArr['tumblelog']['title'] = (string) $xmlObj->tumblelog->attributes()->title;
		$xmlArr['tumblelog']['description'] = (string) $xmlObj->tumblelog;
		if ((string) $xmlObj->tumblelog->attributes()->cname == null) {
			$xmlArr['tumblelog']['url'] =  (string) 'http://'.$xmlObj->tumblelog->attributes()->name.'.tumblr.com/';
		} else {
			$xmlArr['tumblelog']['url'] =  (string) 'http://'.$xmlObj->tumblelog->attributes()->cname.'/';
		}
		$xmlArr['tumblelog']['timezone'] = (string) $xmlObj->tumblelog->attributes()->timezone;
		
		foreach ($xmlObj->posts->children() as $k => $v) {
			$pid = (integer) $v->attributes()->{'unix-timestamp'}.'|'.$v->attributes()->id;
			$xmlArr['posts'][$pid]['id'] = (integer) $v->attributes()->id;
			$xmlArr['posts'][$pid]['url'] = (string) $v->attributes()->url;
			$xmlArr['posts'][$pid]['type'] = (string) $v->attributes()->type;
			$xmlArr['posts'][$pid]['timestamp'] = (integer) $v->attributes()->{'unix-timestamp'};
			$xmlArr['posts'][$pid]['bookmarklet'] = (boolean) $v->attributes()->bookmarklet;
			$xmlArr['posts'][$pid]['mobile'] = (boolean) $v->attributes()->mobile;
		
			switch ($v->attributes()->type) {
				case 'regular' :
					$xmlArr['posts'][$pid]['content']['title'] = (string) $v->{'regular-title'};
					$xmlArr['posts'][$pid]['content']['body'] = (string) $v->{'regular-body'};
					break;
				case 'quote' :
					$xmlArr['posts'][$pid]['content']['quote'] = (string) $v->{'quote-text'};
					$xmlArr['posts'][$pid]['content']['source'] = (string) $v->{'quote-source'};
					break;
				case 'photo' :
					$xmlArr['posts'][$pid]['content']['caption'] = (string) $v->{'photo-caption'};
					$xmlArr['posts'][$pid]['content']['url-500'] = (string) $v->{'photo-url'}[0];
					$xmlArr['posts'][$pid]['content']['url-400'] = (string) $v->{'photo-url'}[1];
					$xmlArr['posts'][$pid]['content']['url-250'] = (string) $v->{'photo-url'}[2];
					$xmlArr['posts'][$pid]['content']['url-100'] = (string) $v->{'photo-url'}[3];
					$xmlArr['posts'][$pid]['content']['url-75sq'] = (string) $v->{'photo-url'}[4];
					break;
				case 'link' :
					$xmlArr['posts'][$pid]['content']['text'] = (string) $v->{'link-text'};
					$xmlArr['posts'][$pid]['content']['url'] = (string) $v->{'link-url'};
					$xmlArr['posts'][$pid]['content']['description'] = (string) $v->{'link-description'};
					break;
				case 'conversation' :
					$xmlArr['posts'][$pid]['content']['text'] = (string) $v->{'conversation-text'};
					$x = 0;
					foreach ($v->{'conversation-line'} as $k) {
						$xmlArr['posts'][$pid]['content']['lines'][$x]['name'] = (string) $k->attributes()->name;
						$xmlArr['posts'][$pid]['content']['lines'][$x]['content'] = (string) $k;
						$x++;
					}
					break;
				case 'video' :
					$xmlArr['posts'][$pid]['content']['caption'] = (string) $v->{'video-caption'};
					$xmlArr['posts'][$pid]['content']['source'] = (string) $v->{'video-source'};
					$xmlArr['posts'][$pid]['content']['player'] = (string) $v->{'video-player'};
					break;
				case 'audio' :
					$xmlArr['posts'][$pid]['content']['caption'] = (string) $v->{'audio-caption'};
					$xmlArr['posts'][$pid]['content']['player'] = (string) $v->{'audio-player'};
					$xmlArr['posts'][$pid]['content']['plays'] = (string) $v->{'audio-plays'};
					break;
			}
		}
		if (!isset($this->xmlArr['posts'])) {
			$xmlArr = $this->__cleanArr($xmlArr);
			$this->xmlArr = $xmlArr;
		} else {
			$xmlArr['posts'] = $this->__cleanArr($xmlArr['posts']);
			$this->xmlArr['posts'] = $this->xmlArr['posts'] + $xmlArr['posts'];
		}
		return true;
	}
	
	protected function __cleanArr($arr = null) {
		if (!$arr) { $arr = $this->xmlArr; }
		foreach ($arr as $k => $v) {
			if (is_string($arr[$k])) {
				$arr[$k] = utf8HtmlEntityDecode::htmlentities2utf8($arr[$k],ENT_QUOTES,'ISO-8859-1');
				$arr[$k] = trim($arr[$k]);
			}
			if (is_array($arr[$k])) { $arr[$k] = $this->__cleanArr($arr[$k]); }
		}
		return $arr;
	}
}
?>
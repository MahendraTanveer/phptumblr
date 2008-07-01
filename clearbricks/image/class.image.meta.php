<?php
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Clearbricks.
# Copyright (c) 2006 Olivier Meunier and contributors. All rights
# reserved.
#
# Clearbricks is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
# 
# Clearbricks is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Clearbricks; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
# ***** END LICENSE BLOCK *****
#
# Contributors:
# - Mathieu Lecarme

class imageMeta
{
	protected $meta = array();
	protected $xmp = array();
	protected $iptc = array();
	protected $exif = array();
	
	public static function readMeta($f)
	{
		$o = new self;
		$o->loadFile($f);
		return $o->getMeta();
	}
	
	public function getMeta()
	{
		foreach ($this->properties as $k => $v)
		{
			if (!empty($this->xmp[$k])) {
				$this->properties[$k] = $this->xmp[$k];
			} elseif (!empty($this->iptc[$k])) {
				$this->properties[$k] = $this->iptc[$k];
			} elseif (!empty($this->exif[$k])) {
				$this->properties[$k] = $this->exif[$k];
			}
		}
		
		# Fix date format
		$this->properties['DateTimeOriginal'] = preg_replace(
			'/^(\d{4}):(\d{2}):(\d{2})/','$1-$2-$3',
			$this->properties['DateTimeOriginal']
		);
		
		return $this->properties;
	}
	
	public function loadFile($f)
	{
		if (!is_file($f) || !is_readable($f)) {
			throw new Exception('Unable to read file');
		}
		
		$this->readXMP($f);
		$this->readIPTC($f);
		$this->readExif($f);
	}
	
	protected function readXMP($f)
	{
		if (($fp = @fopen($f,'rb')) === false) {
			throw new Exception('Unable to open image file');
		}
		
		$inside = false;
		$done = false;
		$xmp = null;
		
		while (!feof($fp))
		{
			$buffer = fgets($fp,4096);
			
			$xmp_start = strpos($buffer,'<x:xmpmeta');
			
			if ($xmp_start !== false) {
				$buffer = substr($buffer,$xmp_start);
				$inside = true;
			}
			
			if ($inside)
			{
				$xmp_end = strpos($buffer,'</x:xmpmeta>');
				if ($xmp_end !== false) {
					$buffer = substr($buffer,$xmp_end,12);
					$inside = false;
					$done = true;
				}
				
				$xmp .= $buffer;
			}
			
			if ($done) {
				break;
			}
		}
		fclose($fp);
		
		if (!$xmp) {
			return;
		}
		
		foreach ($this->xmp_reg as $code => $patterns)
		{
			foreach ($patterns as $p)
			{
				if (preg_match($p,$xmp,$m)) {
					$this->xmp[$code] = $m[1];
					break;
				}
			}
		}
		
		if (preg_match('%<dc:subject>\s*<rdf:Bag>(.+?)</rdf:Bag%msu',$xmp,$m)
		&& preg_match_all('%<rdf:li>(.+?)</rdf:li>%msu',$m[1],$m))
		{
			$this->xmp['Keywords'] = implode(',',$m[1]);
		}
		
		foreach ($this->xmp as $k => $v) {
			$this->xmp[$k] = html::decodeEntities(text::toUTF8($v));
		}
	}
	
	protected function readIPTC($file)
	{
		if (!function_exists('iptcparse')) {
			return;
		}
		
		$imageinfo = null;
		@getimagesize($file,$imageinfo);
		
		if (!is_array($imageinfo) || !isset($imageinfo['APP13'])) {
			return;
		}
		
		$iptc = @iptcparse($imageinfo['APP13']);
		
		if (!is_array($iptc)) {
			return;
		}
		
		foreach ($this->iptc_ref as $k => $v)
		{
			if (isset($iptc[$k]) && isset($this->iptc_to_property[$v])) {
				$this->iptc[$this->iptc_to_property[$v]] = text::toUTF8(trim(implode(',',$iptc[$k])));
			}
		}
	}
	
	protected function readEXIF($f)
	{
		if (!function_exists('exif_read_data')) {
			return;
		}
		
		$d = @exif_read_data($f,'ANY_TAG');
		
		if (!is_array($d)) {
			return;
		}
		
		foreach ($this->exif_to_property as $k => $v)
		{
			if (isset($d[$k])) {
				$this->exif[$v] = text::toUTF8($d[$k]);
			}
		}
	}
	
	/* Properties
	------------------------------------------------------- */
	protected $properties = array(
		'Title' => null,
		'Description' => null,
		'Creator' => null,
		'Rights' => null,
		'Make' => null,
		'Model' => null,
		'Exposure' => null,
		'FNumber' => null,
		'MaxApertureValue' => null,
		'ExposureProgram' => null,
		'ISOSpeedRatings' => null,
		'DateTimeOriginal' => null,
		'ExposureBiasValue' => null,
		'MeteringMode' => null,
		'FocalLength' => null,
		'Lens' => null,
		'CountryCode' => null,
		'Country' => null,
		'State' => null,
		'City' => null,
		'Keywords' => null
	);
	
	# XMP
	protected $xmp_reg = array(
		'Title' => array(
			'%<dc:title>\s*<rdf:Alt>\s*<rdf:li.*?>(.+?)</rdf:li>%msu'
		),
		'Description' => array(
			'%<dc:description>\s*<rdf:Alt>\s*<rdf:li.*?>(.+?)</rdf:li>%msu'
		),
		'Creator' => array(
			'%<dc:creator>\s*<rdf:Seq>\s*<rdf:li>(.+?)</rdf:li>%msu'
		),
		'Rights' => array(
			'%<dc:rights>\s*<rdf:Alt>\s*<rdf:li.*?>(.+?)</rdf:li>%msu'
		),
		'Make' => array(
			'%<tiff:Make>(.+?)</tiff:Make>%msu',
			'%tiff:Make="(.+?)"%msu'
		),
		'Model' => array(
			'%<tiff:Model>(.+?)</tiff:Model>%msu',
			'%tiff:Model="(.+?)"%msu'
		),
		'Exposure' => array(
			'%<exif:ExposureTime>(.+?)</exif:ExposureTime>%msu',
			'%exif:ExposureTime="(.+?)"%msu'
		),
		'FNumber' => array(
			'%<exif:FNumber>(.+?)</exif:FNumber>%msu',
			'%exif:FNumber="(.+?)"%msu'
		),
		'MaxApertureValue' => array(
			'%<exif:MaxApertureValue>(.+?)</exif:MaxApertureValue>%msu',
			'%exif:MaxApertureValue="(.+?)"%msu'
		),
		'ExposureProgram' => array(
			'%<exif:ExposureProgram>(.+?)</exif:ExposureProgram>%msu',
			'%exif:ExposureProgram="(.+?)"%msu'
		),
		'ISOSpeedRatings' => array(
			'%<exif:ISOSpeedRatings>\s*<rdf:Seq>\s*<rdf:li>(.+?)</rdf:li>%msu'
		),
		'DateTimeOriginal' => array(
			'%<exif:DateTimeOriginal>(.+?)</exif:DateTimeOriginal>%msu',
			'%exif:DateTimeOriginal="(.+?)"%msu'
		),
		'ExposureBiasValue' => array(
			'%<exif:ExposureBiasValue>(.+?)</exif:ExposureBiasValue>%msu',
			'%exif:ExposureBiasValue="(.+?)"%msu'
		),
		'MeteringMode' => array(
			'%<exif:MeteringMode>(.+?)</exif:MeteringMode>%msu',
			'%exif:MeteringMode="(.+?)"%msu'
		),
		'FocalLength' => array(
			'%<exif:FocalLength>(.+?)</exif:FocalLength>%msu',
			'%exif:FocalLength="(.+?)"%msu'
		),
		'Lens' => array(
			'%<aux:Lens>(.+?)</aux:Lens>%msu',
			'%aux:Lens="(.+?)"%msu'
		),
		'CountryCode' => array(
			'%<Iptc4xmpCore:CountryCode>(.+?)</Iptc4xmpCore:CountryCode>%msu',
			'%Iptc4xmpCore:CountryCode="(.+?)"%msu'
		),
		'Country' => array(
			'%<photoshop:Country>(.+?)</photoshop:Country>%msu',
			'%photoshop:Country="(.+?)"%msu'
		),
		'State' => array(
			'%<photoshop:State>(.+?)</photoshop:State>%msu',
			'%photoshop:State="(.+?)"%msu'
		),
		'City' => array(
			'%<photoshop:City>(.+?)</photoshop:City>%msu',
			'%photoshop:City="(.+?)"%msu'
		)
	);
	
	# IPTC
	protected $iptc_ref = array(
		'1#090' => 'Iptc.Envelope.CharacterSet',// Character Set used (32 chars max)
		'2#005' => 'Iptc.ObjectName',           // Title (64 chars max)
		'2#015' => 'Iptc.Category',             // (3 chars max)
		'2#020' => 'Iptc.Supplementals',        // Supplementals categories (32 chars max)
		'2#025' => 'Iptc.Keywords',             // (64 chars max)
		'2#040' => 'Iptc.SpecialsInstructions', // (256 chars max)
		'2#055' => 'Iptc.DateCreated',          // YYYYMMDD (8 num chars max)
		'2#060' => 'Iptc.TimeCreated',          // HHMMSS+/-HHMM (11 chars max)
		'2#062' => 'Iptc.DigitalCreationDate',  // YYYYMMDD (8 num chars max)
		'2#063' => 'Iptc.DigitalCreationTime',  // HHMMSS+/-HHMM (11 chars max)
		'2#080' => 'Iptc.ByLine',               // Author (32 chars max)
		'2#085' => 'Iptc.ByLineTitle',          // Author position (32 chars max)
		'2#090' => 'Iptc.City',                 // (32 chars max)
		'2#092' => 'Iptc.Sublocation',          // (32 chars max)
		'2#095' => 'Iptc.ProvinceState',        // (32 chars max)
		'2#100' => 'Iptc.CountryCode',          // (32 alpha chars max)
		'2#101' => 'Iptc.CountryName',          // (64 chars max)
		'2#105' => 'Iptc.Headline',             // (256 chars max)
		'2#110' => 'Iptc.Credits',              // (32 chars max)
		'2#115' => 'Iptc.Source',               // (32 chars max)
		'2#116' => 'Iptc.Copyright',            // Copyright Notice (128 chars max)
		'2#118' => 'Iptc.Contact',              // (128 chars max)
		'2#120' => 'Iptc.Caption',              // Caption/Abstract (2000 chars max)
		'2#122' => 'Iptc.CaptionWriter'         // Caption Writer/Editor (32 chars max)
	);
	
	protected $iptc_to_property = array(
		'Iptc.ObjectName' => 'Title',
		'Iptc.Caption' => 'Description',
		'Iptc.ByLine' => 'Creator',
		'Iptc.Copyright' =>'Rights',
		'Iptc.CountryCode' => 'CountryCode',
		'Iptc.CountryName' => 'Country',
		'Iptc.ProvinceState' => 'State',
		'Iptc.City' => 'City',
		'Iptc.Keywords' => 'Keywords'
	);
	
	# EXIF
	protected $exif_to_property = array(
		//'' => 'Title',
		'ImageDescription' => 'Description',
		'Artist' => 'Creator',
		'Copyright' => 'Rights',
		'Make' => 'Make',
		'Model' => 'Model',
		'ExposureTime' => 'Exposure',
		'FNumber' => 'FNumber',
		'MaxApertureValue' => 'MaxApertureValue',
		'ExposureProgram' => 'ExposureProgram',
		'ISOSpeedRatings' => 'ISOSpeedRatings',
		'DateTimeOriginal' => 'DateTimeOriginal',
		'ExposureBiasValue' => 'ExposureBiasValue',
		'MeteringMode' => 'MeteringMode',
		'FocalLength' => 'FocalLength'
		//'' => 'Lens',
		//'' => 'CountryCode',
		//'' => 'Country',
		//'' => 'State',
		//'' => 'City',
		//'' => 'Keywords'
	);
}
?>
<?php

namespace Minimalism\MapCluster;

class LngLat
{
	public $lng;
	public $lat;

	// private $cacheKey = null;
	public function __construct($lng, $lat)
    {
		$this->lng = self::getRange($lng, -180, 180);
		$this->lat = self::getRange($lat, BD_MAP_LAT_MIN, BD_MAP_LAT_MAX);
	}

	public static function getRange($val, $limitMin, $limitMax)
    {
		if ($limitMin) {
			$val = max($val, $limitMin);
		}
		if ($limitMax) {
			$val = min($val, $limitMax);
		}
		return $val;
	}

	// public function getCacheKey($algo = 'adler32'){
	// 	return $this->cacheKey !== null ?: hash($algo, $this->lng . $this->lat);
	// }

	public function getCacheKey($algo = 'adler32')
    {
		return hash($algo, $this->lng . $this->lat);
	}

	public function toArray()
    {
		return [$this->lng, $this->lat];
	}
}
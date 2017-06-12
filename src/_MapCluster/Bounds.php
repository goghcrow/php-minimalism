<?php

namespace Minimalism\MapCluster;

class Bounds
{
	private $southWest;
	private $northEast;

	public function __construct(LngLat $southWest, LngLat $northEast)
    {
		$this->southWest = $southWest;
		$this->northEast = $northEast;
	}

	public function __toString()
    {
		$toPrint = <<<STR
southwest [{$this->southWest->lng}, {$this->southWest->lat}]
northEast [{$this->northEast->lng}, {$this->northEast->lat}]

STR;
		return $toPrint;
	}

	public function getSouthWest()
    {
		return $this->southWest;
	}

	public function getNorthEast()
    {
		return $this->northEast;
	}

	public function contains(LngLat $lnglat)
    {
		$minLng = $this->southWest->lng;
		$maxLng = $this->northEast->lng;
		$minLat = $this->southWest->lat;
		$maxLat = $this->northEast->lat;
		$lng = $lnglat->lng;
		$lat = $lnglat->lat;

		$scopeLng = abs($minLng-$maxLng) % 180;
		$distoMinLng = abs($lng-$minLng) % 180;
		$distoMaxLng = abs($lng-$maxLng) % 180;

		$scopeLat = abs($minLat-$maxLat);
		$distoMinLat = abs($lat-$minLat);
		$distoMaxLat = abs($lat-$maxLat);

		// !!!取余运算 且是float 不能用=== 需要用 不等运算
		// 或者 abs($scopeLng - ($distoMinLng + $distoMaxLng)) < 阈值
		return ($scopeLng >= ($distoMinLng + $distoMaxLng) 
			&&
			$scopeLat >= ($distoMinLat + $distoMaxLat));
	}

	public function extendMeters($meters)
    {
		if (!is_numeric($meters)) {
			trigger_error("extended meters must be number.");
		}

		if ($meters > 0) {
			$metersPerLng = 85276;
			$metersPerLat = 110940;
			$lngs = $meters / $metersPerLng;
			$lats = $meters / $metersPerLat;

			$this->southWest->lng -= $lngs;
			if ($this->southWest->lng > 180){
				$this->southWest->lng -= 360;
			}
			if ($this->southWest->lng < -180){
				$this->southWest->lng += 360;
			}

			$this->southWest->lat -= $lats;
			if ($this->southWest->lat > BD_MAP_LAT_MAX){
				$this->southWest->lat = BD_MAP_LAT_MAX;
			}
			if ($this->southWest->lat < BD_MAP_LAT_MIN){
				$this->southWest->lat = BD_MAP_LAT_MIN;
			}

			$this->northEast->lng += $lngs;
			if ($this->northEast->lng > 180){
				$this->northEast->lng -= 360;
			}
			if ($this->northEast->lng < -180){
				$this->northEast->lng += 360;
			}
			
			$this->northEast->lat += $lats;
			if ($this->northEast->lat > BD_MAP_LAT_MAX) {
				$this->northEast->lat = BD_MAP_LAT_MAX;
			}
			if ($this->northEast->lat < BD_MAP_LAT_MIN){
				$this->northEast->lat = BD_MAP_LAT_MIN;
			}
		}
	}
}
<?php

namespace Minimalism\MapCluster;

defined('BD_MAP_LAT_MIN') or define('BD_MAP_LAT_MIN', -74);
defined('BD_MAP_LAT_MAX') or define('BD_MAP_LAT_MAX', 74);


class LngLatCluster
{

	public $lnglatToClusterUniqID = [];
	private $cacheHit = 0;

    private $lnglats = [];

    private $clusters = [];

    private $mapBounds = null;

    private $opt = [
        'gridMeters' => 1000,
        'isAverageCenter' => false,
        'minClusterMeters' => 180,
        'maxLngLatSize' => 500,
    ];


    private function addCacheCluster(LngLat $lnglat)
    {
		$cacheKey = $lnglat->getCacheKey();
		
		if (isset($this->lnglatToClusterUniqID[$cacheKey])) {
			$this->cacheHit += 1;
			$clusterUniqID = $this->lnglatToClusterUniqID[$cacheKey];
			$this->clusters[$clusterUniqID]->addLngLat($lnglat);
			return true;
		}

		return $cacheKey;
	}

	// 获取缓存命中率
	public function getCacheHit()
    {
		return sprintf('%.2f',($this->cacheHit / count($this->lnglats)) * 100) . '%';
	}

	public function __construct(array $lnglats, Bounds $mapBounds, array $option)
    {
		$this->lnglats = $lnglats;
		$this->mapBounds = $mapBounds;
		$this->opt = $option + $this->opt;

		if (count($lnglats) <= $this->opt['maxLngLatSize'] &&
			$this->opt['gridMeters'] <= $this->opt['minClusterMeters']) {
			$this->noCreateCluster();
		}else{
			$this->createCluster();
		}
	}

	private function noCreateCluster()
    {
		foreach ($this->lnglats as $lnglat){
			$cluster = new Cluster($this);
			$cluster->addLngLat($lnglat);
			$this->clusters[] = $cluster;
		}
	}

	private function createCluster()
    {

		// 扩展地图边界
		$bounds = $this->getExtendedBounds($this->mapBounds);

		foreach ($this->lnglats as $lnglat) {
			
			// 判断是否在扩展后地图边界内
			if ($bounds->contains($lnglat)) {
				// 尝试通过缓存加入
				$lnglatCacheKey = $this->addCacheCluster($lnglat);
				// 缓存尝试失败，返回LngLat的key，通过正常流程加入，最后
				// key = lnglat val = clusterUniqID
				if ($lnglatCacheKey !== true) {
					$clusterUniqID = $this->addToClosestCluster($lnglat);
					// echo $clusterUniqID.PHP_EOL;
					$this->lnglatToClusterUniqID[$lnglatCacheKey] = $clusterUniqID;
				}
			}
		}
	}

	public function __get($key)
    {
		if (isset($this->opt[$key])) {
			return $this->opt[$key];
		}
		trigger_error("can`t get undefined option $key");
	}

	public function __set($key ,$val)
    {
		if (isset($this->opt[$key])) {
			$this->opt[$key] = $val;
		}
		trigger_error("can`t set undefined option $key");
	}

	// 按照百度地图支持的世界范围对bounds进行边界处理
	private function cutBoundsInRange(Bounds $bounds)
    {
		$maxX = LngLat::getRange($bounds->getNorthEast()->lng, -180, 180);
		$minX = LngLat::getRange($bounds->getSouthWest()->lng, -180, 180);
		$maxY = LngLat::getRange($bounds->getNorthEast()->lat, BD_MAP_LAT_MIN, BD_MAP_LAT_MAX);
		$minY = LngLat::getRange($bounds->getSouthWest()->lat, BD_MAP_LAT_MIN, BD_MAP_LAT_MAX);
		return new Bounds(new LngLat($minX, $minY), new LngLat($maxX, $maxY));
	}

	// Extends a bounds object by the grid size.
	public function getExtendedBounds(Bounds $bounds)
    {
		// print($bounds);
		$bounds = $this->cutBoundsInRange($bounds);	
		// print($bounds);
		// 获取一个扩展的视图范围，把上下左右都扩大一样的像素值。
		// ！！！对扩展范围内的点进行聚合.....
		$bounds->extendMeters($this->opt['gridMeters']);
		// print($bounds);
		return $bounds;
	}
	
	private function distanceBetweenPoints(LngLat $p1, LngLat $p2)
    {
		if ($p1 == $p2) {
			return 0;
		}

		// baidu
		// $R = 6370996.81;
		// return $R * acos(sin($p1->lat) * sin($p2->lat) + cos($p1->lat) * cos($p2->lat) * cos($p2->lng - $p1->lng));

		// google
		$R = 6371; // 地球半径 km
		$dLat = ($p2->lat - $p1->lat) * M_PI / 180;
		$dLon = ($p2->lng - $p1->lng) * M_PI / 180;
		$a = sin($dLat / 2) * sin($dLat / 2) +
		  cos($p1->lat * M_PI / 180) * cos($p2->lat * M_PI / 180) *
		  sin($dLon / 2) * sin($dLon / 2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
		$d = $R * $c;
		return $d;
	}

	private function addToClosestCluster(LngLat $lnglat)
    {
		$distance = 40000;
		$clusterToAddTo = null;
		$clusterUniqID = null;

		// 寻找最近的cluster
		foreach ($this->clusters as $uniqid => $cluster) {
			$center = $cluster->getCenter();
			if ($center) {
				$d = $this->distanceBetweenPoints($center, $lnglat);
				if ($d < $distance) {
					$distance = $d;
					$clusterToAddTo = $cluster;
					$clusterUniqID = $uniqid;
				}
			}
		}

		// if ($clusterToAddTo !== null) {
		// 	var_dump($clusterToAddTo->isLnaLatInClusterBounds($lnglat));
		// }
		// 看是否找到最近的cluster 且 在最近的cluster的边界内
		if ($clusterToAddTo !== null && $clusterToAddTo->isLnaLatInClusterBounds($lnglat)) {
			$clusterToAddTo->addLngLat($lnglat);
		}else{
			// 没找到最近的cluster就创建一个新的cluster
			$cluster = new Cluster($this);
			$cluster->addLngLat($lnglat);
			
			$clusterUniqID = $cluster->getCacheUUID();
			$this->clusters[$clusterUniqID] = $cluster;
		}

		return $clusterUniqID;
	}

	public function getResult(){
		return array_reduce($this->clusters, function($result, $cluster){
			$result[] = $cluster->getCenterAndCount();
			return $result;
		},[]);
	}
}
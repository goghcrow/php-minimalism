<?php

namespace Minimalism\MapCluster;

class Cluster
{
	private $lnglatCluster;
	private $isAverageCenter;
	private $gridMeters;

    /**
     * @var LngLat[]
     */
	private $lnglats = [];

    /**
     * @var LngLat|null
     */
    private $center = null;

    /**
     * @var LngLat|null
     */
	private $bounds = null;
	private $uuid = null;

	public function __construct(LngLatCluster $lnglatCluster)
    {
		$this->lnglatCluster = $lnglatCluster;
		$this->gridMeters = $lnglatCluster->gridMeters;
		$this->isAverageCenter = $this->lnglatCluster->isAverageCenter;
	}

	public function getCacheUUID()
    {
		return $this->uuid !== null ?: uniqid();
	}

	public function getCenter(){
		return $this->center;
	}

	public function getCenterAndCount()
    {
		return [
			'center' => $this->center,
			'count' => count($this->lnglats),
		];
	}

	public function addLngLat(LngLat $lnglat)
    {
		// Cluster的第一个点作为中心
		// 否则计算平均位置
		if ($this->center === null) {
			$this->center = $lnglat;
			$this->reCaculateBounds();
		} else {
			if ($this->isAverageCenter) {
				$l = count($this->lnglats) + 1;
				$lat = ($this->center->lat * ($l - 1) + $lnglat->lat) / $l;
				$lng = ($this->center->lng * ($l - 1) + $lnglat->lng) / $l;
				$this->center = new LngLat($lng, $lat);
				$this->reCaculateBounds();
			}
		}
		$this->lnglats[] = $lnglat;
	}

	public function reCaculateBounds()
    {
		// 重新计算该cluster边界，从中心向四周扩展grid（Km）
		$bounds = new Bounds($this->center, $this->center);
		$this->bounds = $this->lnglatCluster->getExtendedBounds($bounds);
		// var_dump($this->bounds);exit;
	}

	// 遍历markers 算出cluster边界
	// public function getBounds(){
	// 	$boudns = new Bounds($this->center, $this->center);
	// 	foreach ($this->lnglats as $lnglat) {
	// 		$bounds->extendMeters($this->gridMeters);
	// 	}
	// 	return $bounds;
	// }

	public function isLnaLatInClusterBounds(LngLat $lnglat){
		return $this->bounds->contains($lnglat);
	}
}
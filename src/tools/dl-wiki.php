<?php

// 通过保存pdf方式 按照树形结构下载wiki文档

error_reporting(E_ALL);
ini_set("memory_limit", "12048M");


define("HOST", "http://");
define("HEADER", "Cookie:");
define("ROOT", "/path/to/save");
define("IS_WIN", false);

$pageId = "";
$pageName = "";



treeDownlaod(ROOT . '/' . conv($pageName), $pageId);


// utf8togb2312
function conv($in) {
	return IS_WIN ? iconv("utf-8","gb2312//IGNORE", $in) : $in;
}

function get($url) {
	$ctx = stream_context_create(["http" =>  [
		"method" 	=> "GET",
		"header" 	=> HEADER,
	]]);
	return file_get_contents($url, false, $ctx);
}

function download($filepath, $url) {
	return file_put_contents($filepath, get($url));
}

function downloadPdf($rootDir, $pdfName, $pageSrc) {
	if (preg_match('/<a\s+id="action-export-pdf-link"\s+href="(.*)"\s+rel=/', $pageSrc, $pdfExMatches)) {
		$pdf = $rootDir . "/$pdfName.pdf";
		$dir = dirname($pdf);
		if (!file_exists($dir)) {
			echo "mkdir ==> $dir", PHP_EOL;
			mkdir($dir);
		}
		if (!file_exists($pdf)) {
			echo "download pdf ==> $pdf", PHP_EOL;
			download($pdf, HOST . $pdfExMatches[1]);
		}
	}
}

// TO DEBUG
function downloadAttachment($pageId, $fileName, $dir) {
	$file = "$dir/$fileName";
	if (!file_exists($file)) {
		$fileName = urlencode($fileName);
		$url = HOST . "/download/attachments/$pageId/$fileName";
		echo "download attachment ==> $url", PHP_EOL;
		return file_put_contents($file, get($url));
	}
	return false;
}

function clear($str) {
	return str_replace(['*', "\t", '/'], "_", $str);
}

function treeDownlaod($rootDir, $pageId) {
	$url = HOST . "/plugins/pagetree/naturalchildren.action?hasRoot=true&pageId=$pageId";
	$tree = get($url);
	$pattern = '/<span\s+class="plugin_pagetree_children_span"\s+id="childrenspan.*-">\s+<a\s+href="(\/pages\/viewpage\.action\?pageId=(.*))">(.*)<\/a>\s+<\/span>/';
	preg_match_all($pattern, $tree, $matches);

	if (!$matches || count($matches[0]) === 0) {
		return;
	}

	if (!file_exists($rootDir)) {
		echo "mkdir ==> $rootDir", PHP_EOL;
		mkdir($rootDir);
	}

	foreach($matches[1] as $i => $href) {
		$pdfName = clear(conv($matches[3][$i]));
		$page = get(HOST . $href);
		downloadPdf($rootDir, $pdfName, $page);

		// 递归下载目录
		treeDownlaod($rootDir . "/$pdfName", $matches[2][$i]);
	}
}




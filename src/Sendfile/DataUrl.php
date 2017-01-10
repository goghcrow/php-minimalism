<?php

namespace Minimalism\Sendfile;

use RuntimeException;


// 玩具Demo, 生产环境应该通过Linux sendfile 来实现, 节省内核和用户空间的copy

/**
 * Class SendFile
 * @package Minimalism\SendFile
 *
 * dataUrl方式，适用于小文件下载, 不通过临时文件，无临时文件磁盘io操作
 * 解决发送生成zip文件等必须通过临时文件的方案（php的zip等相关的流只读）
 * 大文件需要手动php进行zip编码~
 * 可同时发送多个下载文件
 */
class DataUrl
{
	protected $files = [];

	public function attach($fileName, $content, $mimeType = "text/plain", array $option = [])
    {
		array_unshift($option, $mimeType);
        $option[] = "base64," . base64_encode($content);
		$this->files[$fileName] = "data://" . implode(';', $option);
	}

    public function attachZip($fileName, $content, $level = 2)
    {
		if(strcasecmp(substr($fileName, -strlen(".zip")), ".zip") !== 0) {
			$fileName .= ".zip";
		}
		$this->attach($fileName, gzencode($content, $level), "application/zip");
	}

    public function send()
    {
        if(!$this->files) {
            throw new RuntimeException("No Content To Flush");
        }

        $buffer = "";

        if(count($this->files) > 1) {
            $buffer .=  'alert("请允许浏览器自动下载多个文件！");';
        }
        foreach($this->files as $fileName => $data) {
            $buffer .=  "download('{$data}', '{$fileName}');";
        }

        // 多文件下载需要允许浏览器自动下载多个文件
        // 需要浏览器支持 download属性
        $buffer = <<<JS
<script>
(function(){
var download = function (dataUrl, fileName) {
  var link = document.createElement("a");
  link.download = fileName;
  link.href = dataUrl;
  link.click();
};
$buffer
}());
</script>
JS;
        echo $buffer;
        return true;
    }
}

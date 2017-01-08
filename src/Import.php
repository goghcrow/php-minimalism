<?php

namespace Minimalism;

/**
 * require_once remote or local file
 * @param string $path
 * @return mixed
 */
function import($path)
{
    $isUrl = strncasecmp($path, "http://", 4) === 0 || strncasecmp($path, "https://", 4) === 0;
    if ($isUrl) {
        $file = __DIR__ . "/deps" . parse_url($path, PHP_URL_PATH);
        if (!file_exists($file)) {
            @mkdir(dirname($file), 0777, true);
            $opts = [
                "http" => [ "method"  => "GET", "timeout" => 3],
                "ssl" => [ "verify_peer" => false,  "verify_peer_name" => false,]];
            $ctx  = stream_context_create($opts);
            $contents = file_get_contents($path, false, $ctx);
            file_put_contents($file, $contents);
        }
        /** @noinspection PhpUndefinedVariableInspection */
        return require_once $file;
    } else {
        /** @noinspection PhpIncludeInspection */
        return require_once $path;
    }
}
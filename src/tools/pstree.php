<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/6/7
 * Time: 下午8:48
 */


echo json_encode(pstree(), JSON_PRETTY_PRINT);

// 查找某pid的所有子孙pid
function findDescendantPids($pid)
{
    list($pinfo, ) = pstree();

    $y = function($pid) use(&$y, $pinfo) {
        if (isset($pinfo[$pid])) {
            list(, $childs) = $pinfo[$pid];
            $pids = $childs;
            foreach($childs as $child) {
                $pids = array_merge($pids, $y($child));
            }
            return $pids;
        } else {
            return [];
        }
    };

    return $y($pid);
}

/**
 * @return array [pinfo, tree]
 * tree [
 *  ppid
 *  [...child pids]
 * ]
 * list(ppid, array childs) = tree[pid]
 */
function pstree()
{
    $pinfo = [];
    $iter = new DirectoryIterator("/proc");
    foreach($iter as $item) {
        $pid = $item->getFilename();
        if ($item->isDir() && ctype_digit($pid)) {
            $stat = file_get_contents("/proc/$pid/stat");
            $info = explode(" ", $stat);
            $pinfo[$pid] = [intval($info[3]), []/*, $info*/];
        }
    }

    foreach($pinfo as $pid => $info) {
        list($ppid, ) = $info;
        $ppid = intval($ppid);
        $pinfo[$ppid][1][] = $pid;
    }

    $y = function($pid, $path = []) use(&$y, $pinfo) {
        if (isset($pinfo[$pid])) {
            list($ppid, ) = $pinfo[$pid];
            $ppid = $ppid;
            $path[] = $pid;
            return $y($ppid, $path);
        } else {
            return array_reverse($path);
        }
    };

    $tree = [];
    foreach($pinfo as $pid => $info) {
        $path = $y($pid);
        $node = &$tree;
        foreach($path as $id) {
            if (!isset($node[$id])) {
                $node[$id] = [];
            }
            $node = &$node[$id];
        }
    }


    return [$pinfo, $tree];
}

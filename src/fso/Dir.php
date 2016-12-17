<?php
/*
 *    Copyright 2012-2016 Youzan, Inc.
 *
 *    Licensed under the Apache License, Version 2.0 (the "License");
 *    you may not use this file except in compliance with the License.
 *    You may obtain a copy of the License at
 *
 *        http://www.apache.org/licenses/LICENSE-2.0
 *
 *    Unless required by applicable law or agreed to in writing, software
 *    distributed under the License is distributed on an "AS IS" BASIS,
 *    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *    See the License for the specific language governing permissions and
 *    limitations under the License.
 */
namespace rust\fso;

use rust\exception\InvalidArgumentException;
use rust\util\Arr;

class Dir {
    const SCAN_CURRENT_DIR = 'current';
    const SCAN_BFS = 'bfs';
    const SCAN_DFS = 'dfs';
    const SCAN_EXCLUDE_DIR = 'exclude';
    private static $excludeDirs = [];

    public static function glob($path, $pattern = NULL, $strategy = Dir::SCAN_DFS) {
        if (!is_dir($path) || !$pattern) {
            throw new InvalidArgumentException('invalid $path or $pattern for Dir::glob');
        }
        $files = Dir::scan($path, $strategy);
        $result = [];
        foreach ($files as $file) {
            if (FALSE === static::matchPattern($pattern, $file)) {
                continue;
            }
            $result[] = $file;
        }
        return $result;
    }

    public static function scan($path, $strategy = Dir::SCAN_CURRENT_DIR, $excludeDir = TRUE) {
        if (!is_dir($path)) {
            throw new InvalidArgumentException('invalid $path for Dir::scan');
        }
        switch ($strategy) {
        case static::SCAN_CURRENT_DIR:
            $files = static::scanCurrentDir($path, $excludeDir);
            break;
        case static::SCAN_BFS:
            $files = static::scanBfs($path, $excludeDir);
            break;
        case static::SCAN_DFS:
            $files = static::scanDfs($path, $excludeDir);
            break;
        default:
            throw new InvalidArgumentException('invalid $strategy for Dir::glob');
        }
        return $files;
    }

    public static function formatPath($path) {
        if ('/' == substr($path, -1)) {
            return $path;
        }
        return $path . '/';
    }

    public static function matchPattern($pattern, $file) {
        $replaceMap = [
            '*' => '.*',
            '.' => '\.',
            '+' => '.+',
            '/' => '\/',
        ];
        $pattern = str_replace(array_keys($replaceMap), array_values($replaceMap), $pattern);
        $pattern = '/' . $pattern . '/i';
        if (preg_match($pattern, $file)) {
            return TRUE;
        }
        return FALSE;
    }

    public static function basename($paths, $suffix = '') {
        if (!$paths) {
            return [];
        }
        $ret = [];
        foreach ($paths as $path) {
            $ret[] = basename($path, $suffix);
        }
        return $ret;
    }

    /**
     * @param array $dirs
     */
    public static function setExcludeDirs(array $dirs) {
        static::$excludeDirs = $dirs;
    }

    private static function scanCurrentDir($path, $excludeDir = TRUE) {
        $path = static::formatPath($path);
        if (static::$excludeDirs) {
            $find = in_array($path, static::$excludeDirs);
            if ($find) {
                return [];
            }
        }
        $dh = opendir($path);
        if (!$dh) {
            return [];
        }
        $files = [];
        while (FALSE !== ($file = readdir($dh))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $fileType = filetype($path . $file);
            if ('dir' == $fileType && FALSE === $excludeDir) {
                $files[] = $path . $file . '/';
            }
            if ('file' == $fileType) {
                $files[] = $path . $file;
            }
        }
        closedir($dh);
        return $files;
    }

    private static function scanBfs($path, $excludeDir = TRUE) {
        $files = [];
        $queue = new \SplQueue();
        $queue->enqueue($path);
        while (!$queue->isEmpty()) {
            $file = $queue->dequeue();
            $fileType = filetype($file);
            if ('dir' == $fileType) {
                $subFiles = static::scanCurrentDir($file, FALSE);
                foreach ($subFiles as $subFile) {
                    $queue->enqueue($subFile);
                }
                if (FALSE === $excludeDir && $file != $path) {
                    $files[] = $file;
                }
            }
            if ('file' == $fileType) {
                $files[] = $file;
            }
        }
        return $files;
    }

    private static function scanDfs($path, $excludeDir = TRUE) {
        $files = [];
        $subFiles = static::scanCurrentDir($path, FALSE);
        foreach ($subFiles as $subFile) {
            $fileType = filetype($subFile);
            if ('dir' == $fileType) {
                $innerFiles = static::scanDfs($subFile, $excludeDir);
                $files = Arr::join($files, $innerFiles);
                if (FALSE === $excludeDir) {
                    $files[] = $subFile;
                }
            }
            if ('file' == $fileType) {
                $files[] = $subFile;
            }
        }
        return $files;
    }
}
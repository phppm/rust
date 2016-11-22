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
namespace rust\dbo;
use rust\exception\storage\SqlMapCanNotFindException;
use rust\util\design\Singleton;

class SqlMap {
    use Singleton;
    private $sqlMaps = [];

    public function setSqlMaps($sqlMaps) {
        $this->sqlMaps = $sqlMaps;
    }

    public function getSql($sid, $data = [], $options = []) {
        $sqlMap = $this->getSqlMapBySid($sid);
        $sqlMap = $this->builder($sqlMap, $data, $options);
        return $sqlMap;
    }

    private function builder($sqlMap, $data, $options) {
        return (new SqlBuilder())->setSqlMap($sqlMap)->builder($data, $options)->getSqlMap();
    }

    private function getSqlMapBySid($sid) {
        $sidData  = $this->parseSid($sid);
        $key      = $sidData['key'];
        $filePath = $sidData['file_path'];
        if (!isset($this->sqlMaps[$filePath]) || [] == $this->sqlMaps[$filePath]) {
            throw new SqlMapCanNotFindException('no suck sql map');
        }
        $sqlMap = $this->sqlMaps[$filePath];
        if (!isset($sqlMap[$key]) || [] == $sqlMap[$key]) {
            throw new SqlMapCanNotFindException('no suck sql map');
        }
        return $sqlMap[$key];
    }

    private function parseSid($sid) {
        $pos = strrpos($sid, '.');
        if (FALSE === $pos) {
            throw new SqlMapCanNotFindException('no such sql id');
        }
        $filePath = substr($sid, 0, $pos);
        $base     = explode('.', $filePath);
        return [
            'file_path' => $filePath,
            'base'      => $base,
            'key'       => substr($sid, $pos + 1),
        ];
    }
}




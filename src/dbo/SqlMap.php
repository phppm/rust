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
use rust\Rust;
use rust\util\design\Singleton;

class SqlMap {
    use Singleton;

    public function getSql($sid, $data = [], $options = []) {
        $sqlMap = $this->getSqlMapBySid($sid);
        $sqlMap = $this->builder($sqlMap, $data, $options);
        return $sqlMap;
    }

    /**
     * @param array $sqlMap
     * @param array $data
     * @param array $options
     *
     * @return mixed
     */
    private function builder($sqlMap, $data, $options) {
        return (new SqlBuilder())->setSqlMap($sqlMap)->builder($data, $options)->getSqlMap();
    }

    /**
     * @param string $sid
     *
     * @return null
     * @throws SqlMapCanNotFindException
     */
    private function getSqlMapBySid($sid) {
        $app_config = Rust::getConfig();
        $sql_map    = $app_config->get($sid);
        if (!$sql_map) {
            throw new SqlMapCanNotFindException('no suck sql map');
        }
        $sql_map = (new SqlParser())->setSqlMap($sql_map)->parse()->getSqlMap();
        return $sql_map;
    }
}




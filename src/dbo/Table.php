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
use rust\exception\storage\SQLTableException;
use rust\Rust;
use rust\util\design\Singleton;

class Table {
    use Singleton;
    private $tables = [];

    public function getDatabase($tableName) {
        if (!isset($this->tables[$tableName])) {
            $this->init();
            if (!isset($this->tables[$tableName])) {
                throw new SQLTableException('无法获取数' . $tableName . '表所在的数据库配置');
            }
        }
        return $this->tables[$tableName];
    }

    public function init() {
        $this->initTables();
    }

    private function initTables() {
        if ([] == $this->tables) {
            $config = Rust::getConfig();
            $tables = $config->get('tables');
            if (NULL == $tables || [] == $tables) {
                return;
            }
            $result = [];
            foreach ($tables as $db => $tableList) {
                foreach ($tableList as $tableName) {
                    $result[$tableName] = $db;
                }
            }
            $this->tables = $result;
        }
        return;
    }
}

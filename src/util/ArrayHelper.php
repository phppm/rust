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

namespace rust\util;
/**
 * Class ArrayHelper
 *
 * @package rust\util
 */
class ArrayHelper {
    const FILTER_NULL=1;
    const FILTER_EMPTY=2;

    /**
     * @param array $arr
     * @param int   $type
     *
     * @return array
     */
    public static function filter(array $arr, int $type=self::FILTER_NULL): array {
        $filterHandler=function($val) {
            return is_null($val) ? false : true;
        };
        if (static::FILTER_EMPTY === $type) {
            $filterHandler=function($val) {
                return is_null($val) || '' === $val ? false : true;
            };
        }
        return array_filter($arr, $filterHandler);
    }
}

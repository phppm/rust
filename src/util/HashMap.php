<?php
namespace rust\util;
/**
 * Class HashMap
 *
 * @package rust\util
 */
Class HashMap {
    /**
     * @var array $hashTable
     */
    private $hashTable;

    /**
     * HashMap constructor
     */
    public function __construct() {
        $this->hashTable = [];
    }

    /*
     * 添加一个键值对
     * @param string $key
     * @param mixed $value
     *
     * @return mixed|null
     */
    public function put($key, $value) {
        if (!array_key_exists($key, $this->hashTable)) {
            $this->hashTable[$key] = $value;
            return NULL;
        }
        $tempValue = $this->hashTable[$key];
        $this->hashTable[$key] = $value;
        return $tempValue;
    }

    /**
     * 根据key获取对应的value
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function get($key) {
        if (array_key_exists($key, $this->hashTable)) {
            return $this->hashTable[$key];
        }
        return NULL;
    }

    /**
     * 删除指定key
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function remove($key) {
        $temp_table = [];
        if (!array_key_exists($key, $this->hashTable)) {
            return NULL;
        }
        $tempValue = $this->hashTable[$key];
        while ($curValue = current($this->hashTable)) {
            if (!(key($this->hashTable) == $key)) {
                $temp_table[key($this->hashTable)] = $curValue;
            }
            next($this->hashTable);
        }
        $this->hashTable = NULL;
        $this->hashTable = $temp_table;
        return $tempValue;
    }

    /**
     * 获取所有键值
     *
     * @return array
     */
    public function keys() {
        return array_keys($this->hashTable);
    }

    /**
     * 获取HashMap的所有value值
     *
     * @return array
     */
    public function values() {
        return array_values($this->hashTable);
    }

    /**
     * put一个HashMap到当前HashMap中
     *
     * @param HashMap $map
     */
    public function putAll(HashMap $map): void {
        if (!$map || $map->isEmpty() || $map->size() < 1) {
            return;
        }
        $keys = $map->keys();
        foreach ($keys as $key) {
            $this->put($key, $map->get($key));
        }
    }

    /**
     * 移除所有元素
     */
    public function removeAll(): void {
        $this->hashTable = NULL;
        $this->hashTable = [];
    }

    /**
     * 是否包含指定的值
     *
     * @param $value
     *
     * @return bool
     */
    public function containsValue($value) {
        while ($curValue = current($this->hashTable)) {
            if ($curValue == $value) {
                return TRUE;
            }
            next($this->hashTable);
        }
        return FALSE;
    }

    /**
     * 是否包含指定的键key
     *
     * @param string $key
     *
     * @return bool
     */
    public function containsKey($key): bool {
        if (array_key_exists($key, $this->hashTable)) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * 获取元素个数
     *
     * @return int
     */
    public function size(): int {
        return count($this->hashTable);
    }

    /**
     * 判断是否为空
     *
     * @return bool
     */
    public function isEmpty(): bool {
        return (0 === count($this->hashTable));
    }

    /**
     * 转成字符串
     *
     * @return mixed
     */
    public function toString() {
        return print_r($this->hashTable, TRUE);
    }
}
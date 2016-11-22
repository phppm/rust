<?php
/**
 * Database SQL Command
 *
 * @author rustysun.cn@gmail.com
 */
namespace rust\dbo;

use rust\exception\RustException;

/**
 * Class Command
 *
 * @package rust\dbo
 */
class Command {
    const OPTION_FIELDS   = 'fields';
    const OPTION_SETS     = 'sets';
    const OPTION_WHERE    = 'where';
    const OPTION_ORDER_BY = 'orderBy';
    const OPTION_GROUP_BY = 'groupBy';
    const OPTION_LIMIT    = 'limit';
    const OPTION_JOIN     = 'join';
    const OPTION_TABLE    = 'table';
    const OPTION_HAVING   = 'having';
    const OPTION_UNION    = 'union';
    const OPTION_CMD      = 'cmd';
    const OPTION_VALUES   = 'values';
    const OPTION_COLUMNS  = 'columns';
    const OPTION_ACTION   = 'action';
    const OPTION_FIXED    = 'fixed';
    //command
    const DELETE        = 'DELETE';
    const INSERT        = 'INSERT';
    const INSERT_UPDATE = 'INSERT_UPDATE';
    const UPDATE        = 'UPDATE';
    const SELECT        = 'SELECT';
    const ALTER         = 'ALERT';
    const SHOW          = 'SHOW';
    const CREATE        = 'CREATE';
    const DROP          = 'DROP';
    const TRUNCATE      = 'TRUNCATE';
    protected $cmd         = self::SELECT;
    protected $cmd_map     = [
        self::DELETE        => 'DELETE FROM #table# #where#',
        self::INSERT        => 'INSERT #cmd# INTO #table# (#fields#) VALUES (#values#)',
        self::INSERT_UPDATE => 'INSERT INTO #table# (#fields#) VALUES (#values#) ON DUPLICATE KEY UPDATE #sets#',
        self::UPDATE        => 'UPDATE #table# SET #sets# #where#',
        self::SELECT        => 'SELECT #fields# FROM #table# #join# #where# #groupBy# #having# #orderBy# #limit# #union#',
        self::SHOW          => 'SHOW #cmd#', self::ALTER => 'ALTER #cmd# #table# #action# #colums# #fixed#',
        self::CREATE        => 'CREATE #cmd# #table# (#columns#) #fixed#', self::DROP => 'DROP #cmd# #table#',
        self::TRUNCATE      => 'TRUNCATE #table#',
    ];
    protected $options     = [];
    protected $bind_params = [];

    /**
     * Command constructor.
     *
     * @param null $options
     */
    public function __construct($options = NULL) {
        if ($options && is_array($options)) {
            $this->options = $options;
        }
    }

    /**
     * Delete
     *
     * @param string $table
     *
     * @return Command
     */
    public function delete($table) {
        $this->clear();
        $this->cmd = self::DELETE;
        $this->table($table);
        return $this;

    }

    /**
     * get bind params
     *
     * @return array
     */
    public function getBindParams() {
        return $this->bind_params;
    }

    /**
     * Insert
     *
     * @param string $table
     * @param array  $data
     * @params array $sets
     *
     * @return $this
     */
    public function insert($table, $data, $sets = []) {
        if (!$table || !$data || !is_array($data)) {
            return $this;
        }
        $this->clear();
        $this->cmd = self::INSERT;
        if ($sets && is_array($sets)) {
            $this->cmd = self::INSERT_UPDATE;
            $this->options[self::OPTION_SETS] = array_map(function ($name, $value) {
                return $name . '=\'' . $value . '\'';
            }, array_keys($sets), array_values($sets));
        }
        $this->table($table);
        $this->options[self::OPTION_FIELDS] = array_keys($data);
        $this->options[self::OPTION_VALUES] = array_fill(0, count($data), '?');
        $this->addBindParams(array_values($data));
        return $this;
    }

    /**
     * Add fields for select
     *
     * @param string|array $fields
     *
     * @example $cmd->select('id,name,nick');
     *
     * @return Command
     */
    public function select($fields) {
        $this->clear();
        $this->cmd = self::SELECT;
        $this->options[self::OPTION_FIELDS] = is_array($fields) ? $fields : [$fields];
        return $this;
    }

    /**
     * Update
     *
     * @param string $table
     * @param array  $data
     *
     * @return Command
     */
    public function update($table, $data) {
        $this->clear();
        $this->cmd = self::UPDATE;
        $this->table($table);
        $this->updateSets($data);
        return $this;
    }

    /**
     * Add statement for GROUP BY
     *
     * @param string $statement
     *
     * @example $cmd->groupBy('pub_date')
     *
     * @return Command
     */
    public function groupBy($statement) {
        $this->options[self::OPTION_GROUP_BY][] = $statement;
        return $this;
    }

    /**
     * Add statement for HAVING
     *
     * @param string $statement
     * @param mixed  $params
     *
     * @example $cmd->having('count_num>?',[1])
     *
     * @return Command
     */
    public function having($statement, $params = NULL) {
        $this->options[self::OPTION_HAVING][] = $statement;
        $this->addBindParams($params);

        return $this;
    }

    /**
     * Add where (not)in statement
     *
     * @param string $column
     * @param array  $params
     * @param bool   $is_not_in
     *
     * @example $cmd->whereIn('id',[1,2,3,4])
     *          $cmd->whereIn('id',[6,7,8,9],TRUE)
     *
     * @return Command
     */
    public function whereIn($column, $params, $is_not_in = FALSE) {
        $qm = implode(',', array_fill(0, count($params), '?'));
        $in = $is_not_in ? ' NOT IN ' : ' IN ';
        $this->options[self::OPTION_WHERE][] = $column . $in . ' (' . $qm . ')';
        $this->addBindParams($params);

        return $this;
    }

    /**
     * Add statement for join
     *
     * @param string $statement
     *
     * @example $cmd->join("INNER JOIN posts p ON p.user_id = u.user_id")
     *
     * @return Command
     */
    public function join($statement) {
        $this->options[self::OPTION_JOIN][] = $statement;
        return $this;
    }

    /**
     * Add statement for limit
     *
     * @param int $limit
     * @param int $offset
     *
     * @example $cmd->limit(30);
     *          $cmd->limit(30,30);
     *
     * @return Command
     */
    public function limit($limit, $offset = NULL) {
        $result = '';
        if (!is_null($offset)) {
            $result = $offset . ', ';
        }
        $result .= $limit;
        $this->options[self::OPTION_LIMIT] = $result;
        return $this;
    }

    /**
     * Add statement for order by
     *
     * @param string $statement
     *
     * @return Command
     */
    public function orderBy($statement) {
        $this->options[self::OPTION_ORDER_BY][] = $statement;

        return $this;
    }

    /**
     * Add table for SQL
     *
     * @param string $statement
     *
     * @example $cmd->table("users");
     *          $cmd->table("users u, posts p");
     *
     * @return Command
     */
    public function table($statement) {
        $this->options[self::OPTION_TABLE][] = $statement;
        return $this;
    }

    /**
     * Returns generated SQL
     *
     * @return mixed
     * @throws RustException
     */
    public function toString() {
        $sql_map = isset($this->cmd_map[$this->cmd]) ? $this->cmd_map[$this->cmd] : NULL;
        if (!$sql_map) {
            throw new RustException(1003);
        }
        $options = [];
        //generate fields
        $fields = isset($this->options[self::OPTION_FIELDS]) ? $this->options[self::OPTION_FIELDS] : ['*'];
        if ($fields && is_array($fields)) {
            $options['/#' . self::OPTION_FIELDS . '#/'] = implode(', ', $fields);
        }
        //generate cmd
        $cmd = isset($this->options[self::OPTION_CMD]) ? $this->options[self::OPTION_CMD] : '';
        $options['/#' . self::OPTION_CMD . '#/'] = $cmd;
        //generate from
        $tables = isset($this->options[self::OPTION_TABLE]) ? $this->options[self::OPTION_TABLE] : NULL;
        if ($tables && is_array($tables)) {
            $options['/#' . self::OPTION_TABLE . '#/'] = implode(', ', $tables);
        }
        //generate join
        $joins = isset($this->options[self::OPTION_JOIN]) ? $this->options[self::OPTION_JOIN] : [];
        $options['/#' . self::OPTION_JOIN . '#/'] = implode(', ', $joins);
        //generate values
        $values = isset($this->options[self::OPTION_VALUES]) ? $this->options[self::OPTION_VALUES] : [];
        $options['/#' . self::OPTION_VALUES . '#/'] = $values ? implode(', ', $values) : '';
        //generate columns
        $columns = isset($this->options[self::OPTION_COLUMNS]) ? $this->options[self::OPTION_COLUMNS] : [];
        $options['/#' . self::OPTION_COLUMNS . '#/'] = $columns ? implode(', ', $columns) : '';
        //generate sets
        $sets = isset($this->options[self::OPTION_SETS]) ? $this->options[self::OPTION_SETS] : [];
        $options['/#' . self::OPTION_SETS . '#/'] = $sets ? implode(', ', $sets) : '';
        //generate where
        $where = isset($this->options[self::OPTION_WHERE]) ? $this->options[self::OPTION_WHERE] : NULL;
        if ($where && is_array($where)) {
            $options['/#' . self::OPTION_WHERE . '#/'] = ' WHERE ' . implode(', ', $where);
        }
        //generate group
        $group_by = isset($this->options[self::OPTION_GROUP_BY]) ? $this->options[self::OPTION_GROUP_BY] : [];
        $options['/#' . self::OPTION_GROUP_BY . '#/'] = ($group_by ? ' GROUP BY ' : '') . implode(', ', $group_by);
        //generate having
        $having = isset($this->options[self::OPTION_HAVING]) ? $this->options[self::OPTION_HAVING] : [];
        $options['/#' . self::OPTION_HAVING . '#/'] = ($having ? ' HAVING ' : '') . implode(', ', $having);
        //generate order by
        $order_by = isset($this->options[self::OPTION_ORDER_BY]) ? $this->options[self::OPTION_ORDER_BY] : [];
        $options['/#' . self::OPTION_ORDER_BY . '#/'] = ($order_by ? ' ORDER BY ' : '') . implode(', ', $order_by);
        //generate limit
        $limit = isset($this->options[self::OPTION_LIMIT]) ? $this->options[self::OPTION_LIMIT] : '';
        $options['/#' . self::OPTION_LIMIT . '#/'] = ($limit ? ' LIMIT ' : '') . $limit;
        //generate union
        $union = isset($this->options[self::OPTION_UNION]) ? $this->options[self::OPTION_UNION] : '';
        $options['/#' . self::OPTION_UNION . '#/'] = ($union ? ' UNION ' : '') . $union;
        //generate action
        $action = isset($this->options[self::OPTION_ACTION]) ? $this->options[self::OPTION_ACTION] : '';
        $options['/#' . self::OPTION_ACTION . '#/'] = $action;
        //generate fixed
        $fixed = isset($this->options[self::OPTION_FIXED]) ? $this->options[self::OPTION_FIXED] : '';
        $options['/#' . self::OPTION_FIXED . '#/'] = $fixed;
        return preg_replace(array_keys($options), array_values($options), $sql_map);
    }

    /**
     * Add statement for WHERE
     *
     * @param string $statement
     * @param mixed  $params
     *
     * @example $cmd->where('id=?',[1])
     *
     * @return Command
     */
    public function where($statement, $params = NULL) {
        $this->options[self::OPTION_WHERE][] = $statement;
        $this->addBindParams($params);
        return $this;
    }

    /**
     * Add bind params for prepare
     *
     * @param array $params
     *
     * @return void
     */
    protected function addBindParams($params) {
        if (is_null($params)) {
            return;
        }
        if (!is_array($params)) {
            $params = [$params];
        }
        $this->bind_params = array_merge($this->bind_params, $params);
    }

    /**
     * Clear previous assigned options
     *
     * @param null
     *
     * @return Command
     */
    protected function clear($type = NULL) {
        $this->bind_params = [];
        if (!$type) {
            $this->options = [
                self::OPTION_FIELDS   => '', self::OPTION_CMD => '', self::OPTION_TABLE => [], self::OPTION_WHERE => [],
                self::OPTION_HAVING   => [], self::OPTION_JOIN => [], self::OPTION_ORDER_BY => [],
                self::OPTION_GROUP_BY => [], self::OPTION_SETS => [], self::OPTION_COLUMNS => [],
                self::OPTION_VALUES   => [], self::OPTION_LIMIT => '', self::OPTION_ACTION => '',
                self::OPTION_FIXED    => '',
            ];
        }
        $this->options[$type] = '';
        if (!in_array($type, [
            self::OPTION_FIELDS, self::OPTION_LIMIT, self::OPTION_CMD, self::OPTION_ACTION, self::OPTION_FIXED,
        ])
        ) {
            $this->options[$type] = [];
        }
        return $this;
    }

    /**
     * prepare update sets
     *
     * @param $data
     */
    protected function updateSets($data) {
        $sets = array_map(function ($key) {
            return $key . '=?';
        }, array_keys($data));
        $this->options[self::OPTION_SETS] = $sets;
        $this->addBindParams(array_values($data));
    }
}
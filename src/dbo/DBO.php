<?php
namespace rust\dbo;

use \PDO;
use rust\exception\storage\DBOExecuteException;
use rust\util\Log;

/**
 * DBO extends PDO
 */
class DBO extends \PDO {
    /**
     * DBO constructor.
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array  $options
     */
    public function __construct($dsn, $username, $password, $options = []) {
        $options = $options && is_array($options) ? $options : [];
        $options += [
            \PDO::ATTR_STATEMENT_CLASS => [
                '\\rust\\dbo\\Statement', [$this],
            ], \PDO::ATTR_ERRMODE      => \PDO::ERRMODE_EXCEPTION,
        ];
        parent::__construct($dsn, $username, $password, $options);
    }

    /**
     * Prepare & execute query with params
     *
     * @param      $sql
     * @param null $bind_params
     *
     * @return null|\PDOStatement
     * @throws DBOExecuteException
     */
    public function execute($sql, $bind_params = NULL) {
        $exec_result = NULL;
        $stmt = NULL;
        try {
            $stmt = $this->prepare($sql);
            Log::write($this->getPreparedSQL($sql, $bind_params),'sql');
            $exec_result = $stmt->execute($bind_params);
            //TODO:写入SQL日志
        }
        catch (\PDOException $e) {

        }
        if (!$exec_result) {
            $err_info = $stmt->errorInfo();
            $msg = array_pop($err_info);
            $data = [
                'driver_code'    => isset($err_info[1]) ? $err_info[1] : NULL,
                'sql'            => $this->getPreparedSQL($sql, $bind_params),
                'sql_state_code' => isset($err_info[0]) ? $err_info[0] : NULL,
            ];
            throw new DBOExecuteException($msg, $data);
        }
        return $stmt;
    }

    /**
     * get prepared sql
     *
     * @param string $sql
     * @param array  $bind_params
     *
     * @return null|string
     */
    protected function getPreparedSQL($sql, $bind_params) {
        if (!$sql || !$bind_params || !is_array($bind_params)) {
            return NULL;
        }
        $result = $sql;
        $is_normal = isset($bind_params[0]) ? TRUE : FALSE;
        $pattern = '/\?/i';
        foreach ($bind_params as $key => $param) {
            $value = is_numeric($param) || is_bool($param) ? $param : '\'' . $param . '\'';
            $pattern = !$is_normal ? '/' . $key . '/i' : $pattern;
            $result = preg_replace($pattern, $value, $result, 1);
        }
        return $result;
    }
}

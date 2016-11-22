<?php
namespace rust\task;

use \Generator;

/**
 * Class Task
 *
 * @package rust\task
 */
class Task {
    protected $taskId;
    protected $coroutine;
    protected $sendValue        = NULL;
    protected $beforeFirstYield = TRUE;

    public function __construct($taskId, Generator $coroutine) {
        $this->taskId = $taskId;
        $this->coroutine = $coroutine;
    }

    public function getTaskId() {
        return new SystemCall(function (Task $task, Scheduler $scheduler) {
            $task->setSendValue($task->getTaskId());
            $scheduler->schedule($task);
        });
    }

    public function setSendValue($sendValue) {
        $this->sendValue = $sendValue;
    }

    public function run() {
        if ($this->beforeFirstYield) {
            $this->beforeFirstYield = FALSE;
            return $this->coroutine->current();
        } else {
            $retval = $this->coroutine->send($this->sendValue);
            $this->sendValue = NULL;
            return $retval;
        }
    }

    public function isFinished() {
        return !$this->coroutine->valid();
    }
}
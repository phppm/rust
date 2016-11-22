<?php
namespace rust\task;

use \Generator;
use \SplQueue;

/**
 * Class Scheduler
 *
 * @package rust\task
 */
class Scheduler {
    protected $maxTaskId = 0;
    protected $taskMap   = []; // taskId => task
    protected $taskQueue;

    public function __construct() {
        $this->taskQueue = new SplQueue();
    }

    public function killTask($tid) {
        if (!isset($this->taskMap[$tid])) {
            return FALSE;
        }

        unset($this->taskMap[$tid]);

        // This is a bit ugly and could be optimized so it does not have to walk the queue,
        // but assuming that killing tasks is rather rare I won't bother with it now
        foreach ($this->taskQueue as $i => $task) {
            if ($task->getTaskId() === $tid) {
                unset($this->taskQueue[$i]);
                break;
            }
        }

        return TRUE;
    }

    public function newTask(Generator $coroutine) {
        $tid = ++$this->maxTaskId;
        $task = new Task($tid, $coroutine);
        $this->taskMap[$tid] = $task;
        $this->schedule($task);
        return $tid;
    }

    public function schedule(Task $task) {
        $this->taskQueue->enqueue($task);
    }

    public function run() {
        while (!$this->taskQueue->isEmpty()) {
            $task = $this->taskQueue->dequeue();
            $ret_val = $task->run();

            if ($ret_val instanceof SystemCall) {
                $ret_val($task, $this);
                continue;
            }

            if ($task->isFinished()) {
                unset($this->taskMap[$task->getTaskId()]);
            } else {
                $this->schedule($task);
            }
        }
    }
}
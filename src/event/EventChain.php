<?php
namespace rust\event;
class EventChain {
    private $beforeMap = [];
    private $afterMap  = [];
    private $event     = NULL;

    public function __construct(Event $event) {
        $this->beforeMap = [];
        $this->afterMap = [];
        $this->event = $event;
    }

    /**
     * 连接N个传入的事件为事件链
     * @return bool
     */
    public function join() {
        $argNum = func_num_args();
        if ($argNum < 2) {
            return FALSE;
        }
        $args = func_get_args();
        $beforeEvt = NULL;
        $afterEvt = NULL;
        foreach ($args as $evt) {
            if (NULL === $beforeEvt) {
                $beforeEvt = $evt;
                continue;
            }
            $afterEvt = $evt;
            $this->after($beforeEvt, $afterEvt);
            $beforeEvt = $afterEvt;
        }
        return TRUE;
    }

    /**
     * 断开两个事件链接
     * @param $beforeEvt
     * @param $afterEvt
     */
    public function breakChain($beforeEvt, $afterEvt) {
        $this->crackAfterChain($beforeEvt, $afterEvt);
        $this->crackBeforeChain($beforeEvt, $afterEvt);
    }

    /**
     * @param $beforeEvt
     * @param $afterEvt
     * @return bool
     */
    public function after($beforeEvt, $afterEvt) {
        if (!isset($this->afterMap[$beforeEvt])) {
            $this->afterMap[$beforeEvt] = [];
        }
        $this->afterMap[$beforeEvt][$afterEvt] = 1;
        return TRUE;
    }

    /**
     * @param $beforeEvt
     * @param $afterEvt
     * @return bool
     */
    public function before($beforeEvt, $afterEvt) {
        $this->after($beforeEvt, $afterEvt);
        if (!isset($this->beforeMap[$afterEvt])) {
            $this->beforeMap[$afterEvt] = [$beforeEvt => 0];
        }
        $this->beforeMap[$afterEvt][$beforeEvt] = 0;
        return TRUE;
    }

    public function fireEventChain($evtName) {
        if (!isset($this->afterMap[$evtName]) || !$this->afterMap[$evtName]) {
            return FALSE;
        }
        foreach ($this->afterMap[$evtName] as $afterEvt => $count) {
            $this->fireAfterEvent($evtName, $afterEvt);
        }
        return TRUE;
    }

    private function fireAfterEvent($beforeEvt, $afterEvt) {
        $this->fireBeforeEvent($beforeEvt, $afterEvt);
        if (TRUE !== $this->isBeforeEventFired($afterEvt)) {
            return FALSE;
        }
        $this->event->fire($afterEvt);
        $this->clearBeforeEventBind($afterEvt);
        return TRUE;
    }

    /**
     * @param $beforeEvt
     * @param $afterEvt
     * @return bool
     */
    private function fireBeforeEvent($beforeEvt, $afterEvt) {
        if (!isset($this->beforeMap[$afterEvt])) {
            return FALSE;
        }
        if (!isset($this->beforeMap[$afterEvt][$beforeEvt])) {
            return FALSE;
        }
        $this->beforeMap[$afterEvt][$beforeEvt]++;
        return TRUE;
    }

    /**
     * @param $afterEvt
     * @return bool
     */
    private function clearBeforeEventBind($afterEvt) {
        if (!isset($this->beforeMap[$afterEvt])) {
            return FALSE;
        }
        $decrease = function (&$v) {
            return $v--;
        };
        array_walk($this->beforeMap[$afterEvt], $decrease);
        return TRUE;
    }

    private function isBeforeEventFired($afterEvt) {
        if (!isset($this->beforeMap[$afterEvt])) {
            return TRUE;
        }
        foreach ($this->beforeMap[$afterEvt] as $count) {
            if ($count < 1) {
                return FALSE;
            }
        }
        return TRUE;
    }

    private function crackAfterChain($beforeEvt, $afterEvt) {
        if (!isset($this->afterMap[$beforeEvt])) {
            return FALSE;
        }
        if (!isset($this->afterMap[$beforeEvt][$afterEvt])) {
            return FALSE;
        }
        unset($this->afterMap[$beforeEvt][$afterEvt]);
        return TRUE;
    }

    private function crackBeforeChain($beforeEvt, $afterEvt) {
        if (!isset($this->beforeMap[$afterEvt])) {
            return FALSE;
        }
        if (!isset($this->beforeMap[$afterEvt][$beforeEvt])) {
            return FALSE;
        }
        unset($this->beforeMap[$afterEvt][$beforeEvt]);
        return TRUE;
    }
}
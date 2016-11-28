<?php
namespace rust\event;
class EventChain {
    private static $beforeMap = [];
    private static $afterMap  = [];

    public static function clear() {
        self::$beforeMap = [];
        self::$afterMap  = [];
    }

    /**
     * 连接N个传入的事件为事件链
     *
     * @param args
     *
     * @return bool
     */
    public static function join() {
        $argNum = func_num_args();
        if ($argNum < 2) {
            return FALSE;
        }
        $args = func_get_args();
        $beforeEvt = NULL;
        $afterEvt  = NULL;
        foreach ($args as $evt) {
            if (NULL === $beforeEvt) {
                $beforeEvt = $evt;
                continue;
            }
            $afterEvt = $evt;
            self::after($beforeEvt, $afterEvt);
            $beforeEvt = $afterEvt;
        }
    }

    /**
     * 断开两个事件链接
     *
     * @param $beforeEvt
     * @param $afterEvt
     */
    public static function breakChain($beforeEvt, $afterEvt) {
        self::crackAfterChain($beforeEvt, $afterEvt);
        self::crackBeforeChain($beforeEvt, $afterEvt);
    }

    public static function after($beforeEvt, $afterEvt) {
        if (!isset(self::$afterMap[$beforeEvt])) {
            self::$afterMap[$beforeEvt] = [$afterEvt => 1];
            return TRUE;
        }
        self::$afterMap[$beforeEvt][$afterEvt] = 1;
    }

    public static function before($beforeEvt, $afterEvt) {
        self::after($beforeEvt, $afterEvt);
        if (!isset(self::$beforeMap[$afterEvt])) {
            self::$beforeMap[$afterEvt] = [$beforeEvt => 0];
            return TRUE;
        }
        self::$beforeMap[$afterEvt][$beforeEvt] = 0;
    }

    public static function fireEventChain($evtName) {
        if (!isset(self::$afterMap[$evtName]) || !self::$afterMap[$evtName]) {
            return FALSE;
        }
        foreach (self::$afterMap[$evtName] as $afterEvt => $count) {
            self::fireAfterEvent($evtName, $afterEvt);
        }
        return TRUE;
    }

    private static function fireAfterEvent($beforeEvt, $afterEvt) {
        self::fireBeforeEvent($beforeEvt, $afterEvt);
        if (TRUE !== self::isBeforeEventFired($afterEvt)) {
            return FALSE;
        }
        Event::fire($afterEvt);
        self::clearBeforeEventBind($afterEvt);
    }

    private static function fireBeforeEvent($beforeEvt, $afterEvt) {
        if (!isset(self::$beforeMap[$afterEvt])) {
            return FALSE;
        }
        if (!isset(self::$beforeMap[$afterEvt][$beforeEvt])) {
            return FALSE;
        }
        self::$beforeMap[$afterEvt][$beforeEvt]++;
    }

    private static function clearBeforeEventBind($afterEvt) {
        if (!isset(self::$beforeMap[$afterEvt])) {
            return FALSE;
        }
        $decrease = function (&$v) {
            return $v--;
        };
        array_walk(self::$beforeMap[$afterEvt], $decrease);
    }

    private static function isBeforeEventFired($afterEvt) {
        if (!isset(self::$beforeMap[$afterEvt])) {
            return TRUE;
        }
        foreach (self::$beforeMap[$afterEvt] as $count) {
            if ($count < 1) {
                return FALSE;
            }
        }
        return TRUE;
    }

    private static function crackAfterChain($beforeEvt, $afterEvt) {
        if (!isset(self::$afterMap[$beforeEvt])) {
            return FALSE;
        }
        if (!isset(self::$afterMap[$beforeEvt][$afterEvt])) {
            return FALSE;
        }
        unset(self::$afterMap[$beforeEvt][$afterEvt]);
        return TRUE;
    }

    private static function crackBeforeChain($beforeEvt, $afterEvt) {
        if (!isset(self::$beforeMap[$afterEvt])) {
            return FALSE;
        }
        if (!isset(self::$beforeMap[$afterEvt][$beforeEvt])) {
            return FALSE;
        }
        unset(self::$beforeMap[$afterEvt][$beforeEvt]);
        return TRUE;
    }
}
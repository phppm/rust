<?php
namespace rust\util\design;
/**
 * Class Subject
 * @package rust\util\design
 */
abstract class AbstractSubject {
    abstract function attach(AbstractObserver $observer_in);

    abstract function detach(AbstractObserver $observer_in);

    abstract function notify();
}
<?php
namespace rust\util\design;

/**
 * Trait Observer
 * @package rust\util\design
 */
abstract class AbstractObserver {
    abstract function update(AbstractSubject $subject_in);
}
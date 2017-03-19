<?php
namespace rust\interfaces\able;

interface ICallable {
    /**
     * Computes a result, or throws an exception if unable to do so.
     *
     * @return mixed computed result
     */
    public function call(): mixed;
}
<?php
namespace rust\interfaces\async;

use rust\interfaces\able\IRunnable;

interface IRunnableFuture extends IRunnable, IFuture {
    /**
     * Sets this Future to the result of its computation
     * unless it has been cancelled.
     */
    public function run(): void;
}
<?php
namespace rust\interfaces\async;

interface IFuture {
    /**
     * Attempts to cancel execution of this task.  This attempt will
     * fail if the task has already completed, has already been cancelled,
     * or could not be cancelled for some other reason. If successful,
     * and this task has not started when <tt>cancel</tt> is called,
     * this task should never run.  If the task has already started,
     * then the <tt>mayInterruptIfRunning</tt> parameter determines
     * whether the thread executing this task should be interrupted in
     * an attempt to stop the task.
     *
     * @param bool $mayInterruptIfRunning
     *
     * @return bool
     */
    public function cancel(bool $mayInterruptIfRunning): bool;

    /**
     * Returns <tt>true</tt> if this task was cancelled before it completed
     * normally.
     */
    public function isCancelled(): bool;

    /**
     * Returns <tt>true</tt> if this task completed.
     *
     */
    public function isDone(): bool;

    /**
     * Waits if necessary for the computation to complete, and then
     * retrieves its result.
     *
     * @return mixed the computed result
     */
    public function get(): mixed;

    /**
     * Waits if necessary for at most the given time for the computation
     * to complete, and then retrieves its result, if available.
     *
     * @param int $timeout the maximum time to wait
     *
     * @return mixed
     */
    public function getByTimeout(int $timeout): mixed;
    //throws InterruptedException, ExecutionException, TimeoutException;
}
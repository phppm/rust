<?php
/**
 *
 */
namespace rut\exception;

use \Exception;

/**
 * Wraps ErrorException; mostly used for typing (at least now)
 * to easily cleanup the stack trace of redundant info.
 */
class Error extends Exception {
}

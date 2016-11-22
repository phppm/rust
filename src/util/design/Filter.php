<?php
namespace rust\util\design;
/**
 * Interface Filter
 * @package rust\util\design
 */
interface Filter {
    public function doFilter($request, $response, $context);
}
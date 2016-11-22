<?php
namespace rust\util\design;
/**
 * Class FilterChain
 * @package rust\util\deisgn
 */
class FilterChain {
    private $request  = NULL;
    private $response = NULL;
    private $context  = NULL;
    private $filters  = [];

    public function __construct($request, $response, $context) {
        $this->request = $request;
        $this->response = $response;
        $this->context = $context;
        $this->filters = [];
    }

    public function addFilter(Filter $filter) {
        $this->filters[] = $filter;
    }

    public function execute() {
        if (empty($this->filters)) {
            return NULL;
        }
        foreach ($this->filters as $filter) {
            $filter->doFilter(
                $this->request,
                $this->response,
                $this->context
            );
        }
    }
}
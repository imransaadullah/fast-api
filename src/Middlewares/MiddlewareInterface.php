<?php

namespace FASTAPI\Middlewares;

use FASTAPI\Request;

interface MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void;
    // public function matches(Request $request): bool;
}

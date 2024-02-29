<?php

namespace FASTAPI\Middlewares;

use FASTAPI\Request;
use FASTAPI\Response;

interface MiddlewareInterface {
    public function handle(Request $request, \Closure $next): Response;
    public function matches(Request $request): bool;
}

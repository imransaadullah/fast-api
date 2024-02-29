<?php

namespace FASTAPI\Middlewares;

interface BeforeMiddlewareInterface extends MiddlewareInterface {
    public function before(): bool;
}
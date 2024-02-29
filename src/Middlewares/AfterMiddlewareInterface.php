<?php

namespace FASTAPI\Middlewares;

interface AfterMiddlewareInterface extends MiddlewareInterface {
    public function after(): bool;
}
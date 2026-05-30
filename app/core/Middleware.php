<?php

/**
 * Base Middleware Class
 * 
 * All system middleware classes can inherit from this base class.
 */
abstract class Middleware
{
    /**
     * Handle the incoming request.
     * 
     * @return void
     */
    abstract public function handle();
}

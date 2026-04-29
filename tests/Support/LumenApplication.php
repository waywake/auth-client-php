<?php

namespace Laravel\Lumen;

if (!class_exists(Application::class, false)) {
    class Application extends \Illuminate\Container\Container
    {
        public array $configured = [];

        public function __construct(public string|null $basePath = null)
        {
        }

        public function runningInConsole()
        {
            return false;
        }

        public function configure($name)
        {
            $this->configured[] = $name;
        }
    }
}

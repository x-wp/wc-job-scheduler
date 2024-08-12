<?php

namespace XWC\Queue;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Job {

    protected string $classname;

    public function __construct(
        public readonly string $hook,
        public readonly string $group = '',
        public readonly array $deps = [],
        public readonly bool $unique = false,
        public readonly int $priority = 10,
    ) {
    }

    public function set_classname(string $classname): static {
        $this->classname = $classname;

        return $this;
    }

    public function run() {

    }
}

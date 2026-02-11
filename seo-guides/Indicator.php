<?php
namespace SEOIndicator;

abstract class Indicator {
    protected string $name;

    public function __construct(string $name) {
        $this->name = $name;
    }

    public function getName(): string {
        return $this->name;
    }

    abstract public function analyze(array $data): array;
}

<?php
namespace KS;

interface PhilerConfigInterface extends ExecutableConfigInterface
{
    public function loadPhilerJson(string $json): void;
    public function getBuildDir(): string;
    public function getExecutableConfigs(): array;
    public function getIgnoreList(): array;
    public function getOptionalList(): array;
}


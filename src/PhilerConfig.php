<?php
namespace KS;

class PhilerConfig extends ExecutableConfig implements PhilerConfigInterface
{
    public function loadPhilerJson(string $philerFile): void
    {
        if (!file_exists($philerFile)) {
            throw new \InvalidConfigException("Missing philer.hjson local config file.");
        }

        $config = json_decode(file_get_contents($philerFile), true);
        if (!is_array($config)) {
            throw new InvalidConfigException("The JSON passed to `loadPhilerJson` appears to be invalid!");
        }
        $this->config = array_merge_recursive($this->config, $config);
    }

    public function getBuildDir(): string
    {
        return $this->get('build-dir');
    }

    public function getExecutableConfigs(): array
    {
        return $this->get('executables');
    }

    public function getIgnoreList(): array
    {
        return isset($this->config['ignore']) ? $this->config['ignore'] : [];
    }

    public function getOptionalList(): array
    {
        return isset($this->config['optional']) ? $this->config['optional'] : [];
    }

    public function getLogIdentifier(): string
    {
        return isset($this->config['log-identifier']) ? $this->config['log-identifier'] : 'Philer';
    }

    public function getLogLevel(): int
    {
        return isset($this->config['log-level']) ? $this->config['log-level'] : LOG_ERR;
    }
}


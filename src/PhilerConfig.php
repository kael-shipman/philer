<?php
namespace KS;

class PhilerConfig extends ExecutableConfig implements PhilerConfigInterface
{
    private $hjsonParser;

    public function loadPhilerJson(string $philerFile): void
    {
        if (!file_exists($philerFile)) {
            throw new InvalidConfigException("Missing philer.hjson local config file.");
        }

        $config = $this->parseConfig(file_get_contents($philerFile));
        if (!is_array($config)) {
            throw new InvalidConfigException("The HJSON passed to `loadPhilerJson` appears to be invalid!");
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

    protected function getHjsonParser(): \HJSON\HJSONParser
    {
        if (!$this->hjsonParser) {
            $this->hjsonParser = new \HJSON\HJSONParser();
        }
        return $this->hjsonParser;
    }

    protected function parseConfig(string $config): array
    {
        $parser = $this->getHjsonParser();
        return $parser->parse($config, ['assoc' => true]);
    }
}


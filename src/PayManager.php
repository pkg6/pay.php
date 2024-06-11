<?php

namespace Pkg6\Pay;

use Closure;
use InvalidArgumentException;
use Pkg6\Pay\Contracts\ProviderInterface;

class PayManager
{
    /**
     * @var Config
     */
    protected $config;

    protected $resolved       = [];
    protected $customCreators = [];
    protected $providers      = [

    ];

    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    /**
     * @param Config $config
     *
     * @return $this
     */
    public function config(Config $config)
    {
        $this->config = $config;

        return $this;
    }
    /**
     * @param string $name
     *
     * @return ProviderInterface
     */
    public function create(string $name)
    {
        $name = strtolower($name);

        if (!isset($this->resolved[$name])) {
            $this->resolved[$name] = $this->createProvider($name);
        }

        return $this->resolved[$name];
    }
    /**
     * @param string   $name
     * @param \Closure $callback
     *
     * @return $this
     */
    public function extend(string $name, Closure $callback)
    {
        $this->customCreators[strtolower($name)] = $callback;

        return $this;
    }
    /**
     * @return ProviderInterface[]
     */
    public function getResolvedProviders(): array
    {
        return $this->resolved;
    }
    /**
     * @param string $provider
     * @param array  $config
     *
     * @return ProviderInterface
     */
    public function buildProvider(string $provider, array $config)
    {
        return new $provider($config);
    }
    /**
     * @param string $name
     *
     * @return ProviderInterface
     * @throws \InvalidArgumentException
     *
     */
    protected function createProvider(string $name)
    {
        $config = $this->config->get($name, []);
        $provider = $config['provider'] ?? $name;

        if (isset($this->customCreators[$provider])) {
            return $this->callCustomCreator($provider, $config);
        }

        if (!$this->isValidProvider($provider)) {
            throw new InvalidArgumentException("Provider [$provider] not supported.");
        }

        return $this->buildProvider($this->providers[$provider] ?? $provider, $config);
    }
    /**
     * @param string $driver
     * @param array  $config
     *
     * @return ProviderInterface
     */
    protected function callCustomCreator(string $driver, array $config): ProviderInterface
    {
        return $this->customCreators[$driver]($config);
    }

    /**
     * @param string $provider
     *
     * @return bool
     */
    protected function isValidProvider(string $provider): bool
    {
        return isset($this->providers[$provider]) || is_subclass_of($provider, ProviderInterface::class);
    }
}

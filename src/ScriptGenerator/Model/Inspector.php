<?php

namespace EloquentJs\ScriptGenerator\Model;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;

class Inspector
{
    /**
     * @var array
     */
    protected $excludeScopes = ['scopeScope', 'scopeEloquentJs'];

    /**
     * @var Repository
     */
    protected $config;

    /**
     * Create a new Inspector instance.
     *
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Inspect a model class and return its metadata.
     *
     * @param Model $instance
     * @return Metadata
     */
    public function inspect(Model $instance)
    {
        return new Metadata(
            class_basename($instance),
            $this->findEndpoint($instance),
            $this->findDateColumns($instance),
            $this->findScopeMethods($instance)
        );
    }

    /**
     * Find the endpoint for this model.
     *
     * @param Model $instance
     * @return string|null
     */
    protected function findEndpoint(Model $instance)
    {
        if ($instance->getEndpoint()) {
            return $instance->getEndpoint();
        }

        return $this->readModelConfig($instance, 'endpoint');
    }

    /**
     * Get any additional date columns for this model.
     *
     * @param Model $instance
     * @return array
     */
    protected function findDateColumns(Model $instance)
    {
        return array_values(
            array_diff($instance->getDates(), ['created_at', 'updated_at', 'deleted_at'])
        );
    }

    /**
     * Get the scope methods for this model with 'scope' prefix removed.
     *
     * @param Model $instance
     * @return array
     */
    protected function findScopeMethods(Model $instance)
    {
        return array_map(
            function($method) {
                return lcfirst(substr($method, 5));
            },
            array_values(
                array_filter(
                    get_class_methods($instance),
                    function($method) {
                        return substr($method, 0, 5) === 'scope' and ! in_array($method, $this->excludeScopes);
                    }
                )
            )
        );
    }

    /**
     * Read from a model config value from the eloquentjs.php config file.
     *
     * @param Model $instance
     * @param string $key
     * @return mixed
     */
    protected function readModelConfig(Model $instance, $key)
    {
        $className = get_class($instance);

        return $this->config->get("eloquentjs.generator.{$className}.{$key}");
    }
}

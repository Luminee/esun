<?php

namespace Luminee\Esun\Eloquent;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Luminee\Esun\Query\Builder as QueryBuilder;

class Builder
{
    /**
     * The base query builder instance.
     *
     * @var \Luminee\Esun\Query\Builder
     */
    protected $query;

    /**
     * The model being queried.
     *
     * @var \Luminee\Esun\Eloquent\Model
     */
    protected $model;

    /**
     * Create a new Eloquent query builder instance.
     *
     * @param  \Luminee\Esun\Query\Builder $query
     * @return void
     */
    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * Create a collection of models from plain arrays.
     *
     * @param  array $items
     * @return \Illuminate\Support\Collection
     */
    public function hydrate(array $items)
    {
        $instance = $this->newModelInstance();

        return $instance->newCollection(array_map(function ($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $items));
    }

    /**
     * Get the first record matching the attributes or create it.
     *
     * @param  array $attributes
     * @param  array $values
     * @return \Luminee\Esun\Eloquent\Model
     */
    public function firstOrCreate(array $attributes, array $values = [])
    {
        if (!is_null($instance = $this->where($attributes)->first())) {
            return $instance;
        }

        return $this->create($attributes + $values);
    }

    /**
     * Get a single column's value from the first result of a query.
     *
     * @param  string $column
     * @return mixed
     */
    public function value($column)
    {
        if ($result = $this->select($column)->first()) {
            return $result->{$column};
        }
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @return \Illuminate\Support\Collection|static[]
     */
    public function get()
    {
        $models = $this->hydrate($this->query->get()->all());

        return collect($models);
    }

    /**
     * @return \Luminee\Esun\Eloquent\Model|null
     */
    public function first()
    {
        if (count($result = $this->take(1)->get()) == 0) {
            return null;
        }
        return $result[0];
    }

    public function find($id)
    {
        $this->where($this->model->getKeyName(), '=', $id);
        return $this->first();
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param  string $column
     * @param  string|null $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = null)
    {
        $results = $this->toBase()->pluck($column, $key);

        return $results->map(function ($value) use ($column) {
            return $this->model->newFromBuilder([$column => $value])->{$column};
        });
    }

    /**
     * Paginate the given query.
     *
     * @param  int $perPage
     * @param  int|null $page
     * @return \Illuminate\Support\Collection
     *
     * @throws \InvalidArgumentException
     */
    public function paginate($perPage = null, $page = 1)
    {
        $pagination = $this->toBase()->paginate($perPage, $page);
        $pagination['data'] = $this->hydrate($pagination['data']->all());
        return $pagination;
    }

    /**
     * Save a new model and return the instance.
     *
     * @param  array $attributes
     * @return \Luminee\Esun\Eloquent\Model|$this
     */
    public function create(array $attributes = [])
    {
        $object = $this->toBase()->create($attributes);

        return $this->newModelInstance(array_merge($attributes, ['_id' => $object->_id]));
    }

    /**
     * Update a record in the database.
     *
     * @param  array $values
     * @return int
     */
    public function update(array $values)
    {
        return $this->toBase()->update($this->getModelId(), $this->addUpdatedAtColumn($values));
    }

    /**
     * Add the "updated at" column to an array of values.
     *
     * @param  array $values
     * @return array
     */
    protected function addUpdatedAtColumn(array $values)
    {
        if (!$this->model->usesTimestamps()) {
            return $values;
        }

        return Arr::add(
            $values, $this->model->getUpdatedAtColumn(),
            $this->model->freshTimestampString()
        );
    }

    /**
     * Delete a record from the database.
     *
     * @return mixed
     */
    public function delete()
    {
        return $this->toBase()->delete($this->getModelId());
    }

    /**
     * Create a new instance of the model being queried.
     *
     * @param  array $attributes
     * @return \Luminee\Esun\Eloquent\Model
     */
    public function newModelInstance($attributes = [])
    {
        return $this->model->newInstance($attributes);
    }

    /**
     * @param array $models
     * @return Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Get the underlying query builder instance.
     *
     * @return \Luminee\Esun\Query\Builder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set the underlying query builder instance.
     *
     * @param  \Luminee\Esun\Query\Builder $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get a base query builder instance.
     *
     * @return \Luminee\Esun\Query\Builder
     */
    public function toBase()
    {
        return $this->getQuery();
    }

    /**
     * Get the model instance being queried.
     *
     * @return \Luminee\Esun\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    public function getModelId()
    {
        $keyName = $this->model->getKeyName();

        return $this->model->$keyName;
    }

    /**
     * Set a model instance for the model being queried.
     *
     * @param  \Luminee\Esun\Eloquent\Model $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        $this->query->table($model->getTable());

        return $this;
    }

    /**
     * Qualify the given column name by the model's table.
     *
     * @param  string $column
     * @return string
     */
    public function qualifyColumn($column)
    {
        return $this->model->qualifyColumn($column);
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $this->query->{$method}(...$parameters);

        return $this;
    }

    /**
     * Force a clone of the underlying query builder when cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->query = clone $this->query;
    }
}

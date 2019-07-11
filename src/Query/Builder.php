<?php

namespace Luminee\Esun\Query;

use Luminee\Esun\Core\Url;
use Luminee\Esun\Core\Data;
use Luminee\Esun\Core\Response;

class Builder
{
    /**
     * @var Connector $connector
     */
    protected $connector;

    protected $processor;

    protected $table;

    protected $type;

    protected $_id;

    protected $wheres = [];

    protected $url;

    protected $data = '';

    public function __construct($processor, $connector)
    {
        $this->processor = $processor;
        $this->connector = $connector;
    }

    /**
     * @param $table
     * @return $this
     */
    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    public function find($id)
    {
        $this->_id = $id;
        $this->url = Url::findUrl($this->getUri(), $this->_id);
        return $this->response('find', 'get');
    }

    public function get()
    {
        $this->url = Url::searchUrl($this->getUri());
        $this->data = Data::toJson($this->wheres);
        return $this->response('get', 'get');
    }

    /**
     * @param $id
     * @param $data
     * @return mixed
     */
    public function create($id, $data)
    {
        $this->url = Url::createUrl($this->getUri(), $id);
        $this->data = Data::toJson($data);
        return $this->response('create', 'put');
    }

    /**
     * @param $data
     * @return mixed
     */
    public function insert($data)
    {
        $this->url = Url::insertUrl($this->getUri());
        $this->data = Data::toJson($data);
        return $this->response('insert');
    }

    /**
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id, $data)
    {
        $this->url = Url::updateUrl($this->getUri(), $id);
        $this->data = Data::toJson($data, true);
        return $this->response('update');
    }

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->url = Url::deleteUrl($this->getUri(), $id);
        return $this->response('delete', 'delete');
    }

    // Protected

    protected function response($function, $method = 'post')
    {
        $response = $this->processor->$method($this->url, $this->data);
        return Response::$function($response);
    }

    protected function getUri()
    {
        return $this->connector->getUri($this->table, $this->type);
    }


}
<?php

namespace Luminee\Esun;

use Luminee\Esun\Core\Url;
use Luminee\Esun\Core\Curl;
use Luminee\Esun\Core\Data;
use Luminee\Esun\Core\Config;
use Luminee\Esun\Core\Response;
use Luminee\Esun\Core\Connector;

class Builder
{
    /**
     * @var Connector $connector
     */
    protected $connector;

    protected $curl;

    protected $table;

    protected $url;

    protected $data = '';

    public function __construct()
    {
        $this->curl = Curl::init();
        $config = Config::getConfig();
        $this->connector = Connector::init($config);
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

    /**
     * @param $id
     * @param $data
     * @return mixed
     */
    public function create($id, $data)
    {
        $this->url = Url::createUrl($this->getUri(), $id);
        $this->data = Data::toJson($data);
        return $this->response('create', 'curlPut');
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
        return $this->response('delete', 'curlDelete');
    }

    // Protected

    protected function response($function, $method = 'curlPost')
    {
        $response = $this->curl->$method($this->url, $this->data);
        return Response::$function($response);
    }

    protected function getUri()
    {
        return $this->connector->getUri() . '/' . $this->table;
    }


}
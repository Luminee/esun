<?php

namespace Luminee\Esun;

class Esun
{

    protected $baseUrl;
    protected $curlSession;
    protected $header;

    protected $index;
    protected $type;
    protected $url;
    private $filterJson;
    private $filterMustJson;
    /**
     * 通过构造方法构建
     *
     * @param string $url 传入要搜索的 '索引/类型/文档'
     * @param string $method GET | PUT | DELETE
     * @param string $data 接收json字符串，与$method配合使用
     * @return json $document 返回查询或者插入的值
     */

    public function __construct($index, $type)
    {
//        if (!config('app.debug')) {
//            $index = md5($index);
//        }
        $this->index   = $index;
        $this->type    = $type;
        $this->header  = array('Content-Type: application/json');
        $this->baseUrl = config('elasticsearch.connections.default.hosts')[0];
        $this->url     = $this->baseUrl.'/'.$this->index.'/'.$this->type;
    }


    public function createMapping($json_data)
    {
        $mappingUrl = $this->baseUrl.'/'.$this->index.'/_mapping'.'/'.$this->type;
        return $this->elastic_search($mappingUrl, 'put', $json_data);
    }

    public function searchById($id){
        $url = $this->url.'/'.$id;
        return $this->elastic_search($url);
    }



    public function search($json_dsl_rule)
    {
        $url = $this->url.'/_search';
        return $this->elastic_search($url, 'get', $json_dsl_rule);
    }




    /**
     * 调用elasticsearch接口进行全文检索方法
     *
     * @param string $url 传入要搜索的 '索引/类型/文档'
     * @param string $method GET | PUT | DELETE
     * @param string $data 接收json字符串，与$method配合使用
     * @return json $document 返回查询或者插入的值
     */

    public function elastic_search($url, $method = 'get', $data = "")
    {
        $this->curlSession = curl_init();
        curl_setopt($this->curlSession, CURLOPT_URL, $url); //设置请求的URL
        curl_setopt($this->curlSession, CURLOPT_RETURNTRANSFER, true); //设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        curl_setopt($this->curlSession, CURLOPT_CUSTOMREQUEST, strtoupper($method)); //设置请求方式
        curl_setopt($this->curlSession, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($this->curlSession, CURLOPT_TIMEOUT, 3);
        if (!empty($data)) {
            curl_setopt($this->curlSession, CURLOPT_POSTFIELDS, $data);//设置提交的字符串
        }
        $document = curl_exec($this->curlSession);//执行预定义的CURL
        curl_close($this->curlSession);
        return $document;
    }












}
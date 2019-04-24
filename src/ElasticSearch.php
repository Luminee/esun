<?php

namespace Luminee\Esun;

class ElasticSearch
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

    private function maxFavorNum()
    {
        return Testbank::max('favorite_num');
    }

    private function maxPowerMark()
    {
        return Testbank::max('power_mark');
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

    public function create($id, $json_data)
    {
        $url = $this->url;
        if ($id){
            $url .= '/'.$id;
        }
        return $this->elastic_search($url, $method = "post", $data = $json_data);
    }


    public function createIndex($type = 'all'){
        $url = $this->baseUrl.'/'.$this->index;
        $method = "put";
        switch ($type){
            case 'all':
                $data = self::getCreatedIndexJson(['testbank','bill','test']);
                break;
            default:
                $data = self::getCreatedIndexJson(['testbank','bill','test']);
        }

        return $this->elastic_search($url, $method, $data);
    }

    protected function getCreatedIndexJson($sourceTypes){
        $sourceTypeJsons=[];
        foreach ($sourceTypes as $sourceType){
            $sourceTypeJsons[] = self::getCreatedIndexJsonItem($sourceType,['name','es_name']);
        }

        $json =
            '{
                "mappings" : 
                {'
            .implode(',',$sourceTypeJsons).
            '}
            }';

        return $json;
    }

    //ik5.0以后分ik_smart和ik_max_word(ik_smart拆分粗化，ik_max_word 拆分细化)
    protected function getCreatedIndexJsonItem($sourceType,$keys){
        $keyJsons=[];
        foreach ($keys as $key){
            $keyJsons[] =
                '
                    "'.$key.'" : 
                    {
                        "type" : "string",
                        "analyzer" : "ik_max_word",
                        "search_analyzer": "ik_max_word",
                        "index_options": "docs",
                        "norms":{"enabled": false}
                    }
                '
            ;
        }
        $json =
            '
                "'.$sourceType.'" : 
                {
                    "dynamic" : true,
                    "properties" : 
                    {
                        '.implode(',',$keyJsons).'
                    }
                }
            ';
        return $json;
    }

    public function creates($items){
        $url = $this->baseUrl.'/'.'_bulk';
        $createText="";
        foreach ($items as $k=>$v){
            if (empty(trim($v['id']))){
                $createText=$createText.'{"index":{"_index":"'.$this->index.'","_type":"'.$this->type.'"}}'."\n";
            }else{
                $createText=$createText.'{"index":{"_index":"'.$this->index.'","_type":"'.$this->type.'","_id":"'.$v['id'].'"}}'."\n";
            }
            $createText.=$v['json']."\n";
        }

        return $this->elastic_search($url,"post",$createText);
    }

    public function bluks($items){
        $url = $this->baseUrl.'/'.'_bulk';
        $blukText="";
        foreach ($items as $k=>$v){
            switch ($v['handle']){
                case 'create':
                    if (empty(trim($v['id']))){
                        $blukText=$blukText.'{"index":{"_index":"'.$this->index.'","_type":"'.$this->type.'"}}'."\n";
                    }else{
                        $blukText=$blukText.'{"index":{"_index":"'.$this->index.'","_type":"'.$this->type.'","_id":"'.$v['id'].'"}}'."\n";
                    }
                    $blukText.=$v['json']."\n";
                    break;
                case 'delete':
                    $blukText=$blukText.'{"delete":{"_index":"'.$this->index.'","_type":"'.$this->type.'","_id":"'.$v['id'].'"}}'."\n";
                    break;
            }
        }
        return $this->elastic_search($url,"post",$blukText);
    }

    public function delete($id)
    {
        $url = $this->url.'/'.$id;
        return $this->elastic_search($url, 'delete');
    }

    public function deleteIndex($index)
    {
        $delete_index_url = $this->baseUrl.'/'.$index;
        return $this->elastic_search($delete_index_url, 'delete');
    }

    public function search($json_dsl_rule)
    {
        $url = $this->url.'/_search';
        return $this->elastic_search($url, 'get', $json_dsl_rule);
    }

    public function search_all($page_size, $page_from)
    {
        $url = $this->url.'/_search';
        $dsl_rule
            = '{
           "from" : '.$page_from.', "size" :'.$page_size.',
            "query": {
                "match_all": { "boost" : 1.2 }
            },
             "sort" : [
                { "create_time" : {"order" : "desc"}}
            ]
        }';
        return $this->elastic_search($url, 'get', $dsl_rule);
    }

    public function update($id, $json_data)
    {
        $url = $this->url.'/'.$id.'/_update';
        $dsl_template
            = '{
            "doc":'.$json_data.'
        }';
        return $this->elastic_search($url, 'post', $dsl_template);
    }

    //TODO 未测试通过
    public function updates($items){
        $url = $this->baseUrl.'/'.'_bulk';
        $updateText="";
        foreach ($items as $k=>$v){
            $updateText=$updateText.'{"update":{"_index":"'.$this->index.'","_type":"'.$this->type.'","_id":"'.$v['id'].'"}}'."\n";
            $updateText.=$v['json']."\n";
        }

        return $this->elastic_search($url,"post",$updateText);
    }

    public function vanthink_testbank_search($searchData,$scope, $page_size, $page_from, $game_id = 0,$game_type_id = 0)
    {
        $url            = $this->url.'/_search?preference=_primary_first';

        //热词
        $hotwords = [];
        $hotWordsList = HotWords::get();
        foreach ($hotWordsList as $hotWords) {
            $hotWordsArr=explode(',',$hotWords->keywords);
            foreach ($hotWordsArr as $hotWord){
                if (mb_strstr(StringHelper::fixBackslash($searchData),$hotWord)){
                    $hotwords[]='hw'.$hotWords->id;
                    break;
                }
            }
        }

        $filterMustJsons=[];
        $filterMustNotJsons =[];
        if(!empty($game_id)){
            $filterMustJsons[] = '{"match_phrase": {"game_id" : "'.$game_id.'"}}';
        }
        if($game_type_id > 0){
            $filterMustJsons[] = '{"match_phrase": {"game_type_id" : '.$game_type_id.'}}';
        }
        if (count($hotwords)>0){
            foreach ($hotwords as $hotword){
                $filterMustJsons[] = '{"match_phrase": {"hot_words" : "'.$hotword.'"}}';
            }
        }
        if (!empty($scope['system_label_ids'])){
            foreach ($scope['system_label_ids'] as $system_label_id){
                $filterMustJsons[] = '{"match_phrase": {"system_label_ids" : "s'.$system_label_id.'"}}';
            }
        }
        if ($scope['type'] == 'all'){
            $filterMustNotJsons = self::getPrivateAccounts($filterMustNotJsons);
            $filterMustJsons[] = '{"match_phrase": {"type" : "testbank"}}';
            $filterMustJsons[] = '{"match_phrase": {"is_public" : 1}}';
        }
        if ($scope['type'] == 'school'){
            $filterMustJsons[] = '{"match_phrase": {"school_ids" : "s'.$scope['school_id'].'"}}';
        }
        if ($scope['type'] == 'send'){
            $filterMustJsons[] = '{"match_phrase": {"school_ids" : "s'.$scope['school_id'].'"}}';
            $filterMustJsons[] = '{"match_phrase": {"send_account_ids" : "s'.$scope['account_id'].'"}}';
        }
        if ($scope['type'] == 'favor'){
            $filterMustJsons[] = '{"match_phrase": {"favor_account_ids" : "f'.$scope['account_id'].'"}}';
            if ($scope['custom_label_ids'] !== null && count($scope['custom_label_ids'])>0) {
                foreach ($scope['custom_label_ids'] as $custom_label_id){
                    $filterMustJsons[] = '{"match_phrase": {"favor_custom_label_ids" : "fc'.$custom_label_id.'"}}';
                }
            }
        }
        if ($scope['type'] == 'custom'){
            $filterMustJsons[] = '{"match_phrase": {"account.id" : '.$scope['account_id'].'}}';
            $filterMustJsons[] = '{"match_phrase": {"own_origin":0}}';
            if ($scope['custom_label_ids'] !== null && count($scope['custom_label_ids'])>0) {
                //我的自创
                if (in_array(-2,$scope['custom_label_ids'])){
                    $filterMustJsons[] = '{"match_phrase": {"type":"testbank"}}';
                    goto custom_end;
                }
                //我的引用
                if (in_array(-1,$scope['custom_label_ids'])){
                    $filterMustJsons[] = '{"match_phrase": {"type":"quotedTestbank"}}';
                    goto custom_end;
                }
                //已加入公共题库
                if (in_array(-3,$scope['custom_label_ids'])){
                    $filterMustJsons[] = '{"match_phrase": {"type":"testbank"}}';
                    $filterMustJsons[] = '{"match_phrase": {"is_public":1}}';
                    goto custom_end;
                }
                foreach ($scope['custom_label_ids'] as $custom_label_id){
                    $filterMustJsons[] = '{"match_phrase": {"custom_label_ids" : "c'.$custom_label_id.'"}}';
                }
                custom_end:
            }
        }
        $filterMustJson     = count($filterMustJsons)>0?implode(',',$filterMustJsons):'';
        $filterMustNotJson  = count($filterMustNotJsons)>0?implode(',',$filterMustNotJsons):'';

        $search_rule_hotwords
            = '{
            "from" : '.$page_from.', "size" :'.$page_size.',
            "query": {
                "function_score": {
                  "query": {
					"bool": {
					  "should": [
						{ "match": { "es_name": "'.StringHelper::formatESname($searchData).'" }}
					  ],
					  "must": [
                                '.$filterMustJson.'
					  ],
					  "must_not": [
						'.$filterMustNotJson.'
					  ]
					}
				  },
                  "functions": [
                        {
                          "filter": { "match": { "es_name": "'.StringHelper::formatESname($searchData).'" } },
                          "filter": { "match": { "is_recommend": 1 } },
                          "script_score": {
                               "script": {
                                    "lang": "painless",
                                    "inline": "1.01"
                               }
                            }
                      }
                  ],
                    "score_mode":"sum"
                }
            }
       }';

//return $search_rule_hotwords;
        return $this->elastic_search($url, 'post', $search_rule_hotwords);
    }

    public function vanthink_bill_search($searchData, $scope, $page_size, $page_from)
    {
        $url            = $this->url.'/_search?preference=_primary_first';

        //热词
        $hotwords = [];
        $hotWordsList = HotWords::get();
        foreach ($hotWordsList as $hotWords) {
            $hotWordsArr=explode(',',$hotWords->keywords);
            foreach ($hotWordsArr as $hotWord){
                if (mb_strstr(StringHelper::fixBackslash($searchData),$hotWord)){
                    $hotwords[]='hw'.$hotWords->id;
                    break;
                }
            }
        }

        $filterMustJsons=[];
        $filterMustNotJsons =[];

        if (!empty($scope['system_label_ids'])){
            foreach ($scope['system_label_ids'] as $system_label_id){
                $filterMustJsons[] = '{"match_phrase": {"system_label_ids" : "s'.$system_label_id.'"}}';
            }
        }
        if (count($hotwords)>0){
            foreach ($hotwords as $hotword){
                $filterMustJsons[] = '{"match_phrase": {"hot_words" : "'.$hotword.'"}}';
            }
        }
        if ($scope['type'] == 'all'){
            $filterMustNotJsons = self::getPrivateAccounts($filterMustNotJsons);
            $filterMustJsons[] = '{"match_phrase": {"is_public" : 1}}';
        }
        if ($scope['type'] == 'school'){
            $filterMustJsons[] = '{"match_phrase": {"school_ids" : "s'.$scope['school_id'].'"}}';
        }
        if ($scope['type'] == 'send'){
            $filterMustJsons[] = '{"match_phrase": {"school_ids" : "s'.$scope['school_id'].'"}}';
            $filterMustJsons[] = '{"match_phrase": {"send_account_ids" : "s'.$scope['account_id'].'"}}';
        }
        if ($scope['type'] == 'favor'){
            $filterMustJsons[] = '{"match_phrase": {"favor_account_ids" : "f'.$scope['account_id'].'"}}';
            if ($scope['custom_label_ids'] !== null && count($scope['custom_label_ids'])>0) {
                foreach ($scope['custom_label_ids'] as $custom_label_id){
                    $filterMustJsons[] = '{"match_phrase": {"favor_custom_label_ids" : "fc'.$custom_label_id.'"}}';
                }
            }
        }
        if ($scope['type'] == 'custom'){
            $filterMustJsons[] = '{"match_phrase": {"account.id" : '.$scope['account_id'].'}}';
            if ($scope['custom_label_ids'] !== null && count($scope['custom_label_ids'])>0) {
                foreach ($scope['custom_label_ids'] as $custom_label_id){
                    $filterMustJsons[] = '{"match_phrase": {"custom_label_ids" : "c'.$custom_label_id.'"}}';
                }
            }
        }
        $filterMustJson     = count($filterMustJsons)>0?implode(',',$filterMustJsons):'';
        $filterMustNotJson  = count($filterMustNotJsons)>0?implode(',',$filterMustNotJsons):'';

        $search_rule_hotwords
            = '{
            "from" : '.$page_from.', "size" :'.$page_size.',
            "query": {
                "function_score": {
                  "query": {
					"bool": {
					  "should": [
						{ "match": { "es_name": "'.StringHelper::formatESname($searchData).'" }}
					  ],
					  "must": [
                                '.$filterMustJson.'
					  ],
					  "must_not": [
						'.$filterMustNotJson.'
					  ]
					}
				  },
                  "functions": [
                        {
                          "filter": { "match": { "es_name": "'.StringHelper::formatESname($searchData).'" } },
                          "filter": { "match": { "is_recommend": 1 } },
                          "script_score": {
                               "script": {
                                    "lang": "painless",
                                    "inline": "1.01"
                               }
                            }
                      }
                  ],
                    "score_mode":"sum"
                }
            }
       }';
        return $this->elastic_search($url, 'post', $search_rule_hotwords);
    }

    public function vanthink_test_search($searchData, $scope, $page_size, $page_from)
    {
        $url            = $this->url.'/_search?preference=_primary_first';

        //热词
        $hotwords = [];
        $hotWordsList = HotWords::get();
        foreach ($hotWordsList as $hotWords) {
            $hotWordsArr=explode(',',$hotWords->keywords);
            foreach ($hotWordsArr as $hotWord){
                if (mb_strstr(StringHelper::fixBackslash($searchData),$hotWord)){
                    $hotwords[]='hw'.$hotWords->id;
                    break;
                }
            }
        }

        $filterMustJsons=[];
        $filterMustNotJsons =[];
        if(!empty($system_labels)){
            foreach (explode(',',$system_labels) as $systemLabel){
                $filterMustJsons[] = '{"match_phrase": {"system_labels" : "'.$systemLabel.'"}}';
            }
        }
        if (count($hotwords)>0){
            foreach ($hotwords as $hotword){
                $filterMustJsons[] = '{"match_phrase": {"hot_words" : "'.$hotword.'"}}';
            }
        }
        if (!empty($scope['system_label_ids'])){
            foreach ($scope['system_label_ids'] as $system_label_id){
                $filterMustJsons[] = '{"match_phrase": {"system_label_ids" : "s'.$system_label_id.'"}}';
            }
        }
        if ($scope['type'] == 'all'){
            $filterMustNotJsons = self::getPrivateAccounts($filterMustNotJsons);
            $filterMustJsons[] = '{"match_phrase": {"type" : "test"}}';
            $filterMustJsons[] = '{"match_phrase": {"is_public" : 1}}';
        }
        if ($scope['type'] == 'school'){
            $filterMustJsons[] = '{"match_phrase": {"school_ids" : "s'.$scope['school_id'].'"}}';
        }
        if ($scope['type'] == 'send'){
            $filterMustJsons[] = '{"match_phrase": {"school_ids" : "s'.$scope['school_id'].'"}}';
            $filterMustJsons[] = '{"match_phrase": {"send_account_ids" : "s'.$scope['account_id'].'"}}';
        }
        if ($scope['type'] == 'favor'){
            $filterMustJsons[] = '{"match_phrase": {"favor_account_ids" : "f'.$scope['account_id'].'"}}';
            if ($scope['custom_label_ids'] !== null && count($scope['custom_label_ids'])>0) {
                foreach ($scope['custom_label_ids'] as $custom_label_id){
                    $filterMustJsons[] = '{"match_phrase": {"favor_custom_label_ids" : "fc'.$custom_label_id.'"}}';
                }
            }
        }
        if ($scope['type'] == 'custom'){
            $filterMustJsons[] = '{"match_phrase": {"account.id" : '.$scope['account_id'].'}}';
//            $filterMustJsons[] = '{"match_phrase": {"own_origin":0}}';
            if ($scope['custom_label_ids'] !== null && count($scope['custom_label_ids'])>0) {
                //我的自创
                if (in_array(-2,$scope['custom_label_ids'])){
                    $filterMustJsons[] = '{"match_phrase": {"type":"test"}}';
                    goto custom_end;
                }
                //我的引用
                if (in_array(-1,$scope['custom_label_ids'])){
                    $filterMustJsons[] = '{"match_phrase": {"type":"exam"}}';
                    goto custom_end;
                }
                //已加入公共题库
                if (in_array(-3,$scope['custom_label_ids'])){
                    $filterMustJsons[] = '{"match_phrase": {"type":"test"}}';
                    $filterMustJsons[] = '{"match_phrase": {"is_public":1}}';
                    goto custom_end;
                }
                foreach ($scope['custom_label_ids'] as $custom_label_id){
                    $filterMustJsons[] = '{"match_phrase": {"custom_label_ids" : "c'.$custom_label_id.'"}}';
                }
                custom_end:
            }
        }
        $filterMustJson     = count($filterMustJsons)>0?implode(',',$filterMustJsons):'';
        $filterMustNotJson  = count($filterMustNotJsons)>0?implode(',',$filterMustNotJsons):'';

        $search_rule_hotwords
            = '{
            "from" : '.$page_from.', "size" :'.$page_size.',
            "query": {
                "function_score": {
                  "query": {
					"bool": {
					  "should": [
						{ "match": { "es_name": "'.StringHelper::formatESname($searchData).'" }}
					  ],
					  "must": [
                                '.$filterMustJson.'
					  ],
					  "must_not": [
						'.$filterMustNotJson.'
					  ]
					}
				  },
                  "functions": [
                      {
                          "filter": { "match": { "es_name": "'.StringHelper::formatESname($searchData).'" } },
                          "filter": { "match": { "is_recommend": 1 } },
                          "script_score": {
                               "script": {
                                    "lang": "painless",
                                    "inline": "1.01"
                               }
                            }
                      }
                  ],
                    "score_mode":"sum"
                }
            }
       }';

        return $this->elastic_search($url, 'post', $search_rule_hotwords);
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

    protected function getPrivateAccounts($mustNotJsons){
        $limitedAccounts=PrivateTestbankHelper::getPrivateUser();
        foreach ($limitedAccounts as $limitedAccount){
            $mustNotJsons[] = '{"term": {"account.id" : '.$limitedAccount.'}}';
        }
        return $mustNotJsons;
    }

    /**
     * 管理端查询ES
     * @author LuminEe
     */
    public function listForManage($page, $pageSize, $queries)
    {
        $queryStrings = [];
        if (!empty($queries) && count($queries) > 0) {
            foreach ($queries as $key => $value) {
                $queryStrings[] = '{"query_string":{"default_field":"'.$key.'","query":"'.$value.'"}}';
            }
        }
        $str
            = '{
              "from" : '.($page - 1) * $pageSize.',
               "size" :'.$pageSize.',
              "query": {
                "bool": {
                  "must": [
                     '.implode(',', $queryStrings).'
                  ]
                }
              }
        }';
        return $this->elastic_search($this->url.'/_search', 'post', $str);
    }

    public static function getGameList(){
        if ($gameList = RedisHelper::getRedis(cfg('redis_all_game'))){
            $gameList  = json_decode($gameList);
        }else{
            $gameList   = Game::get();
            $gameList   = json_encode($gameList);
            RedisHelper::setRedis(cfg('redis_all_game'),$gameList,86400);
            $gameList  = json_decode($gameList);
        }

        return collect($gameList);
    }

    public static function getLabelList(){
//        if ($labelList = RedisHelper::getRedis(cfg('redis_all_active_label'))){
//            $labelList = json_decode($labelList);
//        }else{
            $labelList  = Label::where('is_active','=',1)->where('label_type_id',1)->get();
            $labelList  = json_encode($labelList);
//            RedisHelper::setRedis(cfg('redis_all_active_label'),$labelList,86400);
            $labelList = json_decode($labelList);
//        }
        return collect($labelList);
    }

    public static function getHotWordsList(){
        if ($hotWordsList = RedisHelper::getRedis(cfg('redis_all_hotwords'))){
            $hotWordsList  = json_decode($hotWordsList);
        }else{
            $hotWordsList   = HotWords::get();
            $hotWordsList   = json_encode($hotWordsList);
            RedisHelper::setRedis(cfg('redis_all_hotwords'),$hotWordsList,86400);
            $hotWordsList  = json_decode($hotWordsList);
        }
        return collect($hotWordsList);
    }

    /**
     * 增/改
     * @param $tpye
     * testbank | quotedTestbank | bill | test |exam
     * @param $cons
     * [
     *  'ids'           => [1,2,3,4],
     *  'id_between'    => [],
     *  'game_ids'      => [],
     *  'game_type_ids' => [],
     *  'modes'         => [],
     *  'game_mode_ids' => [],
     *  'account_ids'   => [],
     *  's_label_ids'   => [],
     *  'c_label_ids'   => [],
     *  'limit'   => []
     * ]
     * @param $isNew
     * 是否是新的mysql数据（如教师创建题调用传true,修改题调用传false）
     * @return array
     */
    public static function fixES($type,$cons,$isNew = false){
        //从数据库中查资源
        $list = self::getResourcesByCons($type,$cons);
        $list = json_decode(json_encode($list),true);
        //将资源整理成es需要的格式
        $jsons = self::handleResourcesForES($type,$list,$isNew);
        if (count($jsons)){
            //插入es
            self::batchFixESByJsons($type,$jsons);
            //插入成功 后续处理
            self::batchFixMysqlByList($type,$list);
        }
        return array_pluck($jsons,'id');
    }

    /**
     * 根据筛选条件，从数据库中查出需要同步到es的资源list
     * @param $tpye
     * testbank | quotedTestbank | bill | test |exam
     * @param $cons
     * [
     *  'ids'           => [1,2,3,4],
     *  'id_between'    => [1,4],
     *  'game_ids'      => [],
     *  'game_type_ids' => [],
     *  'modes'         => [],
     *  'game_mode_ids' => [],
     *  'account_ids'   => [],
     *  's_label_ids'   => [],
     *  'c_label_ids'   => []
     * ]
     * @return array
     */
    private static function getResourcesByCons($type,$cons){
        $db = null;
        $fields = ['item.*'];

        /*
         * 拼接关联表sql
         */
        switch ($type){
            case 'testbank' :
                $db = DB::table('testbank as item')
                    ->useWritePdo()
                    ->join('game','item.game_id','=','game.id')
                    ->where('item.is_active', 1)
                ;
                $fields = array_merge(
                    $fields,['game.name as game_name','game.game_url','game.beta_game_url','game.game_type','game.icon']
                );
                break;
            case 'bill' :
                $db = DB::table('testbank_collection as item')
                    ->useWritePdo()
                    ->where('is_active', 1)
                ;
                break;
            case 'test' :
                $db = DB::table('test as item')
                    ->useWritePdo()
                    ->where('is_active', 1)
                ;
                break;
            case 'quotedTestbank' :
                $db = DB::table('user_quoted_testbank as item')
                    ->useWritePdo()
                ;
                $db->join('game','item.game_id','=','game.id');
                $fields = array_merge(
                    $fields,['game.name as game_name','game.game_url','game.beta_game_url','game.game_type','game.icon']
                );
                $db->join('testbank as testbank1', function ($join) {
                    $join->on('item.origin_id', '=', 'testbank1.id')
                        ->where('item.origin_type', '=' ,'testbank');
                }, null, null, 'left');
                $db->join('user_quoted_testbank as testbank2', function ($join) {
                    $join->on('item.origin_id', '=', 'testbank2.id')
                        ->where('item.origin_type', '=' ,'quotedTestbank');
                }, null, null, 'left');
                $fields []= 'testbank1.account_id as origin1_account_id';
                $fields []= 'testbank2.account_id as origin2_account_id';
                break;
            case 'exam' :
                $db = DB::table('test_quotation as item')
                    ->useWritePdo()
                ;
                $db->join('test', function ($join) {
                    $join->on('item.test_id', '=', 'test.id');
                }, null, null, 'left');
                $fields []= 'test.account_id as origin_account_id';
                break;
            default :
                return [];
        }

        $db->whereNull('item.deleted_at');
        //关联account
        $db->join('user_account as account','item.account_id','=','account.id');
        $fields []= 'account.nickname';

        /*
         * 过滤条件
         */
        if (array_key_exists('ids',$cons)){
            $db->whereIn('item.id',$cons['ids']);
        }
        if (array_key_exists('id_between',$cons)){
            $db->whereBetween('item.id',$cons['id_between']);
        }

        if (array_key_exists('game_ids',$cons) && !empty($cons['game_ids'])){
            $db->whereIn('item.game_id',$cons['game_ids']);
        }

        if (array_key_exists('game_type_ids',$cons) && !empty($cons['game_type_ids'])){
            $db->whereIn('item.game_type_id',$cons['game_type_ids']);
        }

        if (array_key_exists('modes',$cons) && !empty($cons['modes'])){
            $db->whereIn('item.mode',$cons['modes']);
        }

        if (array_key_exists('game_mode_ids',$cons) && !empty($cons['game_mode_ids'])){
            $db->whereIn('item.game_mode_id',$cons['game_mode_ids']);
        }

        if (array_key_exists('account_ids',$cons) && !empty($cons['account_ids'])){
            $db->whereIn('item.account_id',$cons['account_ids']);
        }

        if (array_key_exists('s_label_ids',$cons) && !empty($cons['s_label_ids'])){
            foreach ($cons['s_label_ids'] as $label_id){
                $db = $db->whereRaw("FIND_IN_SET('" . $label_id . "',system_label_ids)");
            }
        }

        if (array_key_exists('c_label_ids',$cons) && !empty($cons['c_label_ids'])){
            foreach ($cons['c_label_ids'] as $label_id){
                $db = $db->whereRaw("FIND_IN_SET('" . $label_id . "',custom_label_ids)");
            }
        }

        if (array_key_exists('limit',$cons) && !empty($cons['limit'])){
            $rz = $db->skip($cons['limit'][0])->take($cons['limit'][1])->get($fields);
        }else{
            $rz = $db->get($fields);
        }

        return $rz;
    }


    /**
     * 将数据库返回的list，整理成插入es需要的格式
     * @param $tpye
     *  testbank | quotedTestbank | bill | test |exam
     * @param $list
     * @return array
     */
    private static function handleResourcesForES($type,$list,$isNew){
        $labelList = self::getLabelList();
        $hotWordsList = self::getHotWordsList();
        $labels    = [];
        $accountNames   = [];

        $itemIds    = array_pluck($list,'id');
        $schools    = [];
        $sends      = [];
        $favors     = [];
        $favorCustomLabels = [];
        if (!$isNew){
            $user_app   = \App::make(UserRepository::class);
            $school_app = \App::make(SchoolRepository::class);

            $schools    = \App::call([$school_app, "listSchoolIdsFromTestbankByTypeAndItemIds_nm"],[$type,$itemIds]);
            $schools    = array_column($schools,null,'item_id');
            $sends      = \App::call([$school_app, "listTeacherIdsFromTestbankByTypeAndItemIds_nm"],[$type,$itemIds]);
            $sends      = array_column($sends,null,'item_id');
            $favors     = \App::call([$user_app, "listTeacherIdsFromFavorByTypeAndItemIds_nm"],[$type,$itemIds]);
            $favors     = array_column($favors,null,'item_id');
            $favorCustomLabels  = \App::call([$user_app, "listlabelIdsFromFavorByTypeAndItemIds_nm"],[$type,$itemIds]);
            $favorCustomLabels  = array_column($favorCustomLabels,null,'item_id');
        }

        foreach ($labelList as $label_item) {
            $labels[$label_item->id] = $label_item->name;
        }

        $testbankIds  = [];
        $jsons  = [];

        foreach ($list as &$item) {
            //is_public字段
            $extras['is_public']    = self::hr_getPublic($type,$item);
            //own_origin字段
            $extras['own_origin']   = self::hr_getOrigin($type,$item);
            //被推荐到哪些学校
            $extras['school_ids']   = '';
            if (isset($schools[$item['id']])){
                $extras['school_ids'] = self::hr_addLetter($schools[$item['id']]->school_ids,'s');
            }
            //被谁推荐
            $extras['send_account_ids'] = '';
            if (isset($sends[$item['id']])){
                $extras['send_account_ids'] = self::hr_addLetter($sends[$item['id']]->teacher_ids,'s');
            }
            //被谁收藏
            $extras['favor_account_ids'] = '';
            if (isset($favors[$item['id']])){
                $extras['favor_account_ids'] = self::hr_addLetter($favors[$item['id']]->teacher_ids,'f');
            }
            //收藏自定义标签
            $extras['favor_custom_label_ids'] = '';
            if (isset($favorCustomLabels[$item['id']])){
                $extras['favor_custom_label_ids'] = self::hr_addLetter(
                    implode(',',array_unique(explode(',',$favorCustomLabels[$item['id']]->label_ids))),
                    'fc'
                );
            }
            //作者自定义标签
            $extras['custom_label_ids'] = '';
            if (!empty($item['custom_label_ids'])){
                $extras['custom_label_ids'] = self::hr_addLetter($item['custom_label_ids'],'c');
            }
            //系统标签中文
            $label_ids  = explode(',', $item['system_label_ids']);
            $label_name = [];
            foreach ($label_ids as $label_id) {

                if (array_key_exists($label_id, $labels)) {
                    $label_name[] = $labels[$label_id];
                }
            }
            $extras['system_labels'] = implode(' ', $label_name);
            //系统标签
            $extras['system_label_ids'] = '';
            if (!empty($item['system_label_ids'])){
                $extras['system_label_ids'] = self::hr_addLetter($item['system_label_ids'],'s');
            }

            //account不存在 用 '为分类' 替代
            if (!array_key_exists($item['account_id'], $accountNames)) {
                if ($item['account_id'] == 3) {
                    $accountNames[$item['account_id']] = '未分类';
                } else {
                    if($item['account_id']){
                        $accountNames[$item['account_id']] = $item['nickname'];
                    }else{
                        $accountNames[$item['account_id']] = '未分类';
                    }
                }
            }
            //作者名
            $extras['auth']     = $accountNames[$item['account_id']];
            //小题数
            $extras['item_num'] = self::hr_getItemNum($type,$item);
            //热词
            $hotwords = [];
            foreach ($hotWordsList as $hotWords) {
                $hotWordsArr=explode(',',$hotWords->keywords);
                foreach ($hotWordsArr as $hotWord){
                    if (mb_strstr(StringHelper::fixBackslash($item['name']),$hotWord)){
                        $hotwords[]='hw'.$hotWords->id;
                        break;
                    }
                }
            }
            $extras['hot_words'] = implode(',',$hotwords);

            $testbankIds[]  = $item['id'];
            //是否有密码
            $item['range']  = empty($item['password']) ? 0 : 1;
            //资源名
            $item['name']   = self::hr_getName($type,$item);
            $extras['name']  = StringHelper::encodeESName($item['name']);
            $extras['es_name']   = StringHelper::formatESname($item['name']);

            //es bulk批量操作所需格式 覆式插入
            $jsons[]    = [
                'handle'=> 'create',
                'id'    => self::hr_getESId($type,$item),
                'json'  => self::hr_getJson($type,$item,$extras)
            ];
            if (isset($item['es_id']) && !empty($item['es_id']) && $item['es_id'] != self::hr_getESId($type,$item)){
                $jsons[]    = [
                    'handle'=> 'delete',
                    'id'    => $item['es_id']
                ];
            }
        }
        return $jsons;
    }



    /*
     * 题单包含引用题时,es里添加is_public ,设为0
     */
    private static  function hr_getPublic($type,$item){
        switch ($type){
            case 'bill':
                $isPublic = $item['is_public'];
                if (empty($item['item_ids']) || count(explode('c',$item['item_ids']))>1){
                    $isPublic = 0;
                }
                return $isPublic;
            default :
                return $item['is_public'] ?? 0;
        }
    }

    /*
     * 引用源作者0 (副本题是否显示需要此字段,原创题附合)
     */
    private static  function hr_getOrigin($type,$item){
        switch ($type){
            case 'quotedTestbank':
                $ownOrigin = 0;
                $originAccountId = empty($item['origin1_account_id']) ? $item['origin2_account_id'] : $item['origin1_account_id'];
                if (empty($originAccountId) || $originAccountId == $item['account_id']){
                    $ownOrigin = 1;
                }
                return $ownOrigin;
            case 'exam':
                $ownOrigin = 0;
                $originAccountId = $item['origin_account_id'];
                if (empty($originAccountId) || $originAccountId == $item['account_id']){
                    $ownOrigin = 1;
                }
                return $ownOrigin;
            default :
                return 0;
        }
    }

    /*
     * 引用源作者0 (副本题是否显示需要此字段,原创题附合)
     */
    private static function hr_getName($type,$item){
        $name =  $item['name'];
        switch ($type){
            case 'quotedTestbank':
            case 'exam':
            if ($item['same_name_index']){
                    $name = $name . '（引用'.$item['same_name_index'].'）';
                }
                break;
        }

        $name = html_entity_decode($name, ENT_QUOTES, 'UTF-8');
        return $name;
    }

    /*
     * testbank|quotedTestbank-小题数;bill|test|exam-testbank数
     */
    private static function hr_getItemNum($type,$item){
        switch ($type){
            case 'test':
            case 'exam':
                return $item['item_count'];
            default :
                return count(explode(',', $item['item_ids']));
        }
    }

    private static function hr_getESId($type,$item){
        switch ($type){
            case 'testbank':
            case 'bill':
            case 'test':
                return $item['id'];
                break;
            case 'quotedTestbank':
            case 'exam':
                return 'q'.$item['id'];
            default : return 0;
        }
    }

    private static function hr_getJson($type,$item,$extras){
        $datas = [];
        $datas['id']    = $item['id'];
        $datas['type']  = $type;
        $datas['is_recommend']  = $item['is_recommend'] ?? 0;
        $datas['range']  = $item['range'];
        $datas['keyword']  = $item['keyword'];
        $datas['favorite_num']  = $item['favorite_num'];
        $datas['mark_power']  = 1;
        $datas['create_time']  = CarbonHelper::toDateString($item['created_at']);
        $datas['account'] = ['id'=>$item['account_id'],'nickname'=>$extras['auth']];
        if (isset($item['game_id'])){
            $datas['game'] =  [
                'id'=>$item['game_id'],
                'name'=>$item['game_name'],
                'game_url'=>$item['game_url'],
                'beta_game_url'=>$item['beta_game_url'],
                'game_type'=>$item['game_type'],
                'icon'=>$item['icon']
            ];
            $datas['detail_url'] = $item['game_url'].'#/detail/'.$item['id'].'?is_preview=1&hide_controls=1';
        }

        foreach ($extras as $k=>$extra){
            $datas[$k] = $extra;
        }

        $mayKeys = ['is_recommend','game_type_id','mode','total_score','exam_time','game_id','game_name'];
        foreach ($mayKeys as $mayKey){
            if (isset($item[$mayKey])){
                $datas[$mayKey]  = $item[$mayKey];
            }
        }

        return json_encode($datas);
    }

    private static function hr_checkResult($json,$count){
        $result = json_decode($json,true);

        $successItems = $result['items'];
        if (count($successItems) != $count){
            return false;
        }

        return true;
    }

    private static function hr_addLetter($items,$first_leter){
        if (empty($items)){
            return $items;
        }
        $arr = explode(',',$items);
        $rs  = [];
        foreach ($arr as $k=>$v){
            if (!empty($v)){
                $rs[] = $first_leter.$v;
            }
        }

        return implode(',',$rs);
    }



    private static function batchFixESByJsons($type,$jsons){
        $_types = [
            'testbank'      => 'testbank',
            'quotedTestbank'=> 'testbank',
            'bill'          => 'bill',
            'test'          => 'test',
            'exam'          => 'test',
        ];
        $es = new ElasticSearchHelper(env('ES_STORE'), $_types[$type]);
        //最多循环三次:插入错误重导
        $createCount    = 0;
        do{
            $rz = $es->bluks($jsons);
            ++$createCount;
            try{
                $check = self::hr_checkResult($rz,count($jsons));
            }catch (\Exception $e){
                $check = false;
            }
        }while($createCount<=3 && !$check);
        if (!$check){
            \Log::error( "\n es fix error : \n type = {$type} \n ids = ".json_encode(array_pluck($jsons,'id')));
        }
    }

    private static function batchFixMysqlByList($type,$list){
        switch ($type){
            case 'quotedTestbank':
                $updateDatas = [];
                foreach ($list as $item){
                    if ($item['es_id'] != 'q'.$item['id']){
                        $updateDatas []= ['id'=>$item['id'],'es_id'=>'q'.$item['id']];
                    }
                }
                try{
                    if (count($updateDatas)){
                        $user_app = \App::make(UserRepository::class);
                        \App::call([$user_app, "batchUpdateQuotedTestbankByData"],[$updateDatas]);
                    }
                }catch (\Exception | \Error $e){
                    \Log::error($e);
                    return false;
                }
                break;
            case 'exam':
                $updateDatas = [];
                foreach ($list as $item){
                    if ($item['es_id'] != 'q'.$item['id']){
                        $updateDatas []= ['id'=>$item['id'],'es_id'=>'q'.$item['id']];
                    }
                }
                try{
                    if (count($updateDatas)){
                        $test_app = \App::make(TestRepository::class);
                        \App::call([$test_app, "batchUpdateQuotationByData"],[$updateDatas]);
                    }
                }catch (\Exception | \Error $e){
                    \Log::error($e);
                    return false;
                }
                break;
            default : break;
        }

        return true;
    }
}
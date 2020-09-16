<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\Pay;
use fast\Form;
use BlockMatrix\EosRpc\WalletFactory;
use BlockMatrix\EosRpc\ChainFactory;
use BlockMatrix\EosRpc\EosRpc;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function index()
    {
        $api = (new ChainFactory)->api(ROOT_PATH);
        $walapi = (new WalletFactory)->api(ROOT_PATH);
        $eos = (new EosRpc($api, $walapi));

        echo $api->getInfo();
    }

    public function news()
    {
        $newslist = [];
        return jsonp(['newslist' => $newslist, 'new' => count($newslist), 'url' => 'https://www.fastadmin.net?ref=news']);
    }

}

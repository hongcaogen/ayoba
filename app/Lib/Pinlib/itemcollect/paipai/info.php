<?php

return array(
    'code' => 'paipai',
    'name' => '拍拍商城',
    'desc' => '通过拍拍开放平台获取商品数据，可到 http://etg.qq.com/ 查看详细',
    'author' => 'PinPHP TEAM',
    'domain' => 'paipai.com',
    'version' => '1.0',
    'config' => array(
        'uin' => array(
            'text' => 'UIN',
            'desc' => '拍拍开放平台申请api所用的QQ',
            'type' => 'text',
        ),
        'access_token' => array(
            'text' => 'accessToken',
            'desc' => '拍拍开放平台申请应用获取',
            'type' => 'text',
        ),
        'app_key' => array(
            'text' => 'appOAuthID',
            'desc' => '拍拍开放平台申请应用获取',
            'type' => 'text',
        ),
        'app_secret' => array(
            'text' => 'secretOAuthKey',
            'desc' => '拍拍开放平台申请应用获取',
            'type' => 'text',
        ),
        'etg_id' => array(
            'text' => 'etgID',
            'desc' => '拍拍开放平台获取',
            'type' => 'text',
        )
    )
);
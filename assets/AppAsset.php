<?php
namespace app\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/normalize.css',
        'css/styles.css',
    ];

    public $js = [
        'js/websocket.js',
        'js/scripts.js',
    ];

    public $depends = [
        'yii\web\YiiAsset',
    ];
}

<?php
namespace app\commands;

use Yii;
use yii\console\Controller;

class ServerController extends Controller
{
    public function actionStart()
    {
        Yii::$app->get('server');
    }
}

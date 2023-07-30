<?php

namespace app\helpers;

use yii\helpers\Url;

class ImageHelper
{

    public static function viewImage($url)
    {

        if (empty($url) || $url == "-") {
            return Url::base()  . '/adminlte/dist/img/default.jpg';
        }

        if (preg_match('/^http(.+)$/', $url)) {
            return $url;
        }

        return Url::base() . '/' . $url;
    }
}

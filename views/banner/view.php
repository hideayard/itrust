<?php

use yii\helpers\Html;
use kartik\detail\DetailView;
use kartik\datecontrol\DateControl;

/**
 * @var yii\web\View $this
 * @var app\models\Banner $model
 */

$this->title = "View banner";
$this->params['breadcrumbs'][] = ['label' => 'Banners', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="banner-view">

    <?= DetailView::widget([
        'model' => $model,
        'condensed' => false,
        'hover' => true,
        'mode' => Yii::$app->request->get('edit') == 't' ? DetailView::MODE_EDIT : DetailView::MODE_VIEW,
        'attributes' => [
            'b_id',
            'b_title',
            'b_desc:ntext',
            'b_link:ntext',
            'b_foto',
        ],
        'deleteOptions' => [
            'url' => ['delete', 'id' => $model->b_id],
        ],
        'enableEditMode' => false,
    ]) ?>

</div>
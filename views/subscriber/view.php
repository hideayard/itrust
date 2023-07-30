<?php

use yii\helpers\Html;
use kartik\detail\DetailView;
use kartik\datecontrol\DateControl;

/**
 * @var yii\web\View $this
 * @var app\models\Subscriber $model
 */

$this->title = "View Subscriber";
$this->params['breadcrumbs'][] = ['label' => 'Subscribers', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="subscriber-view">

    <?= DetailView::widget([
        'model' => $model,
        'condensed' => false,
        'hover' => true,
        'mode' => Yii::$app->request->get('edit') == 't' ? DetailView::MODE_EDIT : DetailView::MODE_VIEW,
        'attributes' => [
            'subs_email:email',
            'subs_status',
            'subs_remark',
        ],
        'deleteOptions' => [
            'url' => ['delete', 'id' => $model->subs_id],
        ],
        'enableEditMode' => false,
    ]) ?>

</div>
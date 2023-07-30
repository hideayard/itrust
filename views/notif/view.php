<?php

use yii\helpers\Html;
use kartik\detail\DetailView;
use kartik\datecontrol\DateControl;

/**
 * @var yii\web\View $this
 * @var app\models\Notif $model
 */

$this->title = "View Notification";
$this->params['breadcrumbs'][] = ['label' => 'Notifs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notif-view">

    <?= DetailView::widget([
        'model' => $model,
        'condensed' => false,
        'hover' => true,
        'mode' => Yii::$app->request->get('edit') == 't' ? DetailView::MODE_EDIT : DetailView::MODE_VIEW,
        'attributes' => [
            'notif_from',
            'notif_title',
            'notif_text',
            [
                'attribute' => 'notif_date',
                'format' => [
                    'datetime', (isset(Yii::$app->modules['datecontrol']['displaySettings']['datetime']))
                        ? Yii::$app->modules['datecontrol']['displaySettings']['datetime']
                        : 'd-m-Y H:i:s A'
                ],
                'type' => DetailView::INPUT_WIDGET,
                'widgetOptions' => [
                    'class' => DateControl::classname(),
                    'type' => DateControl::FORMAT_DATETIME
                ]
            ],
            'notif_processed',
        ],
        'deleteOptions' => [
            'url' => ['delete', 'id' => $model->notif_id],
        ],
        'enableEditMode' => false,
    ]) ?>

</div>

<?php

use yii\helpers\Html;
use kartik\detail\DetailView;
use kartik\datecontrol\DateControl;

/**
 * @var yii\web\View $this
 * @var app\models\Enroll $model
 */

$this->title = "View Enroll";
$this->params['breadcrumbs'][] = ['label' => 'Enrolls', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="enroll-view">

    <?= DetailView::widget([
        'model' => $model,
        'condensed' => false,
        'hover' => true,
        'mode' => Yii::$app->request->get('edit') == 't' ? DetailView::MODE_EDIT : DetailView::MODE_VIEW,
        'attributes' => [
            'enroll_userid',
            'enroll_courseid',
            'enroll_cs',
            'enroll_remark',
            'enroll_status',
        ],
        'deleteOptions' => [
            'url' => ['delete', 'id' => $model->enroll_id],
        ],
        'enableEditMode' => false,
    ]) ?>

</div>

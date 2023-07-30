<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\CourseSession $model
 */

$this->title = 'Update Course Session';
$this->params['breadcrumbs'][] = ['label' => 'Course Sessions', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->cs_id, 'url' => ['view', 'id' => $model->cs_id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="course-session-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

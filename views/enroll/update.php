<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Enroll $model
 */

$this->title = 'Update Enrollment';
$this->params['breadcrumbs'][] = ['label' => 'Enrollments', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->enroll_id, 'url' => ['view', 'id' => $model->enroll_id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="enroll-update">

    <?= $this->render('_form', [
        'model' => $model,
        'users' => $users,
        'courses' => $courses,
        'cs' => $cs,
    ]) ?>

</div>
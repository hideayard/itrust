<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\CourseSession $model
 */

$this->title = 'Create Course Session';
$this->params['breadcrumbs'][] = ['label' => 'Course Sessions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="course-session-create">

    <?= $this->render('_form', [
        'model' => $model,
        'users' => $users,
        'course' => $course,
    ]) ?>

</div>

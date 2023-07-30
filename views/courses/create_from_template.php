<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Courses $model
 */

$this->title = 'Create Courses';
$this->params['breadcrumbs'][] = ['label' => 'Courses', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="courses-create">

    <?= $this->render('_form_template', [
        'model' => $model,
        'users' => $users,
        'courses' => $courses,
    ]) ?>

</div>

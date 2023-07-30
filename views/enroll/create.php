<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Enroll $model
 */

$this->title = 'Create Enrollment';
$this->params['breadcrumbs'][] = ['label' => 'Enrolls', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="enroll-create">

    <?= $this->render('_form', [
        'model' => $model,
        'users' => $users,
        'courses' => $courses,
        'cs' => $cs,
    ]) ?>

</div>

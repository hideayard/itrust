<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Subscriber $model
 */

$this->title = 'Create Subscriber';
$this->params['breadcrumbs'][] = ['label' => 'Subscribers', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="subscriber-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

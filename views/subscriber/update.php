<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Subscriber $model
 */

$this->title = 'Update Subscriber';
$this->params['breadcrumbs'][] = ['label' => 'Subscribers', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->subs_id, 'url' => ['view', 'id' => $model->subs_id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="subscriber-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\builder\Form;
use kartik\datecontrol\DateControl;

/**
 * @var yii\web\View $this
 * @var app\models\Subscriber $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="subscriber-form">

    <?php $form = ActiveForm::begin(['type' => ActiveForm::TYPE_HORIZONTAL]);
    echo Form::widget([

        'model' => $model,
        'form' => $form,
        'columns' => 2,
        'attributes' => [

            'subs_status' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter Subs Status...']],

            'subs_email' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter Subs Email...', 'maxlength' => 100]],

            'subs_remark' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter Subs Remark...', 'maxlength' => 100]],

        ]

    ]);

    echo Html::submitButton(
        $model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
        ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
    );
    ActiveForm::end(); ?>

</div>
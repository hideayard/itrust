<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\builder\Form;
use kartik\datecontrol\DateControl;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 * @var app\models\Notif $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Notification</h3>
    </div>

    <div class="card-body">


        <?php $form = ActiveForm::begin(['type' => ActiveForm::TYPE_HORIZONTAL, 'options' => ['enctype' => 'multipart/form-data']]); ?>

        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <?= $form->field($model, 'notif_from')->textInput(['placeholder' => "Enter link"]) ?>

                <?= $form->field($model, 'notif_title')->textInput(['placeholder' => "Enter title"]) ?>

                <?= $form->field($model, 'notif_processed')->textInput(['placeholder' => "Enter title"]) ?>
            </div>
            <div class="col-lg-6 col-md-6">
                <?= $form->field($model, 'notif_text')->textarea(['placeholder' => "Enter title", "rows" => 5]) ?>

                <?= $form->field($model, 'notif_date')->textInput(['placeholder' => "Enter title"]) ?>
            </div>
        </div>

        <?= Html::submitButton(
            $model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
            ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
        );
        ActiveForm::end(); ?>

    </div>

</div>
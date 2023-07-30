<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\SubscriberSearch $searchModel
 */

$this->title = 'Subscribers';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="subscriber-index">

    <p>
        <?php echo Html::a('<span class="fa fa-plus"></span> Add', ['create'], ['class' => 'btn btn-success'])  ?>
    </p>

    <?php Pjax::begin();
    echo GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'subs_email:email',
            'subs_status',
            'subs_remark',

            [
                'class' => 'yii\grid\ActionColumn',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a(
                            '<span class="fa fa-search"></span>',
                            Yii::$app->urlManager->createUrl(['subscriber/view', 'id' => $model->subs_id, 'view' => 't']),
                            ['title' => Yii::t('yii', 'View'),]
                        );
                    },
                    'update' => function ($url, $model) {
                        return Html::a(
                            '<span class="fa fa-edit"></span>',
                            Yii::$app->urlManager->createUrl(['subscriber/update', 'id' => $model->subs_id, 'edit' => 't']),
                            ['title' => Yii::t('yii', 'Edit'),]
                        );
                    },
                    'delete' => function ($url, $model) {
                        return Html::a(
                            '<span class="fa fa-trash"></span>',
                            Yii::$app->urlManager->createUrl(['subscriber/delete', 'id' => $model->subs_id, 'delete' => 't']),
                            ['title' => Yii::t('yii', 'Delete'),]
                        );
                    }
                ],
            ],
        ],
        'responsive' => true,
        'hover' => true,
        'condensed' => true,
        'floatHeader' => true,
        'pjax' => true,
        'pjaxSettings' => [
            'neverTimeout' => true,
        ],
        'pager' => [
            'activePageCssClass' => 'active',
            'pageCssClass' => 'page-item',
            'disabledPageCssClass' => 'page-item disabled',
            'disabledListItemSubTagOptions' =>  [
                'class' => 'page-link',
            ],
            'linkOptions' => [
                'class' => 'page-link',
            ],
            'options' => [
                'class' => 'pagination justify-content-center m-0'
            ],
            'firstPageLabel' => '<span class="fa fa-fast-backward"></span>',
            'lastPageLabel' => '<span class="fa fa-fast-forward"></span>',
            'nextPageLabel' => '<span class="fa fa-step-forward"></span>',
            'prevPageLabel' => '<span class="fa fa-step-backward"></span>',
        ],
    ]);
    Pjax::end(); ?>

</div>
<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\VideoSearch $searchModel
 */

$this->title = 'Videos';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="video-index">

    <p>
        <?php echo Html::a('<span class="fa fa-plus"></span> Add', ['create'], ['class' => 'btn btn-success'])  ?>
    </p>

    <?php Pjax::begin();
    echo GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'video_filename',
            ['attribute' => 'video_date', 'format' => ['datetime', (isset(Yii::$app->modules['datecontrol']['displaySettings']['datetime'])) ? Yii::$app->modules['datecontrol']['displaySettings']['datetime'] : 'd-m-Y H:i:s A']],
            'video_status',

            [
                'class' => 'yii\grid\ActionColumn',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a(
                            '<span class="fa fa-search"></span>',
                            Yii::$app->urlManager->createUrl(['video/view', 'id' => $model->video_id, 'view' => 't']),
                            ['title' => Yii::t('yii', 'View'),]
                        );
                    },
                    'update' => function ($url, $model) {
                        return Html::a(
                            '<span class="fa fa-edit"></span>',
                            Yii::$app->urlManager->createUrl(['video/update', 'id' => $model->video_id, 'edit' => 't']),
                            ['title' => Yii::t('yii', 'Edit'),]
                        );
                    },
                    'delete' => function ($url, $model) {
                        return Html::a(
                            '<span class="fa fa-trash"></span>',
                            Yii::$app->urlManager->createUrl(['video/delete', 'id' => $model->video_id, 'delete' => 't']),
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
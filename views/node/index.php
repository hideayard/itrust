<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\NodeSearch $searchModel
 */

$this->title = 'Nodes';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="node-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?php /* echo Html::a('Create Node', ['create'], ['class' => 'btn btn-success'])*/  ?>
    </p>

    <p>
        <?php echo Html::a('<span class="fa fa-plus"></span> Add', ['create'], ['class' => 'btn btn-success'])  ?>
    </p>
    
    <?php Pjax::begin(); echo GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'node_id',
            'node_name',
            'node_remark',
            ['attribute' => 'node_created_at','format' => ['datetime',(isset(Yii::$app->modules['datecontrol']['displaySettings']['datetime'])) ? Yii::$app->modules['datecontrol']['displaySettings']['datetime'] : 'd-m-Y H:i:s A']],
            'node_created_by',
//            ['attribute' => 'node_modified_at','format' => ['datetime',(isset(Yii::$app->modules['datecontrol']['displaySettings']['datetime'])) ? Yii::$app->modules['datecontrol']['displaySettings']['datetime'] : 'd-m-Y H:i:s A']], 
//            'node_modified_by', 
//            'node_status', 

            [
                'class' => 'yii\grid\ActionColumn',
                'buttons' => [
                    'update' => function ($url, $model) {
                        return Html::a('<span class="glyphicon glyphicon-pencil"></span>',
                            Yii::$app->urlManager->createUrl(['node/view', 'id' => $model->node_id, 'edit' => 't']),
                            ['title' => Yii::t('yii', 'Edit'),]
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
    ]); Pjax::end(); ?>

</div>

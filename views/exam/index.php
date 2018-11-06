<?php

use yii\helpers\Html;
use yii\helpers\Url;
#use yii\grid\GridView;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use kartik\dynagrid\DynaGrid;
use kartik\select2\Select2;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ExamSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Exams';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="exam-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?php Pjax::begin(); ?>
    <?= DynaGrid::widget([
        'showPersonalize' => true,
        'columns' => [
            ['class' => 'kartik\grid\SerialColumn', 'order' => DynaGrid::ORDER_FIX_LEFT],

            //'name',
            [
                'attribute'=>'name',
                'filterType'=>GridView::FILTER_SELECT2,
                'filterWidgetOptions'=>[
                    'name' => 'ExamSearch[name]',
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'minimumInputLength' => 2,
                        'placeholder' => '',
                        'language' => [
                            'errorLoading' => new JsExpression("function () { return 'Waiting for results...'; }"),
                        ],
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['exam/index', 'mode' => 'list', 'attr' => 'name']),
                            'dataType' => 'json',
                            'data' => new JsExpression('function(params) { return {q:params.term}; }')
                        ],
                        'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                        'templateResult' => new JsExpression('function(q) { return q.text; }'),
                        'templateSelection' => new JsExpression('function (q) { return q.text; }'),
                    ],
                ],
                'filterInputOptions' => [
                    'placeholder' => 'Any'
                ],
                'format'=>'raw'
            ],
            [
                'attribute'=>'subject',
                'filterType'=>GridView::FILTER_SELECT2,
                'filterWidgetOptions'=>[
                    'name' => 'ExamSearch[subject]',
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'minimumInputLength' => 2,
                        'placeholder' => '',
                        'language' => [
                            'errorLoading' => new JsExpression("function () { return 'Waiting for results...'; }"),
                        ],
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['exam/index', 'mode' => 'list', 'attr' => 'subject']),
                            'dataType' => 'json',
                            'data' => new JsExpression('function(params) { return {q:params.term}; }')
                        ],
                        'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                        'templateResult' => new JsExpression('function(q) { return q.text; }'),
                        'templateSelection' => new JsExpression('function (q) { return q.text; }'),
                    ],
                ],
                'filterInputOptions' => [
                    'placeholder' => 'Any'
                ],
                'format'=>'raw'
            ],
            /*[
                'attribute' => 'userName',
                'label' => 'Owner',
                'value' => function($model){
                    return ( $model->user_id == null ? '<span class="not-set">(user removed)</span>' : '<span>' . $model->user->username . '</span>' );
                },
                'format' => 'html',
            ],*/
            [
                'attribute'=>'userName',
                'label' => 'Owner',
                'value' => function($model){
                    return ( $model->user_id == null ? '<span class="not-set">(user removed)</span>' : '<span>' . $model->user->username . '</span>' );
                },
                'filterType'=>GridView::FILTER_SELECT2,
                'filterWidgetOptions'=>[
                    'name' => 'ExamSearch[userName]',
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'minimumInputLength' => 2,
                        'placeholder' => '',
                        'language' => [
                            'errorLoading' => new JsExpression("function () { return 'Waiting for results...'; }"),
                        ],
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['user/index', 'mode' => 'list', 'attr' => 'username']),
                            'dataType' => 'json',
                            'data' => new JsExpression('function(params) { return {q:params.term}; }')
                        ],
                        'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                        'templateResult' => new JsExpression('function(q) { return q.text; }'),
                        'templateSelection' => new JsExpression('function (q) { return q.text; }'),
                    ],
                ],
                'filterInputOptions' => [
                    'placeholder' => 'Any'
                ],
                'format' => 'raw',
                'visible' => Yii::$app->user->can('user/index')
            ],
            'ticketCount',
            [
                'attribute' => 'time_limit',
                #'format' => 'shortSize',
                'visible'=>false
            ],            
            [
                'attribute' => 'fileSize',
                'format' => 'shortSize',
                'visible'=>false
            ],
            [
                'attribute' => 'grp_netdev',
                'format' => 'boolean',
                'visible'=>false
            ],
            [
                'attribute' => 'allow_sudo',
                'format' => 'boolean',
                'visible'=>false
            ],
            [
                'attribute' => 'allow_mount',
                'format' => 'boolean',
                'visible'=>false
            ],
            [
                'attribute' => 'firewall_off',
                'format' => 'boolean',
                'visible'=>false
            ],
            [
                'attribute' => 'screenshots',
                'format' => 'boolean',
                'visible'=>false
            ],
            [
                'attribute' => 'screenshots_interval',
                'label' => 'Screenshot Interval',
                'value' => function($model){
                    return $model->screenshots_interval*60; # in seconds
                },
                'format' => 'duration',
                'visible'=>false
            ],
            [
                'attribute' => 'max_brightness',
                'label' => 'Maximum brightness',
                'value' => function($model){
                    return $model->max_brightness/100;
                },
                'format' => 'percent',
                'visible'=>false
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete}',
                'urlCreator' => function ($action, $model, $key, $index) {
                    if ($action === 'create-many') {
                        return Url::toRoute(['ticket/create-many', 'exam_id' => $model->id]);
                    }
                    return Url::toRoute(['exam/' . $action, 'id' => $model->id]);
                },
            ],
        ],
        'storage' => DynaGrid::TYPE_COOKIE,
        'theme' => 'simple-default',
        'gridOptions' => [
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'panel' => ['heading' => '<h3 class="panel-title">Your Exams</h3>'],
            'toolbar' =>  [
                ['content' =>
                    Html::a('<i class="glyphicon glyphicon-plus"></i>', ['create'], ['data-pjax' => 0, 'class' => 'btn btn-success', 'title' => 'Create Exam']) . ' ' .
                    Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['/exam/index'], ['data-pjax' => 0, 'class' => 'btn btn-default', 'title' => 'Reset Grid'])
                ],
                ['content' => '{dynagridFilter}{dynagridSort}{dynagrid}'],
                '{export}',
        ]            
        ],
        'options' => ['id' => 'dynagrid-exam-index'] // a unique identifier is important
    ]); ?>

    <?= $this->render('@app/views/_notification', [
        'session' => $session,
    ]) ?>

    <?php Pjax::end(); ?>

</div>

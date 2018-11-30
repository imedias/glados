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

            [
                'attribute' => 'createdAt',
                'format' => 'timeago',
                'filterType' => GridView::FILTER_DATE,
                'filterWidgetOptions' => [
                    'options' => ['placeholder' => 'Enter day...'],
                    'pluginOptions' => [
                       'format' => 'yyyy-mm-dd',
                       'todayHighlight' => true,
                       'autoclose' => true,
                    ]
                ],
                'visible' => false,
            ],
            [
                'attribute'=>'name',
                'filterType'=>GridView::FILTER_SELECT2,
                'filterWidgetOptions'=>[
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'placeholder' => '',
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['exam/index', 'mode' => 'list', 'attr' => 'name']),
                            'dataType' => 'json',
                            'delay' => 250,
                            'cache' => true,
                            'data' => new JsExpression('function(params) {
                                return {
                                    q: params.term,
                                    page: params.page,
                                    per_page: 10
                                };
                            }'),
                            'processResults' => new JsExpression('function(data, page) {
                                return {
                                    results: data.results,
                                    pagination: {
                                        more: data.results.length === 10 // If there are 10 matches, theres at least another page
                                    }
                                };
                            }'),
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
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'placeholder' => '',
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['exam/index', 'mode' => 'list', 'attr' => 'subject']),
                            'dataType' => 'json',
                            'delay' => 250,
                            'cache' => true,
                            'data' => new JsExpression('function(params) {
                                return {
                                    q: params.term,
                                    page: params.page,
                                    per_page: 10
                                };
                            }'),
                            'processResults' => new JsExpression('function(data, page) {
                                return {
                                    results: data.results,
                                    pagination: {
                                        more: data.results.length === 10 // If there are 10 matches, theres at least another page
                                    }
                                };
                            }'),
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
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'placeholder' => '',
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['user/index', 'mode' => 'list', 'attr' => 'username']),
                            'dataType' => 'json',
                            'delay' => 250,
                            'cache' => true,
                            'data' => new JsExpression('function(params) {
                                return {
                                    q: params.term,
                                    page: params.page,
                                    per_page: 10
                                };
                            }'),
                            'processResults' => new JsExpression('function(data, page) {
                                return {
                                    results: data.results,
                                    pagination: {
                                        more: data.results.length === 10 // If there are 10 matches, theres at least another page
                                    }
                                };
                            }'),
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
            [
                'attribute' => 'ticketInfo',
                'format' => 'html',
                'value' => function($model){
                    $a = array();
                    $model->openTicketCount != 0 ? $a[] = Html::a($model->openTicketCount, ['ticket/index', 'TicketSearch[examName]' => $model->name, 'TicketSearch[examSubject]' => $model->subject, 'TicketSearch[state]' => 0], ['class' => 'bg-success text-success']) : null;
                    $model->runningTicketCount != 0 ? $a[] = Html::a($model->runningTicketCount, ['ticket/index', 'TicketSearch[examName]' => $model->name, 'TicketSearch[examSubject]' => $model->subject, 'TicketSearch[state]' => 1], ['class' => 'bg-info text-info']) : null;
                    $model->closedTicketCount != 0 ? $a[] = Html::a($model->closedTicketCount, ['ticket/index', 'TicketSearch[examName]' => $model->name, 'TicketSearch[examSubject]' => $model->subject, 'TicketSearch[state]' => 2], ['class' => 'bg-danger text-danger']) : null;
                    $model->submittedTicketCount != 0 ? $a[] = Html::a($model->submittedTicketCount, ['ticket/index', 'TicketSearch[examName]' => $model->name, 'TicketSearch[examSubject]' => $model->subject, 'TicketSearch[state]' => 3], ['class' => 'bg-warning text-warning']) : null;

                    return (count($a) == 0 ? '' : (count($a) == 1 ? implode(',', $a) . '/' : ('(' . implode(',', $a) . ')/'))) . 
                        ($model->ticketCount != 0 ? Html::a($model->ticketCount, ['ticket/index', 'TicketSearch[examName]' => $model->name, 'TicketSearch[examSubject]' => $model->subject], ['class' => 'text-muted']) : $model->ticketCount);
                },
            ],  
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

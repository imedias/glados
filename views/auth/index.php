<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use kartik\dynagrid\DynaGrid;
use kartik\select2\Select2;
use yii\web\JsExpression;


/* @var $this yii\web\View */
/* @var $searchModel app\models\AuthenticationSearch */
/* @var $dataProvider yii\data\ArrayDataProvider */

$this->title = \Yii::t('auth', 'Authentication Methods');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="auth-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin(); ?>
    <?= DynaGrid::widget([
        'showPersonalize' => true,
        'columns' => [

            'id',
            'name',
            'description',

            [
                'class' => 'yii\grid\ActionColumn',
                'order' => DynaGrid::ORDER_FIX_RIGHT,
                'contentOptions' => [
                    'class' => 'text-nowrap',
                    'style' => 'width:10px;',
                ],
            ],
        ],
        'storage' => DynaGrid::TYPE_COOKIE,
        'theme' => 'simple-default',
        'gridOptions' => [
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'panel' => ['heading' => '<h3 class="panel-title">' . \Yii::t('auth', 'Authentication Methods') . '</h3>'],
            'toolbar' =>  [
                ['content' =>
                    Html::a('<i class="glyphicon glyphicon-plus"></i>', ['create'], ['data-pjax' => 0, 'class' => 'btn btn-success', 'title' => \Yii::t('auth', 'Create Authentication Method')]) . ' ' .
                    Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['/auth/index'], ['data-pjax' => 0, 'class' => 'btn btn-default', 'title' => \Yii::t('auth', 'Reset Grid')])
                ],
                ['content' => '{dynagridFilter}{dynagridSort}{dynagrid}'],
                '{export}',
        ]            
        ],
        'options' => ['id' => 'dynagrid-auth-index'] // a unique identifier is important
    ]); ?>

    <?php Pjax::end(); ?>


</div>
<?php

/* @var $this yii\web\View */
/* @var $searchModel app\models\forms\Search */
/* @var $dataProvider yii\elasticsearch\ActiveDataProvider */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;
use kartik\select2\Select2;
use kartik\typeahead\Typeahead;
use yii\web\JsExpression;
use app\assets\SearchAsset;

$this->title = \Yii::t('search', 'Search Results');
$this->params['breadcrumbs'][] = $this->title;

SearchAsset::register($this);

$data = [
    'user' => \Yii::t('search', 'users'),
    'exam' => \Yii::t('search', 'exams'),
    'ticket' => \Yii::t('search', 'tickets'),
    'backup' => \Yii::t('search', 'backups'),
    'restore' => \Yii::t('search', 'restores'),
    'howto' => \Yii::t('search', 'howtos'),
    'log' => \Yii::t('search', 'logs'),
    'file' => \Yii::t('search', 'files'),
    'exam_setting_de' => \Yii::t('search', 'exam settings (de)'),
    'exam_setting_en' => \Yii::t('search', 'exam settings (en)'),
];

$enter_submit = <<< SCRIPT
// submit form on enter key
$(".site-search input#q").on("keypress", function(event) {
    if (event.which == 13) {
        event.preventDefault();
        $("form#search-form").submit();
    }
});

// submit form when changing select2 field
$('#index').on('change', function (e) {
    $("form#search-form").submit();
});

// focus and move cursor to the end
input = $("input#q");
input.focus();
var tmp = input.val();
input.val('');
input.val(tmp);

// only load the search-body on form submit
$(document).on('submit', 'form#search-form', function(event) {
  $.pjax.submit(event, '#search-body');
})

$(document).on('pjax:send', function(event) {
    $("#search-body").addClass('loading');
})

$(document).on('pjax:complete', function(event) {
    $("#search-body").removeClass('loading');
})

$(document).on('pjax:timeout', function(event) {
    $("#search-body").removeClass('loading');
})

SCRIPT;

$this->registerJs($enter_submit);

?>
<div class="site-search">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin([
        'method' => 'get',
        'id' => 'search-form',
        'action' => [''],
        'options' => [
            'autocomplete' => 'off', //disable autocomplete, we do it manually
        ]
    ]); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($searchModel, 'q')->widget(Typeahead::classname(), [
                'pluginOptions' => ['highlight' => true],
                'dataset' => [
                    [
                        'datumTokenizer' => "Bloodhound.tokenizers.obj.whitespace('value')",
                        'display' => 'value',
                        'remote' => [
                            'cache' => false, // solves the issue that cached content is tranformed again ...
                            'prepare' => new JsExpression('function(q, settings) {
                                var cur = $("#q")[0].selectionStart
                                var pos = getWordAt(q, cur);
                                q = q.slice(pos[0], pos[1]);
                                url = settings.url.replace("__QUERY__", q);
                                return url.replace("__INDEX__", $("#index").val());
                            }'),
                            'url' => Url::to(['site/autocomplete', 'q' => '__QUERY__', 'index' => '__INDEX__']),
                            'transform' => new JsExpression('function(response) {
                                var q = $("#q").val();
                                var pos = getWordAt(q, $("#q")[0].selectionStart);
                                response.forEach(function(e){
                                    e.value = q.substr(0, pos[0]) + e.value + q.substr(pos[1]);
                                });
                                return response;
                            }'),
                        ],
                        'limit' => 10
                    ]
                ]
            ]); ?>
        </div>
        <div class="col-md-6 autocomplete">
            <?= $form->field($searchModel, 'index')->widget(Select2::classname(), [
                'data' => $data,
                'options' => [
                    'placeholder' => \Yii::t('search', 'Select categories ...'),
                    'multiple' => true,
                ],
            ]); ?>
        </div>
    </div>

    <hr>

    <?php ActiveForm::end(); ?>

    <?php Pjax::begin([
        'id' => 'search-body',
        'class' => 'search-body',
    ]); ?>

        <?php var_dump($searchModel->is_query_string() ? 'query_string' : 'multi_match'); ?>
        <?php var_dump($searchModel->is_notouch() ? 'notouch' : 'rewrite'); ?>
        <pre><?= $searchModel->rewrite_query($searchModel->q); ?></pre>

        <?php
        try {
            echo ListView::widget([
                'dataProvider' => $dataProvider,
                'itemView' => '_search_result',
                'emptyText' => \Yii::t('search', 'No search results.'),
                'pager' => [
                    'options' => [
                        'class' => 'pagination pagination-sm',
                    ]
                ],
            ]);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        ?>

    <?php Pjax::end(); ?>
   
</div>

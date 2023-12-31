<?php








use common\modules\student\components\forumIn\forum\bizley\podium\src\helpers\Helper;
use common\modules\student\components\forumIn\forum\bizley\podium\src\models\Meta;
use common\modules\student\components\forumIn\forum\bizley\podium\src\widgets\Avatar;
use common\modules\student\components\forumIn\forum\bizley\podium\src\widgets\editor\EditorBasic;
use kartik\file\FileInput;
use kartik\select2\Select2;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

$this->title = Yii::t('podium/view', 'Forum Details');
$this->params['breadcrumbs'][] = ['label' => Yii::t('podium/view', 'My Profile'), 'url' => ['profile/index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerJs("$('[data-toggle=\"popover\"]').popover();");

?>
<div class="row">
    <div class="col-md-3 col-sm-4">
        <?= $this->render('/elements/profile/_navbar', ['active' => 'forum']) ?>
    </div>
    <div class="col-md-6 col-sm-8">
        <div class="panel panel-default">
            <?php $form = ActiveForm::begin(['id' => 'forum-form', 'options' => ['enctype' => 'multipart/form-data']]); ?>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-12">
                        <?= $form->field($model, 'timezone')->widget(Select2::class, [
                            'data' => Helper::timeZones(),
                            'theme' => Select2::THEME_KRAJEE,
                            'showToggleAll' => false,
                            'options' => ['placeholder' => Yii::t('podium/view', 'Select your time zone for proper dates display...')],
                            'pluginOptions' => ['allowClear' => true],
                        ])->label(Yii::t('podium/view', 'Time Zone'))
                            ->hint(Html::a(Yii::t('podium/view', 'What is my time zone?'), 'http://www.timezoneconverter.com/cgi-bin/findzone', ['target' => '_blank'])); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <?= $form->field($model, 'anonymous')->checkbox(['uncheck' => 0])->label(Yii::t('podium/view', 'Hide username while forum viewing')) ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <?= $form->field($model, 'location')->textInput(['autocomplete' => 'off'])->label(Yii::t('podium/view', 'Whereabouts')) ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <?= $form
                            ->field($model, 'signature')
                            ->label(Yii::t('podium/view', 'Signature under each post'))
                            ->widget(EditorBasic::class) ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <?= $form
                            ->field($model, 'gravatar')
                            ->checkbox(['disabled' => empty($user->email)])
                            ->label('<strong>' . Yii::t('podium/view', 'Use Gravatar image as avatar') . '</strong>')
                            ->hint(
                                (empty($user->email)
                                    ? Html::tag('span', Yii::t('podium/view', 'You need email address set to use Gravatar.'), ['class' => 'text-danger pull-right'])
                                    : ''
                                ) . Html::a(Yii::t('podium/view', 'What is Gravatar?'), 'http://gravatar.com', ['target' => 'gravatar'])
                            ) ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <?= $form->field($model, 'image')
                            ->label(Yii::t('podium/view', 'Or upload your own avatar'))
                            ->widget(FileInput::class, [
                                'options' => ['accept' => 'image/*'],
                                'pluginOptions' => [
                                    'theme' => 'fa4',
                                    'allowedFileExtensions' => ['jpg', 'jpeg', 'gif', 'png']
                                ]
                            ])
                            ->hint(Yii::t('podium/view', 'Square avatars look best.') . '<br>' . Yii::t('podium/view', 'Maximum size is {size}, {width}x{height} pixels; png, jpg and gif images only.', ['size' => ceil(Meta::MAX_SIZE / 1024) . 'kB', 'width' => Meta::MAX_WIDTH, 'height' => Meta::MAX_HEIGHT])) ?>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <div class="row">
                    <div class="col-xs-12">
                        <?= Html::submitButton('<span class="glyphicon glyphicon-ok-sign"></span> ' . Yii::t('podium/view', 'Save changes'), ['class' => 'btn btn-block btn-primary', 'name' => 'save-button']) ?>
                    </div>
                </div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
    <div class="col-md-3 hidden-sm hidden-xs">
        <?= Avatar::widget([
            'author' => $user,
            'showName' => false
        ]) ?>
    </div>
</div><br>

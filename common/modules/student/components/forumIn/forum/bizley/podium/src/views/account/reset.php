<?php








use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

$this->title = Yii::t('podium/view', 'Password Reset');
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="row">
    <div class="col-xs-12">
        <div class="alert alert-info">
            <span class="glyphicon glyphicon-info-sign"></span> <?= Yii::t('podium/view', 'Enter your user name or e-mail address you have registered with and we will send you the password reset link.') ?>
        </div>
    </div>
    <div class="col-sm-4 col-sm-offset-4">
        <?php $form = ActiveForm::begin(['id' => 'reset-form']); ?>
            <div class="form-group">
                <?= $form->field($model, 'username')->textInput(['placeholder' => Yii::t('podium/view', 'User Name or E-mail'), 'autofocus' => true])->label(false) ?>
            </div>
            <div class="form-group">
                <?= Html::submitButton('<span class="glyphicon glyphicon-ok-sign"></span> ' . Yii::t('podium/view', 'Send me the password reset link'), ['class' => 'btn btn-block btn-danger', 'name' => 'reset-button']) ?>
            </div>
        <?php ActiveForm::end(); ?>
    </div>
</div><br>

<?php

use yii\web\View;

$script='
            $(document).ready(function(){
                admission.init();
                admission.speciality(["widget-container"]);
            });
            ';
    $this->registerJsFile('/js/admission/admission.js',  ['position' => yii\web\View::POS_END]);
    $this->registerJs($script, View::POS_END);
?>
<div id="widget-container">
    
</div>

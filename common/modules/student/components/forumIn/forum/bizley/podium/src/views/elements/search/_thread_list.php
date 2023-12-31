<?php








use yii\widgets\ListView;

?>
<?= ListView::widget([
    'dataProvider' => $dataProvider,
    'itemView' => '/elements/search/_thread_' . $type,
    'summary' => '',
    'layout' => "{summary}\n{items}\n</tbody></table>{pager}<table><tbody>",
    'pager' => ['options' => ['class' => 'pagination podium-pagination']],
    'emptyText' => $type == 'topics' ? Yii::t('podium/view', 'No matching threads can be found.') : Yii::t('podium/view', 'No matching posts can be found.'),
    'emptyTextOptions' => ['tag' => 'td', 'class' => 'text-muted', 'colspan' => 4],
    'options' => ['tag' => 'tbody'],
    'itemOptions' => ['tag' => 'tr', 'class' => 'podium-thread-line']
]);

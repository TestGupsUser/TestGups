<?php

use common\components\Migration\MigrationWithDefaultOptions;




class m210414_142657_add_curriculum_ref_id_to_dictionary_speciality extends MigrationWithDefaultOptions
{
    


    public function safeUp()
    {
        $this->addColumn('{{%dictionary_speciality}}', 'curriculum_ref_id', $this->integer());
        $this->createIndex(
            '{{%idx-dictionary_speciality-curriculum_ref_id}}',
            '{{%dictionary_speciality}}',
            'curriculum_ref_id'
        );


        $this->addForeignKey(
            '{{%fk-dictionary_speciality-curriculum_ref_id}}',
            '{{%dictionary_speciality}}',
            'curriculum_ref_id',
            '{{%curriculum_reference_type}}',
            'id',
            'NO ACTION'
        );
    }

    


    public function safeDown()
    {
        $this->dropForeignKey(
            '{{%fk-dictionary_speciality-curriculum_ref_id}}',
            '{{%dictionary_speciality}}'
        );
        $this->dropIndex('{{%idx-dictionary_speciality-curriculum_ref_id}}', '{{%dictionary_speciality}}');

        $this->dropColumn('{{%dictionary_speciality}}', 'curriculum_ref_id');
    }

}

<?php

return [
    'components' => [
        'created_by_at'             => '<strong>:subject</strong>, <strong>:causer</strong> tarafından <strong>:event</strong>. <br><small> Güncellendi: <strong>:update_at</strong></small>',
        'updater_updated'           => ':causer :event şunları: <br>:changes',
        'from_oldvalue_to_newvalue' => '- :key <strong>:old_value</strong> değerinden <strong>:new_value</strong> değerine',
        'to_newvalue'               => '- :key <strong>:new_value</strong>',
        'unknown'                   => 'Bilinmiyor',
    ],
];

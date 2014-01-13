<?php

return array(
    'includes' => array('_sdk1'),
    'services' => array(
        'default_settings' => array(
            'params' => array(
                'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
                'secret' => $_ENV['AWS_SECRET_KEY'],
                'region' => 'ap-southeast-1'
            )
        )
    )
);

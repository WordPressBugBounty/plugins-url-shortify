<?php return array(
    'root' => array(
        'name' => 'kaizen-coders/url-shortify',
        'pretty_version' => 'dev-master',
        'version' => 'dev-master',
        'reference' => '125b82d7d2dab9d5c74301fc20a792fcbcbb5f99',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'composer/installers' => array(
            'pretty_version' => 'v1.12.0',
            'version' => '1.12.0.0',
            'reference' => 'd20a64ed3c94748397ff5973488761b22f6d3f19',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'kaizen-coders/url-shortify' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => '125b82d7d2dab9d5c74301fc20a792fcbcbb5f99',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'roundcube/plugin-installer' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'shama/baton' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
    ),
);

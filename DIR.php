<?php
define('BASE_PATH', __DIR__ . '/');
define('PUBLIC_PATH', BASE_PATH . 'public/');
define('SHARED_PATH', BASE_PATH . 'shared/');

define('COMPONENT_PATH', BASE_PATH . 'front-end/Component/');
define('STATIC_PATH', PUBLIC_PATH . 'static/'); // Note : Pas de `/` supplémentaire'COMPONENT_PATH', BASE_PATH . '/front-end/Component/');
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]");
define('PUBLIC_URL', BASE_URL . '/');
define('STATIC_URL', PUBLIC_URL . 'static/');
define('PRODUCT_IMAGES_URL', STATIC_URL . 'product_images/');

define('CONFIG_PATH', BASE_PATH . 'config/');
define('API_PATH', BASE_PATH . 'api/');
define('SERVICES_PATH', BASE_PATH . 'services/');
define('CRUD_PATH', BASE_PATH . 'CRUD/');
define('SERVICE_CRUD_PATH', BASE_PATH . 'SERVICE_CRUD/');
define('SECURISER_PATH', BASE_PATH . 'securiser/');



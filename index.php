<?php

    header('Content-Type: text/plain; charset=utf-8');

    $options = Array(
        // YMapsML-файл
        'XML' => 'prokudin.xml',
        // Минимальный коэффициент масштабирования,
        // начиная с которого требуется подготовить тайлы
        'minZoom' => 4,
        // Максимальный коэффициент масштабирования,
        // до которого требуется подготовить тайлы
        'maxZoom' => 15,
        // Папка для размещения тайлов, примера
        // и файла со стилями
        'directory' => 'prokudin',
        // Права доступа, которые будут выставлены
        // всем создаваемым папкам
        'permissions' => 0774,
        // Шаблон имени тайла с изображениями
        'imageTemplate' => 'png/%z/tile-%x-%y.png',
        // Шаблон имени тайла с описанием данных
        'jsTemplate' => 'js/%z/tile-%x-%y.js',
        // Шаблон ключа запроса
        'keyTemplate' => 'prokudin-%x-%y-%z',
        // Префикс имени стиля
        'styleKey' => 'prokudin',
        // Имя файла со стилями
        'stylesFile' => 'styles.js',
        // Процессор изображений
        'imageProcessor' => array (
            // Класс процессора изображений
            'name' => 'GDImageProcessor',
            // Имя подключаемого файла
            'file' => 'imageProcessors/GDImageProcessor.class.php'
        ),
        // Имя файла с примером кода
        'htmlExample' => 'index.html',
        // API-ключ, используется только в примере кода
        'apiKey' => 'AEy0r0kBAAAAVbCAHwMAqvKQgnnbjUmBw5U28rM0LiiM8FUAAAAAAAAAAAB6j_kSspbKsUy6jvwHJcRpSIQGzQ=='
    );

    // Подключаем основной файл проекта
    include ('include/Application.class.php');

    // Запускаем
    try {
        Application::get()->run($options);
    } catch (Exception $e) {
        die($e->getMessage());
    }

    // Если ошибок не было, делаем редирект на страницу с примером
    header('location: ' . $options['directory'] . '/' . $options['htmlExample']);
?>
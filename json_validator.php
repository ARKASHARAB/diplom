<?php
// json_validator.php - инструмент для проверки и исправления JSON
require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Валидатор JSON</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        textarea { font-family: monospace; font-size: 0.9rem; }
        .json-valid { border: 2px solid #198754; }
        .json-invalid { border: 2px solid #dc3545; }
    </style>
</head>
<body>
<div class='container mt-4'>
    <h1 class='mb-4'><i class='bi bi-file-earmark-json'></i> Валидатор JSON файла</h1>";

if (!file_exists(JSON_FILE_PATH)) {
    echo "<div class='alert alert-warning'>
            <h4>Файл не найден</h4>
            <p>Файл <code>" . JSON_FILE_PATH . "</code> не существует.</p>
            <a href='install.php' class='btn btn-primary'>Вернуться к установке</a>
          </div>";
} else {
    $content = file_get_contents(JSON_FILE_PATH);
    $json = json_decode($content);
    
    echo "<div class='row'>
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-body'>
                        <h5 class='card-title'>Информация о файле</h5>
                        <p><strong>Путь:</strong> " . JSON_FILE_PATH . "</p>
                        <p><strong>Размер:</strong> " . number_format(strlen($content)) . " байт</p>
                        <p><strong>Строк:</strong> " . substr_count($content, "\n") . "</p>
                        <p><strong>Валидность JSON:</strong> ";
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<span class='badge bg-success'>Валидный</span>";
    } else {
        echo "<span class='badge bg-danger'>Не валидный: " . json_last_error_msg() . "</span>";
    }
    
    echo "</p></div></div></div>
          <div class='col-md-6'>
            <div class='card'>
                <div class='card-body'>
                    <h5 class='card-title'>Действия</h5>
                    <a href='install.php' class='btn btn-primary w-100 mb-2'>Вернуться к установке</a>
                    <a href='?action=download' class='btn btn-secondary w-100 mb-2'>Скачать JSON</a>
                    <a href='?action=fix' class='btn btn-warning w-100'>Попытаться исправить</a>
                </div>
            </div>
          </div>
        </div>
        
        <div class='card mt-4'>
            <div class='card-header'>
                <h5 class='mb-0'>Просмотр содержимого (первые 200 строк)</h5>
            </div>
            <div class='card-body p-0'>
                <textarea class='form-control " . (json_last_error() === JSON_ERROR_NONE ? 'json-valid' : 'json-invalid') . "' 
                          rows='20' readonly>" . htmlspecialchars($content) . "</textarea>
            </div>
        </div>";
    
    // Детальный анализ ошибок
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "<div class='card mt-4 border-danger'>
                <div class='card-header bg-danger text-white'>
                    <h5 class='mb-0'>Анализ ошибок</h5>
                </div>
                <div class='card-body'>
                    <h6>Возможные проблемы:</h6>
                    <ol>";
        
        // Проверяем распространенные ошибки
        if (strpos($content, "',") !== false) {
            echo "<li>Обнаружены одинарные кавычки - замените их на двойные</li>";
        }
        
        if (substr_count($content, '{') !== substr_count($content, '}')) {
            $diff = abs(substr_count($content, '{') - substr_count($content, '}'));
            echo "<li>Несоответствие фигурных скобок: разница = $diff</li>";
        }
        
        if (substr_count($content, '[') !== substr_count($content, ']')) {
            $diff = abs(substr_count($content, '[') - substr_count($content, ']'));
            echo "<li>Несоответствие квадратных скобок: разница = $diff</li>";
        }
        
        // Ищем лишние запятые
        $lines = explode("\n", $content);
        foreach ($lines as $i => $line) {
            if (preg_match('/,\s*[}\]],?$/', $line)) {
                echo "<li>Строка " . ($i + 1) . ": Возможна лишняя запятая перед закрывающей скобкой</li>";
            }
        }
        
        echo "</ol>
                <h6 class='mt-3'>Как исправить:</h6>
                <ul>
                    <li>Убедитесь, что все строки в двойных кавычках</li>
                    <li>Удалите лишние запятые перед ] и }</li>
                    <li>Проверьте соответствие открывающих и закрывающих скобок</li>
                    <li>Используйте онлайн-валидатор JSON</li>
                </ul>
              </div>
            </div>";
    }
}

echo "</div></body></html>";
?>
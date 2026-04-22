<?php
$dsn = 'mysql:host=127.0.0.1;dbname=espace_membre_2026';
$username = 'valet';
$password = 'valet';
$options = [];
try {
  $pdo= new PDO($dsn, $username, $password, $options);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
die("erreur". $e->getMessage());
}
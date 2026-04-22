<?php
session_start();
require_once 'flash.php';

$_SESSION = array();
flash_set('success', "Vous avez été déconnecté avec succès.");

header('Location: connexion.php');
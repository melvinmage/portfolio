<?php
// pages/logout.php
require_once __DIR__ . '/../includes/core.php';
require_once __DIR__ . '/../includes/auth.php';
auth_logout();
session_start();
flash('success', current_lang() === 'fr' ? 'Vous avez été déconnecté.' : 'You have been signed out.');
redirect('/index.php?page=login');

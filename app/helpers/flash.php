<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function showFlash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        echo "<div class='alert alert-{$flash['type']} alert-dismissible fade show' role='alert'>
                {$flash['message']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

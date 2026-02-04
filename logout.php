<?php
session_start();

/* SESSION TÖRLÉS */
session_unset();
session_destroy();

/* VISSZA A FŐOLDALRA */
header("Location: index.php");
exit;

<?php

session_start();

session_destroy();

header('location: /Healthcare/index.php');

<?php
require_once 'config/mailer.php';

sendOutsideMail('fungi11@yahoo.com', 'Test Email', 'Hey this is <strong>a test.</strong>', 'Hey this is a test', 'Tester Tester');
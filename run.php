#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Command\EC2CnameCommand\EC2CnameCommand;

$application = new Application();
$application->add(new EC2CnameCommand());
$application->run();

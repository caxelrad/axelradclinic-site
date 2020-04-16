<?php

error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

AxBin::load('axelrad-data-access');
AxData::configure(DB_HOST, DB_NAME, DB_USER, DB_PASSWORD);

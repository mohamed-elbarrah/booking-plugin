<?php
require 'C:/Users/mohamed/Local Sites/demo-wp/app/public/wp-load.php';
$services = \BookingApp\Service_Manager::instance()->get_services();
foreach ($services as $s) {
    echo $s->name . ' - ' . $s->duration . " mins\n";
}

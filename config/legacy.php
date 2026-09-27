<?php

/*
|--------------------------------------------------------------------------
| Application parameters carried over from the Yii app
|--------------------------------------------------------------------------
|
| These mirror the 'params' array in the Yii app's protected/config/main.php
| (read there as Yii::app()->params['...']). Read them with
| config('legacy.<key>').
|
*/

return [

    'topTag' => 'Progetto Uomo',
    'adminName' => 'Rishilpi International',
    'bottomTag' => 'Rishilpi Health Program',
    'adminAddress' => 'Gopinathpur, Binerpota, Post box 8, Satkhira 9400. Mobile: +880 1715 608768 (Adult), +880 1715 608 793 (Child)',
    'tagLine' => 'Rishilpi International is a non government, humanitarian organization working for untouchable and outcaste community.<br />Rishilpi International has been registered as NGO-AB. The Registration Number of RISHILPI is 215, dated 24 february 1987.<br />The Registration Authority is the NGO Affairs Bureau of the Government of Bangladesh.',
    'physician_visit_time' => '9:00 AM to 1:30 PM (Friday and Government holidays are closed)',
    'physician_visit_time_2' => '9:00 AM to 4:00 PM (Friday and Government holidays are closed)',

    // ACTUAL, LIFO, FIFO, AVERAGE
    'RATEMETHODE' => 'LIFO',

    'adminEmail' => 'info@domain.com',
    'noreply' => 'noreply@domain.com',
    'rishilpiEmail' => 'rishilpi.pm-health@rishilpibd.org',
    'AdultChildMobile' => 'Adult Mobile: +880 1715 608786. Child Mobile: +880 1715 608 793',
    'TherapyUnit' => 'Physiotherapy/Occupational Therapy Unit',

    // Percent discount applied to invoice lines
    'discountMedicine' => 10,
    'discountService' => 10,

    // Session value set at login in the Yii app
    'currency' => '৳',

    'pageSize' => 25,
    'pageSize10' => 10,
    'pageSize20' => 20,
    'pageSize30' => 30,
    'pageSize40' => 40,
    'pageSize50' => 50,
    'pageSize100' => 100,
    'pageSize500' => 500,
    'pageSize1000' => 1000,
];

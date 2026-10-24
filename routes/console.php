<?php

use App\Models\Upload;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Uploads that no saved content used within a day are removed with their files (D-028).
Schedule::command('model:prune', ['--model' => [Upload::class]])->daily();

// The details of STS transactions older than 7 days (plate number, owner's name, RT/RW) are dropped (D-079), as every import does too.
Schedule::command('pintu:clear-revenue-details')->dailyAt('00:05')->timezone('Asia/Makassar');

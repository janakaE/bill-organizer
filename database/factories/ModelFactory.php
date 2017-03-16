<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Support\Facades\DB;

use App\FieldAreas;
use App\Record;
use App\RecordIssuer;
use App\RecordIssuerType;
use App\User;
use App\Template;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(User::class, function (Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});



$factory->define(RecordIssuer::class, function (Generator $faker){
   return [
       'name' => $faker->company,
       'type' => function() {
           $record_issuer_types = DB::table('record_issuer_types')->pluck('id')->toArray();
           $rand_index = array_rand($record_issuer_types);
           return $record_issuer_types[$rand_index];
       },
       'user_id' => function() {
           return factory(App\User::class)->create()->id;
       }
   ];
});


/**
 * Be careful, there's a pitfall here.
 * Since in Record, the issue_date, due_date, and period are typecasted to date type
 * The factory returns a Carbon instance instead of the DateString. The returned associative array
 * of this method is only used to create a new instance in the DB
 */
$factory->define(Record::class, function(Generator $faker){
    $now = Carbon::now();
    $issue_date = (clone $now)->subDays(random_int(0, 30));
    $period = $issue_date->format('Y-m');
    $amount = round(rand() / getrandmax() * 1000, 2);
    $user_id = factory(App\User::class)->create()->id;
    $record_issuer = factory(App\RecordIssuer::class)->create([
        'user_id' => $user_id
    ]);

    $record_issuer_type = DB::table('record_issuer_types')->find($record_issuer->type);
    $is_bill = $record_issuer_type->type === RecordIssuerType::BILL_TYPE_NAME;
    $due_date = $is_bill ? (clone $now)->addDays(random_int(0, 90)) : null;

    return [
        'issue_date' => $issue_date->toDateString(),
        'due_date' => $due_date === null ? null : $due_date->toDateString(),
        'period' => $period,
        'amount' => $amount,
        'user_id' => $user_id,
        'path_to_file' => 'whatever/tmp/file.pdf',
        'record_issuer_id' => $record_issuer->id
    ];
});


/**
 * A4 pixels size is 2480x3508 in 300 DPI. Currently, the DPI we're using is supposedly not
 * going to be more than 300 DPI
 */
$factory->define(FieldAreas::class, function(Generator $faker) {
   return [
       'page' => rand(),
       'x' => rand(0, 2480),
       'y' => rand(0, 3508),
       'w' => rand(0, 2480),
       'h' => rand(0, 3508)
   ];
});



$factory->define(Template::class, function(Generator $faker) {
    $record_issuer_id = factory(RecordIssuer::class)->create()->id;
    $issue_date_area_id = factory(FieldAreas::class)->create()->id;
    $due_date_area_id = factory(FieldAreas::class)->create()->id;
    $period_area_id = factory(FieldAreas::class)->create()->id;
    $amount_area_id = factory(FieldAreas::class)->create()->id;

    return [
        'record_issuer_id' => $record_issuer_id,
        'issue_date_area_id' => $issue_date_area_id,
        'due_date_area_id' => $due_date_area_id,
        'period_area_id' => $period_area_id,
        'amount_area_id' => $amount_area_id
    ];
});

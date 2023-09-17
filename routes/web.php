<?php

use Illuminate\Support\Facades\Route;
use \Illuminate\Support\Facades\DB;
use Ifsnop\Mysqldump as IMysqldump;
use App\Classes\Import;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', 'Site\HomeController@index')->name('/');
Route::get('offer_details/{id}', 'Site\HomeController@offerDetails')->name('offer_details');

// safety
Route::get('safety', 'Site\HomeController@safety')->name('safety');

## About
Route::get('about_us', 'Site\HomeController@about')->name('about_us');

## Terms
Route::get('terms', 'Site\HomeController@terms')->name('terms');

## Groups
Route::get('groups', 'Site\HomeController@groups')->name('groups');

## Activities
Route::get('activities', 'Site\HomeController@activities')->name('activities');

## Contact
Route::get('contact_us', 'Site\HomeController@contact')->name('contact_us');
Route::post('storeContact', 'Site\HomeController@storeContact')->name('storeContact');


#### create Capacity 33333
Route::get('createCapacity', 'Site\CapacityController@createCapacity')->name('createCapacity');
Route::POST('storeTicket', 'Site\CapacityController@storeTicket')->name('storeTicket');

Route::get('/clear/route', function () {
    \Artisan::call('optimize:clear');
    return 'done';
});


require __DIR__ . '/sales/auth.php';


Route::group(['middleware' => 'auth', 'namespace' => 'Sales'], function () {


//================================ Home ====================================
    Route::get('/sales', 'HomeController@index')->name('sales');

    require __DIR__ . '/sales/CRUD.php';

});
Route::group(['namespace' => 'Sales'], function () {

    //=========================== visitor Types Prices ============================
    Route::get('visitorTypesPrices', 'VisitorTypesPricesController@visitorTypesPrices')->name('visitorTypesPrices');

});

/////////////////////// un auth ////////
Route::get('getShifts', 'Sales\TicketController@getShifts')->name('getShifts');
Route::get('calcCapacity', 'Sales\TicketController@calcCapacity')->name('calcCapacity');
Route::get('getProductsPrices', 'Sales\TicketController@getProductsPrices')->name('getProductsPrices');
Route::get('printTicket/{id}', 'Sales\TicketController@edit')->name('printTicket');

//================================ Admin Dashboard ====================================
require __DIR__ . '/admin.php';


Route::get('changeDbConnection', 'Sales\Auth\AuthController@uploadData')->name('changeDbConnection');
//end route web now ------------

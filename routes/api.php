<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'middleware' => ['api'],
    'namespace' => 'Api\v1',
    'prefix' => 'v1'
], function () {
    // Guest Access
    Route::post('/auth/resetPasswordEmail', 'Auth\ForgotPasswordController@sendResetLinkEmail');
    Route::post('/auth/resetPassword', 'Auth\ResetPasswordController@reset');
    Route::post('/auth/login', 'AuthController@login');

    // Token Refresh
    Route::get('/auth/refresh', 'AuthController@refresh')
        ->middleware(['api', 'jwt.refresh']);

    // Login Check
    Route::group([
        'middleware' => ['jwt.auth'],
    ], function () {
        Route::get('/auth/user', 'AuthController@user');
        Route::post('/auth/logout', 'AuthController@logout');

        Route::get('/admin/roles', 'AdminController@roles');
        Route::get('/admin/users', 'AdminController@users');
        Route::get('/admin/user', 'AdminController@user');
        Route::post('/admin/userEdit', 'AdminController@userEdit');
        Route::post('/admin/userDelete', 'AdminController@userDelete');

        Route::get('/user/users', 'UserController@users');
        Route::post('/user/edit', 'UserController@edit');
        Route::post('/user/passwordChange', 'UserController@passwordChange');
        Route::post('/user/uploadAvatar', 'UserController@uploadAvatar');
        Route::post('/user/deleteAvatar', 'UserController@deleteAvatar');

        Route::post('/click2call/originate', 'Click2CallController@originate');

        // 発着信履歴
        Route::group([
            'middleware' => 'permission:cdr-user',
            'prefix' => 'cdr'
        ], function () {
            Route::get('search', 'CdrController@search');
            Route::get('download', 'CdrController@download');
        });

        Route::get('/addressbook/search', 'AddressBookController@search');
        Route::get('/addressbook/detail', 'AddressBookController@detail');
        Route::get('/addressbook/groupList', 'AddressBookController@groupList');
        Route::get('/addressbook/groups', 'AddressBookController@groups');
        Route::get('/addressbook/group', 'AddressBookController@group');
        Route::post('/addressbook/edit', 'AddressBookController@edit');
        Route::post('/addressbook/groupEdit', 'AddressBookController@groupEdit');
        Route::post('/addressbook/delete', 'AddressBookController@delete');
        Route::post('/addressbook/groupDelete', 'AddressBookController@groupDelete');
    });
});

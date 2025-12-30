<?php

Route::group([
    'middleware' => 'web',
    'prefix' => \Helper::getSubdirectory(),
    'namespace' => 'Modules\NobilikBranches\Http\Controllers'
], function() {



    // ============================
    // Прикрепление объекта к заявке
    // ============================
    Route::post('/branches/{branch}/attach', [
        'uses' => 'BranchController@attachToConversation',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.attach');

    // ============================
    // AJAX поиск объектов (для модалки)
    // ============================
    Route::get('/branches/search', [
        'uses' => 'BranchController@search',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.search');

    // ============================
    // CRUD объектов
    // ============================
    // Список объектов
    Route::get('/branches', [
        'uses' => 'BranchController@index',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.index');

    // Форма создания
    Route::get('/branches/create', [
        'uses' => 'BranchController@create',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.create');

    // Сохранение нового объекта
    Route::post('/branches', [
        'uses' => 'BranchController@store',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.store');

    // Форма редактирования
    Route::get('/branches/{branch}/edit', [
        'uses' => 'BranchController@edit',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.edit');

    // Обновление объекта
    Route::put('/branches/{branch}', [
        'uses' => 'BranchController@update',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.update');

    // Удаление объекта
    Route::delete('/branches/{branch}', [
        'uses' => 'BranchController@destroy',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.destroy');

// -------------------------------------------------------------
    Route::post('/branches/tags/create', [
        'uses' => 'BranchController@ajaxCreateTag',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.tags.create');

    // 1. Список объектов по тегу
    Route::get('/branches/tag/{tag}', [
        'uses' => 'BranchController@branchesByTag',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.byTag');

    // 2. Привязать тег к объекту
    Route::post('/branches/{branch}/tags', [
        'uses' => 'BranchController@attach',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.tags.attach');

    // 3. Отвязать тег от объекта
    Route::delete('/branches/{branch}/tags/{tag}', [
        'uses' => 'BranchController@detach',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.tags.detach');

    // 4. AJAX автокомплит тегов
    Route::get('/branches/tags/search', [
        'uses' => 'BranchController@ajaxTagAutocomplete',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('tags.search');



// -----------------------------------------------------------

    // 1. AJAX Поиск адресов для автокомплита (search)
    Route::get('/branches/addresses/search', [
        'uses' => 'BranchAddressController@search',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('addresses.search');

    // 2. Создание нового адреса (store)
    Route::post('/branches/addresses', [
        'uses' => 'BranchAddressController@store',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('addresses.store');

    // 3. Получение конкретного адреса по ID (show)
    Route::get('/branches/addresses/{address}', [
        'uses' => 'BranchAddressController@show',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('addresses.show');

    // 4. Обновление адреса по ID (update)
    Route::put('/branches/addresses/{address}', [
        'uses' => 'BranchAddressController@update',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('addresses.update');

    // 5. Удаление адреса по ID (destroy)
    Route::delete('/branches/addresses/{address}', [
        'uses' => 'BranchAddressController@destroy',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('addresses.destroy');

});

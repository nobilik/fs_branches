<?php

Route::group([
    'middleware' => 'web',
    'prefix' => \Helper::getSubdirectory(),
    'namespace' => 'Modules\NobilikBranches\Http\Controllers'
], function() {



    // ============================
    // Прикрепление филиала к заявке
    // ============================
    Route::post('/branches/{branch}/attach', [
        'uses' => 'BranchController@attachToConversation',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.attach');

    // ============================
    // AJAX поиск филиалов (для модалки)
    // ============================
    Route::get('/branches/search', [
        'uses' => 'BranchController@search',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.search');

    // ============================
    // CRUD филиалов
    // ============================
    // Список филиалов
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

    // Сохранение нового филиала
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

    // Обновление филиала
    Route::put('/branches/{branch}', [
        'uses' => 'BranchController@update',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.update');

    // Удаление филиала
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

    // 1. Список филиалов по тегу
    Route::get('/branches/tag/{tag}', [
        'uses' => 'BranchController@branchesByTag',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.byTag');

    // 2. Привязать тег к филиалу
    Route::post('/branches/{branch}/tags', [
        'uses' => 'BranchController@attach',
        'middleware' => ['auth', 'roles'],
        'roles' => ['admin', 'user'],
        'laroute' => true
    ])->name('branches.tags.attach');

    // 3. Отвязать тег от филиала
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

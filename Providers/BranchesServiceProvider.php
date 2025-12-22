<?php

namespace Modules\NobilikBranches\Providers;

use Illuminate\Support\ServiceProvider;
use App\Conversation;
use App\Events\CustomerCreatedConversation;
use Modules\NobilikBranches\Observers\ConversationObserver; 
use Illuminate\Contracts\Config\Repository as Config;

use Modules\NobilikBranches\Services\FiasApiService;

use Illuminate\Support\Facades\Log;

// Определяем алиас модуля
define('NB_MODULE', 'nobilikbranches');

class BranchesServiceProvider extends ServiceProvider
{
    /**
     * Указывает, отложена ли загрузка провайдера.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Запуск событий приложения.
     *
     * @return void
     */
    public function boot()
    {
        
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();

    }

    public function hooks()
    {

        // Добавляем CSS и JS файлы модуля в layout.
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(NB_MODULE).'/css/module.css';
            $styles[] = \Module::getPublicPath(NB_MODULE).'/css/modal.css';
            return $styles;
        });

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            // $javascripts[] = \Module::getPublicPath(NB_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(NB_MODULE).'/js/module.js';
            $javascripts[] = \Module::getPublicPath(NB_MODULE).'/js/conversation.js';
            $javascripts[] = \Module::getPublicPath(NB_MODULE).'/js/branch-tags.js';
            $javascripts[] = \Module::getPublicPath(NB_MODULE).'/js/address.js';
            $javascripts[] = \Module::getPublicPath(NB_MODULE).'/js/settings.js';
            return $javascripts;
        });

        //         // можно также подключить partial modal в footer
        // \Eventy::addAction('layout.body_bottom', function() {
        //     echo view(NB_MODULE . '::partials.attach_modal')->render();
        // });

        \Eventy::addAction('conversation.before_threads', function($conversation) {
            echo view('nobilikbranches::partials.conversation_branch_card', compact('conversation'))->render();
            echo view('nobilikbranches::partials.branch_select_modal', compact('conversation'))->render();
            echo view('nobilikbranches::partials.branch_create_modal', compact('conversation'))->render();
        });

        \Eventy::addAction('menu.manage.after_mailboxes', function() {
            $user = auth()->user();
            // Проверка прав Администратора
            if ($user->isAdmin() || $user->hasPermission(\App\User::PERM_EDIT_TAGS)) {
                ?>
                    <li class="<?php echo \Helper::menuSelectedHtml('branches') ?>">
                        <a href="<?php echo route('branches.index') ?>"><?php echo __('Branches') ?></a>
                    </li>
                <?php
            }
        }, 22);

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
        $this->registerConfig();
                // 2. ЯВНАЯ ПРИВЯЗКА: Принуждаем Laravel разрешать ключи перед созданием сервиса
        $this->app->singleton(FiasApiService::class, function ($app) {
            
            /** @var Config $config */
            $config = $app->make(Config::class);
            
            // Гарантируем, что ключи будут строками, даже если config() вернет null 
            // в критический момент (хотя здесь он должен быть доступен).
            // Используем env() как последний запасной вариант, если config() еще не загружен
            $apiKey = $config->get(NB_MODULE . '.dadata.key') ?? env('DADATA_API_KEY') ?? '';
            $secretKey = $config->get(NB_MODULE . '.dadata.secret') ?? env('DADATA_SECRET_KEY') ?? '';

            // Создаем сервис и передаем ему ключи
            return new FiasApiService($apiKey, $secretKey);
        });
    }

    /**
     * Регистрация конфигурации.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path(NB_MODULE . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', NB_MODULE
        );
    }

    /**
     * Регистрация представлений (Views).
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . NB_MODULE);
        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/' . NB_MODULE;
        }, \Config::get('view.paths')), [$sourcePath]), NB_MODULE);
    }

    /**
     * Регистрация переводов.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }
        
    // /**
    //  * https://github.com/nWidart/laravel-modules/issues/626
    //  * https://github.com/nWidart/laravel-modules/issues/418#issuecomment-342887911
    //  * @return [type] [description]
    //  */
    // public function registerCommands()
    // {
    //     $this->commands([
    //         \Modules\NobilikBranches\Console\Process::class
    //     ]);
    // }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
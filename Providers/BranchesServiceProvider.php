<?php

namespace Modules\NobilikBranches\Providers;

use Illuminate\Support\ServiceProvider;
use App\Conversation;
use App\Events\CustomerCreatedConversation;
use Modules\NobilikBranches\Observers\ConversationObserver; 
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;

use Modules\NobilikBranches\Services\FiasApiService;
use Modules\NobilikBranches\Entities\Branch;
use Modules\NobilikBranches\Entities\ConversationBranch;
use Modules\Tags\Entities\Tag;
use Modules\Tags\Entities\ConversationTag;
use Modules\NobilikGroupedTags\Entities\TagGroup;

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

        // Показываем выбор филиала только при создании новой заявки,
        // чтобы не дублировать UI для уже существующих заявок.
        \Eventy::addAction('conversation.create_form.after_subject', function($conversation) {
            if (!empty($conversation->id)) {
                return;
            }

            $selectedBranchId = (int) old('branch_id', 0);
            $selectedBranch = null;
            if ($selectedBranchId > 0) {
                $selectedBranch = Branch::with('address')->find($selectedBranchId);
            }

            echo view('nobilikbranches::partials.new_conversation_branch_field', [
                'selectedBranchId' => $selectedBranchId,
                'selectedBranch' => $selectedBranch,
            ])->render();
        });

        // Модалки для выбора/создания филиала на странице новой заявки рендерим
        // после формы, чтобы не получить вложенный <form> и поломку submit.
        \Eventy::addAction('new_conversation_form.after', function($conversation) {
            if (!empty($conversation->id)) {
                return;
            }

            echo view('nobilikbranches::partials.branch_select_modal')->render();
            echo view('nobilikbranches::partials.branch_create_modal')->render();
        });

        // Привязка филиала и его тегов должна происходить только после сохранения
        // новой заявки, когда уже гарантирован conversation_id.
        \Eventy::addAction('conversation.send_reply_save', function($conversation, $request = null) {
            if (!$request instanceof Request) {
                $request = request();
            }

            if (!$request instanceof Request) {
                return;
            }

            if ((int) $request->input('is_create') !== 1) {
                return;
            }

            $branchId = (int) $request->input('branch_id');
            if ($branchId <= 0) {
                return;
            }

            $this->attachBranchWithTags($conversation, $branchId);
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
            
            // Ключи в config могут быть NULL при устаревшем config:cache,
            // поэтому дополнительно читаем runtime-переменные и модульный .env.
            $apiCandidates = [
                $config->get(NB_MODULE . '.dadata.key'),
                $config->get('services.dadata.key'),
                env('DADATA_API_KEY'),
                env('DADATA_KEY'),
                getenv('DADATA_API_KEY') ?: null,
                getenv('DADATA_KEY') ?: null,
                $_ENV['DADATA_API_KEY'] ?? null,
                $_ENV['DADATA_KEY'] ?? null,
                $_SERVER['DADATA_API_KEY'] ?? null,
                $_SERVER['DADATA_KEY'] ?? null,
                $this->readModuleEnvValue('DADATA_API_KEY'),
                $this->readModuleEnvValue('DADATA_KEY'),
            ];
            $secretCandidates = [
                $config->get(NB_MODULE . '.dadata.secret'),
                $config->get('services.dadata.secret'),
                env('DADATA_SECRET_KEY'),
                env('DADATA_SECRET'),
                getenv('DADATA_SECRET_KEY') ?: null,
                getenv('DADATA_SECRET') ?: null,
                $_ENV['DADATA_SECRET_KEY'] ?? null,
                $_ENV['DADATA_SECRET'] ?? null,
                $_SERVER['DADATA_SECRET_KEY'] ?? null,
                $_SERVER['DADATA_SECRET'] ?? null,
                $this->readModuleEnvValue('DADATA_SECRET_KEY'),
                $this->readModuleEnvValue('DADATA_SECRET'),
            ];

            $apiKey = '';
            foreach ($apiCandidates as $candidate) {
                $candidate = is_string($candidate) ? trim($candidate) : $candidate;
                if (!empty($candidate)) {
                    $apiKey = (string)$candidate;
                    break;
                }
            }

            $secretKey = '';
            foreach ($secretCandidates as $candidate) {
                $candidate = is_string($candidate) ? trim($candidate) : $candidate;
                if (!empty($candidate)) {
                    $secretKey = (string)$candidate;
                    break;
                }
            }

            // Синхронизируем в runtime config, чтобы остальной код видел уже нормализованные значения.
            if (!empty($apiKey) && !empty($secretKey)) {
                $config->set(NB_MODULE . '.dadata.key', $apiKey);
                $config->set(NB_MODULE . '.dadata.secret', $secretKey);
            }

            // Debug: log only presence, never key values.
            Log::debug('NobilikBranches: resolved Dadata keys', [
                'config_key_present' => !empty($config->get(NB_MODULE . '.dadata.key')),
                'config_secret_present' => !empty($config->get(NB_MODULE . '.dadata.secret')),
                'services_key_present' => !empty($config->get('services.dadata.key')),
                'services_secret_present' => !empty($config->get('services.dadata.secret')),
                'env_key_present' => !empty(env('DADATA_API_KEY')),
                'env_secret_present' => !empty(env('DADATA_SECRET_KEY')),
                'getenv_key_present' => !empty(getenv('DADATA_API_KEY')),
                'getenv_secret_present' => !empty(getenv('DADATA_SECRET_KEY')),
                'module_env_key_present' => !empty($this->readModuleEnvValue('DADATA_API_KEY')),
                'module_env_secret_present' => !empty($this->readModuleEnvValue('DADATA_SECRET_KEY')),
                'apiKey_final_empty' => empty($apiKey),
                'secretKey_final_empty' => empty($secretKey),
            ]);

            // Создаем сервис и передаем ему ключи
            return new FiasApiService($apiKey, $secretKey);
        });
    }

    protected function readModuleEnvValue(string $key): ?string
    {
        static $moduleEnv = null;

        if ($moduleEnv === null) {
            $moduleEnvPath = __DIR__ . '/../.env';
            $moduleEnv = [];

            if (is_file($moduleEnvPath) && is_readable($moduleEnvPath)) {
                $lines = @file($moduleEnvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (is_array($lines)) {
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                            continue;
                        }

                        [$envKey, $envValue] = array_map('trim', explode('=', $line, 2));
                        $envValue = trim($envValue, "\"'");
                        $moduleEnv[$envKey] = $envValue;
                    }
                }
            }
        }

        $value = $moduleEnv[$key] ?? null;
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
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

    protected function attachBranchWithTags(Conversation $conversation, int $branchId): void
    {
        $branch = Branch::find($branchId);
        if (!$branch) {
            return;
        }

        $branchTags = $branch->tagIds();
        $conversationTags = ConversationTag::where('conversation_id', $conversation->id)
            ->pluck('tag_id')
            ->toArray();

        $finalTags = $conversationTags;
        $groups = TagGroup::with('tags')->get();

        $allGroupedTagIds = $groups->pluck('tags.*.id')->flatten()->unique()->toArray();
        $ungroupedBranchTags = array_diff($branchTags, $allGroupedTagIds);
        $finalTags = array_unique(array_merge($finalTags, $ungroupedBranchTags));

        foreach ($groups as $group) {
            $maxTags = (int) $group->max_tags_for_conversation;
            $groupTagIds = $group->tags->pluck('id')->toArray();

            $branchGroupTags = array_intersect($branchTags, $groupTagIds);
            $convGroupTags = array_intersect($finalTags, $groupTagIds);
            $newTagsCount = count($branchGroupTags);

            if ($maxTags > 0 && $newTagsCount > 0) {
                $finalTags = array_diff($finalTags, $convGroupTags);
                $tagsToAdd = array_slice($branchGroupTags, 0, $maxTags);
                $finalTags = array_merge($finalTags, $tagsToAdd);
            } elseif ($maxTags === 0 && $newTagsCount > 0) {
                $finalTags = array_unique(array_merge($finalTags, $branchGroupTags));
            }
        }

        $finalTags = array_unique($finalTags);

        ConversationTag::where('conversation_id', $conversation->id)->delete();
        foreach ($finalTags as $tagId) {
            $tag = Tag::find($tagId);
            if ($tag) {
                $tag->attachToConversation($conversation->id);
            }
        }

        ConversationBranch::updateOrCreate(
            ['conversation_id' => $conversation->id],
            ['branch_id' => $branch->id, 'attached_by' => auth()->id()]
        );
    }
}
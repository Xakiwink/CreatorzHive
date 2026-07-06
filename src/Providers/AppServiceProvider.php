<?php

declare(strict_types=1);

namespace CreatorzHive\Providers;

use CreatorzHive\Config\AppConfig;
use CreatorzHive\Controllers\AdminUserController;
use CreatorzHive\Controllers\AnalyticsController;
use CreatorzHive\Controllers\ApiMetaController;
use CreatorzHive\Controllers\AuthController;
use CreatorzHive\Controllers\DashboardController;
use CreatorzHive\Controllers\GoogleAuthController;
use CreatorzHive\Controllers\DealController;
use CreatorzHive\Controllers\InvoiceController;
use CreatorzHive\Controllers\MediaController;
use CreatorzHive\Controllers\NotificationController;
use CreatorzHive\Controllers\InstagramOAuthController;
use CreatorzHive\Controllers\PostController;
use CreatorzHive\Controllers\TiktokOAuthController;
use CreatorzHive\Controllers\YoutubeOAuthController;
use CreatorzHive\Controllers\SettingsController;
use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Controllers\SystemController;
use CreatorzHive\Controllers\TagController;
use CreatorzHive\Core\Container;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Repositories\AnalyticsRepository;
use CreatorzHive\Repositories\AuditLogRepository;
use CreatorzHive\Repositories\DealRepository;
use CreatorzHive\Repositories\DashboardRepository;
use CreatorzHive\Repositories\InvoiceRepository;
use CreatorzHive\Repositories\JobQueueRepository;
use CreatorzHive\Repositories\MediaFileRepository;
use CreatorzHive\Repositories\NotificationPreferenceRepository;
use CreatorzHive\Repositories\NotificationRepository;
use CreatorzHive\Repositories\PostRepository;
use CreatorzHive\Repositories\SocialAccountRepository;
use CreatorzHive\Repositories\TagRepository;
use CreatorzHive\Repositories\UserPreferencesRepository;
use CreatorzHive\Repositories\UserRepository;
use CreatorzHive\Repositories\UserSessionRepository;
use CreatorzHive\Jobs\CleanupMediaJob;
use CreatorzHive\Jobs\FetchAnalyticsJob;
use CreatorzHive\Jobs\PublishPostJob;
use CreatorzHive\Jobs\SendNotificationJob;
use CreatorzHive\Core\Security\TokenCrypto;
use CreatorzHive\Services\AdminService;
use CreatorzHive\Services\AnalyticsService;
use CreatorzHive\Services\ApiMetaService;
use CreatorzHive\Services\AuthRateLimitService;
use CreatorzHive\Services\AuthService;
use CreatorzHive\Services\DashboardService;
use CreatorzHive\Services\GoogleAuthService;
use CreatorzHive\Services\InstagramOAuthService;
use CreatorzHive\Services\TiktokOAuthService;
use CreatorzHive\Services\YoutubeOAuthService;
use CreatorzHive\Services\NotificationService;
use CreatorzHive\Services\PlatformApiSecretsService;
use CreatorzHive\Services\SocialApiService;
use CreatorzHive\Support\AnalyticsReportHelper;
use CreatorzHive\Support\DealWorkflowHelper;
use CreatorzHive\Support\MediaUploadHelper;
use CreatorzHive\Support\PostInputNormalizer;
use CreatorzHive\Support\SettingsPageHelper;
use CreatorzHive\Support\UserPayloadFormatter;
use CreatorzHive\Middleware\AuthMiddleware;
use CreatorzHive\Middleware\CsrfMiddleware;
use CreatorzHive\Middleware\RoleMiddleware;

final class AppServiceProvider
{
    public static function register(Container $container): void
    {
        $container->set(AppConfig::class, new AppConfig());
        $container->set(Connection::class, new Connection($container->get(AppConfig::class)));
        $container->set(JsonResponder::class, new JsonResponder());
        $container->set(ViewRenderer::class, new ViewRenderer());
        $container->set(TokenCrypto::class, new TokenCrypto());
        $container->set(UserPayloadFormatter::class, new UserPayloadFormatter());
        $container->set(GoogleAuthService::class, new GoogleAuthService());
        $container->factory(YoutubeOAuthService::class, static function (Container $c): YoutubeOAuthService {
            return new YoutubeOAuthService($c->get(Connection::class));
        });
        $container->factory(TiktokOAuthService::class, static function (Container $c): TiktokOAuthService {
            return new TiktokOAuthService($c->get(Connection::class));
        });

        self::registerRepositories($container);
        self::registerServices($container);
        self::registerMiddleware($container);
        self::registerJobs($container);
        self::registerControllers($container);
    }

    private static function registerMiddleware(Container $container): void
    {
        $container->set(CsrfMiddleware::class, new CsrfMiddleware());
        $container->set(RoleMiddleware::class, new RoleMiddleware());
        $container->factory(AuthMiddleware::class, static function (Container $c): AuthMiddleware {
            return new AuthMiddleware($c->get(UserRepository::class));
        });
    }

    private static function registerRepositories(Container $container): void
    {
        $repos = [
            UserRepository::class,
            DealRepository::class,
            InvoiceRepository::class,
            TagRepository::class,
            NotificationRepository::class,
            NotificationPreferenceRepository::class,
            UserPreferencesRepository::class,
            UserSessionRepository::class,
            MediaFileRepository::class,
            SocialAccountRepository::class,
            JobQueueRepository::class,
            AuditLogRepository::class,
            AnalyticsRepository::class,
            PostRepository::class,
            DashboardRepository::class,
        ];

        foreach ($repos as $class) {
            if ($class === AnalyticsRepository::class) {
                $container->factory($class, static function (Container $c): AnalyticsRepository {
                    return new AnalyticsRepository(
                        $c->get(Connection::class),
                        $c->get(PostRepository::class)
                    );
                });
                continue;
            }
            if ($class === SocialAccountRepository::class) {
                $container->factory($class, static function (Container $c): SocialAccountRepository {
                    return new SocialAccountRepository(
                        $c->get(Connection::class),
                        $c->get(TokenCrypto::class)
                    );
                });
                continue;
            }
            $container->factory($class, static function (Container $c) use ($class): object {
                return new $class($c->get(Connection::class));
            });
        }
    }

    private static function registerServices(Container $container): void
    {
        $services = [
            AdminService::class,
            AnalyticsService::class,
            AuthService::class,
            AuthRateLimitService::class,
            InstagramOAuthService::class,
            NotificationService::class,
            PlatformApiSecretsService::class,
            SocialApiService::class,
        ];

        $container->set(SocialApiService::class, new SocialApiService());

        foreach ($services as $class) {
            if ($class === PlatformApiSecretsService::class || $class === SocialApiService::class) {
                continue;
            }
            $container->factory($class, static function (Container $c) use ($class): object {
                return new $class($c->get(Connection::class));
            });
        }

        $container->factory(PlatformApiSecretsService::class, static function (Container $c): PlatformApiSecretsService {
            return new PlatformApiSecretsService(
                $c->get(Connection::class),
                $c->get(TokenCrypto::class)
            );
        });

        $container->factory(NotificationService::class, static function (Container $c): NotificationService {
            return new NotificationService(
                $c->get(Connection::class),
                $c->get(UserRepository::class),
                $c->get(NotificationPreferenceRepository::class),
                $c->get(NotificationRepository::class)
            );
        });

        $container->factory(DashboardService::class, static function (Container $c): DashboardService {
            return new DashboardService($c->get(DashboardRepository::class));
        });

        $container->factory(ApiMetaService::class, static function (Container $c): ApiMetaService {
            return new ApiMetaService(
                $c->get(CsrfMiddleware::class),
                $c->get(UserPayloadFormatter::class)
            );
        });

        $container->factory(PostInputNormalizer::class, static function (Container $c): PostInputNormalizer {
            return new PostInputNormalizer($c->get(Connection::class), $c->get(PostRepository::class));
        });

        $container->set(SettingsPageHelper::class, new SettingsPageHelper());
        $container->set(MediaUploadHelper::class, new MediaUploadHelper());
        $container->factory(AnalyticsReportHelper::class, static function (Container $c): AnalyticsReportHelper {
            return new AnalyticsReportHelper($c->get(Connection::class));
        });
        $container->factory(DealWorkflowHelper::class, static function (Container $c): DealWorkflowHelper {
            return new DealWorkflowHelper(
                $c->get(AuditLogRepository::class),
                $c->get(NotificationService::class),
                $c->get(DealRepository::class)
            );
        });
    }

    private static function registerJobs(Container $container): void
    {
        $container->factory(PublishPostJob::class, static function (Container $c): PublishPostJob {
            return new PublishPostJob(
                $c->get(PostRepository::class),
                $c->get(SocialAccountRepository::class),
                $c->get(SocialApiService::class),
                $c->get(NotificationService::class),
                $c->get(AnalyticsRepository::class),
                $c->get(Connection::class)
            );
        });

        $container->factory(FetchAnalyticsJob::class, static function (Container $c): FetchAnalyticsJob {
            return new FetchAnalyticsJob(
                $c->get(SocialAccountRepository::class),
                $c->get(SocialApiService::class),
                $c->get(AnalyticsService::class),
                $c->get(YoutubeOAuthService::class),
                $c->get(TiktokOAuthService::class),
                $c->get(Connection::class)
            );
        });

        $container->factory(CleanupMediaJob::class, static function (Container $c): CleanupMediaJob {
            return new CleanupMediaJob($c->get(Connection::class));
        });

        $container->factory(SendNotificationJob::class, static function (Container $c): SendNotificationJob {
            return new SendNotificationJob(
                $c->get(UserRepository::class),
                $c->get(NotificationPreferenceRepository::class)
            );
        });
    }

    private static function registerControllers(Container $container): void
    {
        $controllers = [
            DashboardController::class,
            AuthController::class,
            SystemController::class,
            PostController::class,
            AnalyticsController::class,
            DealController::class,
            InvoiceController::class,
            MediaController::class,
            NotificationController::class,
            SettingsController::class,
            AdminUserController::class,
            TagController::class,
            InstagramOAuthController::class,
            GoogleAuthController::class,
            YoutubeOAuthController::class,
            TiktokOAuthController::class,
            ApiMetaController::class,
        ];

        $container->factory(DashboardController::class, static function (Container $c): DashboardController {
            return new DashboardController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(DashboardService::class)
            );
        });

        $container->factory(AuthController::class, static function (Container $c): AuthController {
            return new AuthController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(AuthService::class),
                $c->get(AuthRateLimitService::class),
                $c->get(AdminService::class),
                $c->get(UserRepository::class),
                $c->get(NotificationService::class)
            );
        });

        $container->factory(PostController::class, static function (Container $c): PostController {
            return new PostController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(PostRepository::class),
                $c->get(JobQueueRepository::class),
                $c->get(MediaFileRepository::class),
                $c->get(AnalyticsRepository::class),
                $c->get(NotificationService::class),
                $c->get(PostInputNormalizer::class)
            );
        });

        $container->factory(SettingsController::class, static function (Container $c): SettingsController {
            return new SettingsController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(SettingsPageHelper::class),
                $c->get(UserRepository::class),
                $c->get(UserPreferencesRepository::class),
                $c->get(UserSessionRepository::class),
                $c->get(NotificationPreferenceRepository::class),
                $c->get(SocialAccountRepository::class),
                $c->get(AuthService::class),
                $c->get(AdminService::class),
                $c->get(InstagramOAuthService::class),
                $c->get(YoutubeOAuthService::class),
                $c->get(TiktokOAuthService::class),
                $c->get(JobQueueRepository::class)
            );
        });

        $container->factory(MediaController::class, static function (Container $c): MediaController {
            return new MediaController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(MediaFileRepository::class),
                $c->get(MediaUploadHelper::class)
            );
        });

        $container->factory(DealController::class, static function (Container $c): DealController {
            return new DealController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(DealRepository::class),
                $c->get(InvoiceRepository::class),
                $c->get(DealWorkflowHelper::class),
                $c->get(NotificationService::class)
            );
        });

        $container->factory(AnalyticsController::class, static function (Container $c): AnalyticsController {
            return new AnalyticsController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(AnalyticsRepository::class),
                $c->get(AnalyticsReportHelper::class),
                $c->get(AnalyticsService::class)
            );
        });

        $container->factory(TagController::class, static function (Container $c): TagController {
            return new TagController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(TagRepository::class)
            );
        });

        $container->factory(InvoiceController::class, static function (Container $c): InvoiceController {
            return new InvoiceController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(InvoiceRepository::class),
                $c->get(DealRepository::class),
                $c->get(NotificationService::class)
            );
        });

        $container->factory(NotificationController::class, static function (Container $c): NotificationController {
            return new NotificationController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(NotificationRepository::class)
            );
        });

        $container->factory(AdminUserController::class, static function (Container $c): AdminUserController {
            return new AdminUserController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(UserRepository::class),
                $c->get(AdminService::class),
                $c->get(AuditLogRepository::class),
                $c->get(PlatformApiSecretsService::class),
                $c->get(SocialApiService::class),
                $c->get(AuthService::class)
            );
        });

        $container->factory(InstagramOAuthController::class, static function (Container $c): InstagramOAuthController {
            return new InstagramOAuthController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(InstagramOAuthService::class),
                $c->get(AdminService::class)
            );
        });

        $container->factory(YoutubeOAuthController::class, static function (Container $c): YoutubeOAuthController {
            return new YoutubeOAuthController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(YoutubeOAuthService::class),
                $c->get(AdminService::class)
            );
        });

        $container->factory(TiktokOAuthController::class, static function (Container $c): TiktokOAuthController {
            return new TiktokOAuthController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(TiktokOAuthService::class),
                $c->get(AdminService::class)
            );
        });

        $container->factory(GoogleAuthController::class, static function (Container $c): GoogleAuthController {
            return new GoogleAuthController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(GoogleAuthService::class),
                $c->get(UserRepository::class),
                $c->get(AuthService::class),
                $c->get(AdminService::class),
                $c->get(NotificationService::class)
            );
        });

        $container->factory(ApiMetaController::class, static function (Container $c): ApiMetaController {
            return new ApiMetaController(
                $c->get(ViewRenderer::class),
                $c->get(JsonResponder::class),
                $c->get(Connection::class),
                $c->get(ApiMetaService::class)
            );
        });

        foreach ($controllers as $class) {
            if (in_array($class, [
                DashboardController::class,
                AuthController::class,
                PostController::class,
                SettingsController::class,
                MediaController::class,
                DealController::class,
                AnalyticsController::class,
                TagController::class,
                InvoiceController::class,
                NotificationController::class,
                AdminUserController::class,
                InstagramOAuthController::class,
                GoogleAuthController::class,
                YoutubeOAuthController::class,
                TiktokOAuthController::class,
                ApiMetaController::class,
            ], true)) {
                continue;
            }
            $container->factory($class, static function (Container $c) use ($class): AbstractController {
                return new $class(
                    $c->get(ViewRenderer::class),
                    $c->get(JsonResponder::class),
                    $c->get(Connection::class)
                );
            });
        }
    }
}

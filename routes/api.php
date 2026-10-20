<?php

use App\Http\Controllers\Admin\AccountActivationLinkController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AccountStatusController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Dashboard\ActivityController;
use App\Http\Controllers\Dashboard\AgencyAppController;
use App\Http\Controllers\Dashboard\AgencyAppDeletionController;
use App\Http\Controllers\Dashboard\AgencyAppOrderController;
use App\Http\Controllers\Dashboard\ArticleController;
use App\Http\Controllers\Dashboard\ArticleDeletionController;
use App\Http\Controllers\Dashboard\ClockInController;
use App\Http\Controllers\Dashboard\ContactController;
use App\Http\Controllers\Dashboard\DocumentationController;
use App\Http\Controllers\Dashboard\DocumentationDeletionController;
use App\Http\Controllers\Dashboard\DocumentController;
use App\Http\Controllers\Dashboard\DocumentDeletionController;
use App\Http\Controllers\Dashboard\FaqController;
use App\Http\Controllers\Dashboard\FaqDeletionController;
use App\Http\Controllers\Dashboard\FaqOrderController;
use App\Http\Controllers\Dashboard\FieldActivityController;
use App\Http\Controllers\Dashboard\FieldActivityDeletionController;
use App\Http\Controllers\Dashboard\FormNumberReportController;
use App\Http\Controllers\Dashboard\HomepageController;
use App\Http\Controllers\Dashboard\MessageController;
use App\Http\Controllers\Dashboard\MessageDeletionController;
use App\Http\Controllers\Dashboard\MobileServiceStopController;
use App\Http\Controllers\Dashboard\MobileServiceStopCopyController;
use App\Http\Controllers\Dashboard\MobileServiceStopDayCopyController;
use App\Http\Controllers\Dashboard\MobileServiceStopDeletionController;
use App\Http\Controllers\Dashboard\OpeningHoursController;
use App\Http\Controllers\Dashboard\OrganizationUnitController;
use App\Http\Controllers\Dashboard\OrganizationUnitDeletionController;
use App\Http\Controllers\Dashboard\OrganizationUnitOrderController;
use App\Http\Controllers\Dashboard\OwnAccountController;
use App\Http\Controllers\Dashboard\PotentialImportController;
use App\Http\Controllers\Dashboard\PotentialMapColumnsController;
use App\Http\Controllers\Dashboard\PotentialSummaryController;
use App\Http\Controllers\Dashboard\PotentialVehicleController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\QueueArchiveController;
use App\Http\Controllers\Dashboard\QueueCounterController;
use App\Http\Controllers\Dashboard\QueueCounterDeskController;
use App\Http\Controllers\Dashboard\QueueDisplayController;
use App\Http\Controllers\Dashboard\QueueServiceController;
use App\Http\Controllers\Dashboard\QueueStationController;
use App\Http\Controllers\Dashboard\QueueSummaryController;
use App\Http\Controllers\Dashboard\QueueTicketCallController;
use App\Http\Controllers\Dashboard\QueueTicketController;
use App\Http\Controllers\Dashboard\RevenueCounterController;
use App\Http\Controllers\Dashboard\RevenueImportController;
use App\Http\Controllers\Dashboard\RevenueImportDeletionController;
use App\Http\Controllers\Dashboard\RevenueImportPreviewController;
use App\Http\Controllers\Dashboard\RevenueRealizationController;
use App\Http\Controllers\Dashboard\RevenueReportController;
use App\Http\Controllers\Dashboard\RevenueTargetController;
use App\Http\Controllers\Dashboard\ScheduleAdjustmentController;
use App\Http\Controllers\Dashboard\ScheduleAdjustmentDeletionController;
use App\Http\Controllers\Dashboard\ServiceBoardController;
use App\Http\Controllers\Dashboard\ServiceBoardOrderController;
use App\Http\Controllers\Dashboard\ServiceController;
use App\Http\Controllers\Dashboard\ServiceDeletionController;
use App\Http\Controllers\Dashboard\ServiceLocationController;
use App\Http\Controllers\Dashboard\ServiceLocationDeletionController;
use App\Http\Controllers\Dashboard\ServiceOrderController;
use App\Http\Controllers\Dashboard\SettingTimestampController;
use App\Http\Controllers\Dashboard\SiteController;
use App\Http\Controllers\Dashboard\StaticPageController;
use App\Http\Controllers\Dashboard\StatisticsController;
use App\Http\Controllers\Dashboard\ThemeController;
use App\Http\Controllers\Dashboard\ThemePreviewController;
use App\Http\Controllers\Dashboard\ThemePreviewLinkController;
use App\Http\Controllers\Dashboard\ThemeRevisionController;
use App\Http\Controllers\Dashboard\UpdateStandardController;
use App\Http\Controllers\Dashboard\UpdateStandardDeletionController;
use App\Http\Controllers\Dashboard\UpdateStandardOrderController;
use App\Http\Controllers\Dashboard\UploadController;
use App\Http\Controllers\Dashboard\VehicleController;
use App\Http\Controllers\Dashboard\VisionMissionController;
use App\Http\Controllers\PublicAgencyAppController;
use App\Http\Controllers\PublicArticleController;
use App\Http\Controllers\PublicContactController;
use App\Http\Controllers\PublicDocumentationController;
use App\Http\Controllers\PublicDocumentController;
use App\Http\Controllers\PublicFaqController;
use App\Http\Controllers\PublicHomepageController;
use App\Http\Controllers\PublicMessageController;
use App\Http\Controllers\PublicMessageLookupController;
use App\Http\Controllers\PublicMessageStatusController;
use App\Http\Controllers\PublicMobileServiceStopController;
use App\Http\Controllers\PublicOpeningHoursController;
use App\Http\Controllers\PublicOrganizationController;
use App\Http\Controllers\PublicPotentialMapController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\PublicQueueTicketController;
use App\Http\Controllers\PublicRevenueReportController;
use App\Http\Controllers\PublicRevenueSummaryController;
use App\Http\Controllers\PublicServiceBoardController;
use App\Http\Controllers\PublicServiceController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\PublicStaticPageController;
use App\Http\Controllers\PublicStatisticsController;
use App\Http\Controllers\PublicVehicleController;
use App\Http\Controllers\PublicVisionMissionController;
use App\Http\Controllers\QueueDisplayDataController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\AgencyApp;
use App\Models\Article;
use App\Models\Document;
use App\Models\Documentation;
use App\Models\Faq;
use App\Models\FieldActivity;
use App\Models\Message;
use App\Models\MobileServiceStop;
use App\Models\OrganizationUnit;
use App\Models\PotentialImport;
use App\Models\QueueCounter;
use App\Models\QueueDisplay;
use App\Models\QueueService;
use App\Models\QueueTicket;
use App\Models\RevenueImport;
use App\Models\ScheduleAdjustment;
use App\Models\Service;
use App\Models\ServiceLocation;
use App\Models\Setting;
use App\Models\StaticPage;
use App\Models\UpdateStandard;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

Route::get('/health', function (): JsonResponse {
    return response()->json([
        'status' => 'ok',
    ]);
});

/*
 * Employee sign-in with a cookie session (Sanctum SPA authentication). The
 * login page first calls GET /sanctum/csrf-cookie and then sends the
 * X-XSRF-TOKEN header with every state-changing request. Inactive accounts
 * are signed out by EnsureAccountIsActive, which runs on the whole api group.
 */
Route::post('/masuk', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:login')
    ->name('auth.masuk');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/sesi', [AuthenticatedSessionController::class, 'show'])->name('auth.sesi');
    Route::post('/keluar', [AuthenticatedSessionController::class, 'destroy'])->name('auth.keluar');

    /*
     * Account management for administrators (UserPolicy). Passwords are
     * never set here: the owner chooses one through an activation link.
     */
    Route::prefix('akun')->name('akun.')->whereUlid('user')->group(function (): void {
        Route::get('/', [AccountController::class, 'index'])->can('viewAny', User::class)->name('index');
        Route::post('/', [AccountController::class, 'store'])->can('create', User::class)->name('store');
        Route::patch('/{user}', [AccountController::class, 'update'])->can('update', 'user')->name('update');
        Route::put('/{user}/status', [AccountStatusController::class, 'update'])->can('changeStatus', 'user')->name('status');
        Route::post('/{user}/tautan-aktivasi', [AccountActivationLinkController::class, 'store'])
            ->can('issueActivationLink', 'user')
            ->name('tautan-aktivasi');
    });

    /*
     * Dashboard content kept on the server, following the dashboard's REST
     * contract (docs/dashboard.md). FAQ entries belong to the Tanya Jawab
     * module (FaqPolicy); the other collections still live in the browser.
     */
    Route::prefix('koleksi/faq')->name('koleksi.faq.')->whereUlid('faq')->group(function (): void {
        Route::get('/', [FaqController::class, 'index'])->can('viewAny', Faq::class)->name('index');
        Route::post('/', [FaqController::class, 'store'])->can('create', Faq::class)->name('store');
        Route::put('/urutan', [FaqOrderController::class, 'update'])->can('reorder', Faq::class)->name('urutan');
        Route::post('/hapus', [FaqDeletionController::class, 'store'])->can('deleteAny', Faq::class)->name('hapus');
        Route::get('/{faq}', [FaqController::class, 'show'])->can('view', 'faq')->name('show');
        Route::patch('/{faq}', [FaqController::class, 'update'])->can('update', 'faq')->name('update');
    });

    /*
     * Layanan Luar Kantor: Samsat Keliling stops, fixed outlets and temporary
     * counters (MobileServiceStopPolicy, D-043). The dashboard sorts them by
     * date and time, so there is no reorder endpoint; salin-minggu copies a
     * week of Samsat Keliling stops to the next week and salin-hari one day's
     * stops to other days (D-044). urutan-papan keeps, per date, the order of
     * the entries on the homepage board only (D-053).
     */
    Route::prefix('koleksi/keliling')->name('koleksi.keliling.')->whereUlid('stop')->group(function (): void {
        Route::get('/', [MobileServiceStopController::class, 'index'])->can('viewAny', MobileServiceStop::class)->name('index');
        Route::post('/', [MobileServiceStopController::class, 'store'])->can('create', MobileServiceStop::class)->name('store');
        Route::post('/hapus', [MobileServiceStopDeletionController::class, 'store'])->can('deleteAny', MobileServiceStop::class)->name('hapus');
        Route::post('/salin-minggu', MobileServiceStopCopyController::class)->can('create', MobileServiceStop::class)->name('salin-minggu');
        Route::post('/salin-hari', MobileServiceStopDayCopyController::class)->can('create', MobileServiceStop::class)->name('salin-hari');
        Route::get('/urutan-papan', [ServiceBoardOrderController::class, 'index'])->can('viewAny', MobileServiceStop::class)->name('urutan-papan.index');
        Route::put('/urutan-papan/{tanggal}', [ServiceBoardOrderController::class, 'update'])->can('reorder', MobileServiceStop::class)->name('urutan-papan.update');
        Route::get('/{stop}', [MobileServiceStopController::class, 'show'])->can('view', 'stop')->name('show');
        Route::patch('/{stop}', [MobileServiceStopController::class, 'update'])->can('update', 'stop')->name('update');
    });

    // The list of places picked for service outside the office (ServiceLocationPolicy, D-044)
    Route::prefix('koleksi/lokasi')->name('koleksi.lokasi.')->whereUlid('location')->group(function (): void {
        Route::get('/', [ServiceLocationController::class, 'index'])->can('viewAny', ServiceLocation::class)->name('index');
        Route::post('/', [ServiceLocationController::class, 'store'])->can('create', ServiceLocation::class)->name('store');
        Route::post('/hapus', [ServiceLocationDeletionController::class, 'store'])->can('deleteAny', ServiceLocation::class)->name('hapus');
        Route::get('/{location}', [ServiceLocationController::class, 'show'])->can('view', 'location')->name('show');
        Route::patch('/{location}', [ServiceLocationController::class, 'update'])->can('update', 'location')->name('update');
    });

    /*
     * Jam Pelayanan: the opening hours document (SettingPolicy) and the
     * schedule adjustments (ScheduleAdjustmentPolicy). GET /pengaturan tells
     * every employee when each server-side settings document was last saved.
     */
    Route::get('/pengaturan', SettingTimestampController::class)->name('pengaturan.waktu');
    // A quoted value reaches the policy as a string instead of naming a route parameter.
    $openingHours = "'".Setting::OPENING_HOURS."'";
    Route::get('/pengaturan/jam', [OpeningHoursController::class, 'show'])
        ->can('view', [Setting::class, $openingHours])
        ->name('pengaturan.jam.show');
    Route::put('/pengaturan/jam', [OpeningHoursController::class, 'update'])
        ->can('update', [Setting::class, $openingHours])
        ->name('pengaturan.jam.update');

    // Papan Layanan, the homepage's service board (SettingPolicy).
    $serviceBoard = "'".Setting::SERVICE_BOARD."'";
    Route::get('/pengaturan/papan', [ServiceBoardController::class, 'show'])
        ->can('view', [Setting::class, $serviceBoard])
        ->name('pengaturan.papan.show');
    Route::put('/pengaturan/papan', [ServiceBoardController::class, 'update'])
        ->can('update', [Setting::class, $serviceBoard])
        ->name('pengaturan.papan.update');

    // Statistik Layanan, the homepage's transparency dashboard (SettingPolicy).
    $statistics = "'".Setting::STATISTICS."'";
    Route::get('/pengaturan/statistik', [StatisticsController::class, 'show'])
        ->can('view', [Setting::class, $statistics])
        ->name('pengaturan.statistik.show');
    Route::put('/pengaturan/statistik', [StatisticsController::class, 'update'])
        ->can('update', [Setting::class, $statistics])
        ->name('pengaturan.statistik.update');

    // Kontak & Kanal, the portal's footer and Kontak page (SettingPolicy).
    $contact = "'".Setting::CONTACT."'";
    Route::get('/pengaturan/kontak', [ContactController::class, 'show'])
        ->can('view', [Setting::class, $contact])
        ->name('pengaturan.kontak.show');
    Route::put('/pengaturan/kontak', [ContactController::class, 'update'])
        ->can('update', [Setting::class, $contact])
        ->name('pengaturan.kontak.update');

    // Visi & Misi, named as the dashboard's setting (SettingPolicy).
    $visionMission = "'".Setting::VISION_MISSION."'";
    Route::get('/pengaturan/visiMisi', [VisionMissionController::class, 'show'])
        ->can('view', [Setting::class, $visionMission])
        ->name('pengaturan.visi-misi.show');
    Route::put('/pengaturan/visiMisi', [VisionMissionController::class, 'update'])
        ->can('update', [Setting::class, $visionMission])
        ->name('pengaturan.visi-misi.update');

    // Tentang UPTD, the portal's Profil page (SettingPolicy).
    $profile = "'".Setting::PROFILE."'";
    Route::get('/pengaturan/profil', [ProfileController::class, 'show'])
        ->can('view', [Setting::class, $profile])
        ->name('pengaturan.profil.show');
    Route::put('/pengaturan/profil', [ProfileController::class, 'update'])
        ->can('update', [Setting::class, $profile])
        ->name('pengaturan.profil.update');

    // Beranda, the homepage's texts (SettingPolicy).
    $homepage = "'".Setting::HOMEPAGE."'";
    Route::get('/pengaturan/beranda', [HomepageController::class, 'show'])
        ->can('view', [Setting::class, $homepage])
        ->name('pengaturan.beranda.show');
    Route::put('/pengaturan/beranda', [HomepageController::class, 'update'])
        ->can('update', [Setting::class, $homepage])
        ->name('pengaturan.beranda.update');

    // Pengaturan Situs: every employee reads it, only administrators save it (SettingPolicy, D-026).
    $site = "'".Setting::SITE."'";
    Route::get('/pengaturan/situs', [SiteController::class, 'show'])
        ->can('view', [Setting::class, $site])
        ->name('pengaturan.situs.show');
    Route::put('/pengaturan/situs', [SiteController::class, 'update'])
        ->can('update', [Setting::class, $site])
        ->name('pengaturan.situs.update');

    // Tampilan, the portal and dashboard themes: every employee reads them, only administrators save, preview them and see their history (D-057).
    $theme = "'".Setting::THEME."'";
    Route::get('/pengaturan/tema', [ThemeController::class, 'show'])
        ->can('view', [Setting::class, $theme])
        ->name('pengaturan.tema.show');
    Route::put('/pengaturan/tema', [ThemeController::class, 'update'])
        ->can('update', [Setting::class, $theme])
        ->name('pengaturan.tema.update');
    Route::post('/pengaturan/tema/pratinjau', ThemePreviewController::class)
        ->can('update', [Setting::class, $theme])
        ->name('pengaturan.tema.pratinjau');
    Route::post('/pengaturan/tema/tautan-pratinjau', [ThemePreviewLinkController::class, 'store'])
        ->can('update', [Setting::class, $theme])
        ->name('pengaturan.tema.tautan-pratinjau');
    Route::get('/pengaturan/tema/riwayat', ThemeRevisionController::class)
        ->can('update', [Setting::class, $theme])
        ->name('pengaturan.tema.riwayat');

    // The signed-in employee's own profile, the dashboard's "akun" settings (D-027).
    Route::get('/pengaturan/akun', [OwnAccountController::class, 'show'])->name('pengaturan.akun.show');
    Route::put('/pengaturan/akun', [OwnAccountController::class, 'update'])->name('pengaturan.akun.update');

    Route::prefix('koleksi/penyesuaian')->name('koleksi.penyesuaian.')->whereUlid('adjustment')->group(function (): void {
        Route::get('/', [ScheduleAdjustmentController::class, 'index'])->can('viewAny', ScheduleAdjustment::class)->name('index');
        Route::post('/', [ScheduleAdjustmentController::class, 'store'])->can('create', ScheduleAdjustment::class)->name('store');
        Route::post('/hapus', [ScheduleAdjustmentDeletionController::class, 'store'])->can('deleteAny', ScheduleAdjustment::class)->name('hapus');
        Route::get('/{adjustment}', [ScheduleAdjustmentController::class, 'show'])->can('view', 'adjustment')->name('show');
        Route::patch('/{adjustment}', [ScheduleAdjustmentController::class, 'update'])->can('update', 'adjustment')->name('update');
    });

    /*
     * Pesan & Pengaduan (MessagePolicy). Only administrators delete; the
     * sender's fields cannot be changed, and responses are only added.
     */
    Route::prefix('koleksi/pesan')->name('koleksi.pesan.')->whereUlid('message')->group(function (): void {
        Route::get('/', [MessageController::class, 'index'])->can('viewAny', Message::class)->name('index');
        Route::post('/', [MessageController::class, 'store'])->can('create', Message::class)->name('store');
        Route::post('/hapus', [MessageDeletionController::class, 'store'])->can('deleteAny', Message::class)->name('hapus');
        Route::get('/{message}', [MessageController::class, 'show'])->can('view', 'message')->name('show');
        Route::patch('/{message}', [MessageController::class, 'update'])->can('update', 'message')->name('update');
    });

    /*
     * Persyaratan Layanan (ServicePolicy) with the publication workflow:
     * module managers prepare, submit and review; administrators approve,
     * withdraw, reorder and delete (D-022).
     */
    Route::prefix('koleksi/layanan')->name('koleksi.layanan.')->whereUlid('service')->group(function (): void {
        Route::get('/', [ServiceController::class, 'index'])->can('viewAny', Service::class)->name('index');
        Route::post('/', [ServiceController::class, 'store'])->can('create', Service::class)->name('store');
        Route::put('/urutan', [ServiceOrderController::class, 'update'])->can('reorder', Service::class)->name('urutan');
        Route::post('/hapus', [ServiceDeletionController::class, 'store'])->can('deleteAny', Service::class)->name('hapus');
        Route::get('/{service}', [ServiceController::class, 'show'])->can('view', 'service')->name('show');
        Route::patch('/{service}', [ServiceController::class, 'update'])->can('update', 'service')->name('update');
    });

    /*
     * Berita & Pengumuman (ArticlePolicy) with the publication workflow.
     * Module managers take every step themselves, publishing included;
     * only administrators delete (D-031). Articles are listed by date, so
     * there is no reorder endpoint.
     */
    Route::prefix('koleksi/berita')->name('koleksi.berita.')->whereUlid('article')->group(function (): void {
        Route::get('/', [ArticleController::class, 'index'])->can('viewAny', Article::class)->name('index');
        Route::post('/', [ArticleController::class, 'store'])->can('create', Article::class)->name('store');
        Route::post('/hapus', [ArticleDeletionController::class, 'store'])->can('deleteAny', Article::class)->name('hapus');
        Route::get('/{article}', [ArticleController::class, 'show'])->can('view', 'article')->name('show');
        Route::patch('/{article}', [ArticleController::class, 'update'])->can('update', 'article')->name('update');
    });

    /*
     * Dokumentasi Kegiatan (DocumentationPolicy) with the publication
     * workflow, as for news: module managers take every step themselves;
     * only administrators delete (D-032). Activities are listed by date, so
     * there is no reorder endpoint.
     */
    Route::prefix('koleksi/dokumentasi')->name('koleksi.dokumentasi.')->whereUlid('documentation')->group(function (): void {
        Route::get('/', [DocumentationController::class, 'index'])->can('viewAny', Documentation::class)->name('index');
        Route::post('/', [DocumentationController::class, 'store'])->can('create', Documentation::class)->name('store');
        Route::post('/hapus', [DocumentationDeletionController::class, 'store'])->can('deleteAny', Documentation::class)->name('hapus');
        Route::get('/{documentation}', [DocumentationController::class, 'show'])->can('view', 'documentation')->name('show');
        Route::patch('/{documentation}', [DocumentationController::class, 'update'])->can('update', 'documentation')->name('update');
    });

    /*
     * Struktur Organisasi (OrganizationUnitPolicy): module managers add,
     * change, reorder and delete units, which reach the portal when saved
     * (D-033). Units that others report to cannot be deleted.
     */
    Route::prefix('koleksi/unit')->name('koleksi.unit.')->whereUlid('unit')->group(function (): void {
        Route::get('/', [OrganizationUnitController::class, 'index'])->can('viewAny', OrganizationUnit::class)->name('index');
        Route::post('/', [OrganizationUnitController::class, 'store'])->can('create', OrganizationUnit::class)->name('store');
        Route::put('/urutan', [OrganizationUnitOrderController::class, 'update'])->can('reorder', OrganizationUnit::class)->name('urutan');
        Route::post('/hapus', [OrganizationUnitDeletionController::class, 'store'])->can('deleteAny', OrganizationUnit::class)->name('hapus');
        Route::get('/{unit}', [OrganizationUnitController::class, 'show'])->can('view', 'unit')->name('show');
        Route::patch('/{unit}', [OrganizationUnitController::class, 'update'])->can('update', 'unit')->name('update');
    });

    /*
     * Aplikasi & Situs (AgencyAppPolicy, D-054): module managers add, change,
     * reorder and delete the apps and websites of BPAD and the UPTD, which
     * reach the portal when saved.
     */
    Route::prefix('koleksi/aplikasi')->name('koleksi.aplikasi.')->whereUlid('agencyApp')->group(function (): void {
        Route::get('/', [AgencyAppController::class, 'index'])->can('viewAny', AgencyApp::class)->name('index');
        Route::post('/', [AgencyAppController::class, 'store'])->can('create', AgencyApp::class)->name('store');
        Route::put('/urutan', [AgencyAppOrderController::class, 'update'])->can('reorder', AgencyApp::class)->name('urutan');
        Route::post('/hapus', [AgencyAppDeletionController::class, 'store'])->can('deleteAny', AgencyApp::class)->name('hapus');
        Route::get('/{agencyApp}', [AgencyAppController::class, 'show'])->can('view', 'agencyApp')->name('show');
        Route::patch('/{agencyApp}', [AgencyAppController::class, 'update'])->can('update', 'agencyApp')->name('update');
    });

    /*
     * Regulasi & Unduhan (DocumentPolicy) with the publication workflow, as
     * for services: module managers prepare, submit and review; administrators
     * approve, withdraw and delete (D-030). The portal sorts documents by
     * category, so there is no reorder endpoint.
     */
    Route::prefix('koleksi/dokumen')->name('koleksi.dokumen.')->whereUlid('document')->group(function (): void {
        Route::get('/', [DocumentController::class, 'index'])->can('viewAny', Document::class)->name('index');
        Route::post('/', [DocumentController::class, 'store'])->can('create', Document::class)->name('store');
        Route::post('/hapus', [DocumentDeletionController::class, 'store'])->can('deleteAny', Document::class)->name('hapus');
        Route::get('/{document}', [DocumentController::class, 'show'])->can('view', 'document')->name('show');
        Route::patch('/{document}', [DocumentController::class, 'update'])->can('update', 'document')->name('update');
    });

    /*
     * Halaman Statis (StaticPagePolicy): the portal's long-text pages. Each
     * one is a page of the portal, so they are edited but not added,
     * reordered or deleted (D-024).
     */
    Route::prefix('koleksi/halaman')->name('koleksi.halaman.')->whereUlid('page')->group(function (): void {
        Route::get('/', [StaticPageController::class, 'index'])->can('viewAny', StaticPage::class)->name('index');
        Route::get('/{page}', [StaticPageController::class, 'show'])->can('view', 'page')->name('show');
        Route::patch('/{page}', [StaticPageController::class, 'update'])->can('update', 'page')->name('update');
    });

    /*
     * Jadwal Pemutakhiran (UpdateStandardPolicy): every employee lists the
     * standards of the modules they manage; managers of the module see and
     * change all of them (D-025).
     */
    Route::prefix('koleksi/pemutakhiran')->name('koleksi.pemutakhiran.')->whereUlid('standard')->group(function (): void {
        Route::get('/', [UpdateStandardController::class, 'index'])->can('viewAny', UpdateStandard::class)->name('index');
        Route::post('/', [UpdateStandardController::class, 'store'])->can('create', UpdateStandard::class)->name('store');
        Route::put('/urutan', [UpdateStandardOrderController::class, 'update'])->can('reorder', UpdateStandard::class)->name('urutan');
        Route::post('/hapus', [UpdateStandardDeletionController::class, 'store'])->can('deleteAny', UpdateStandard::class)->name('hapus');
        Route::get('/{standard}', [UpdateStandardController::class, 'show'])->can('view', 'standard')->name('show');
        Route::patch('/{standard}', [UpdateStandardController::class, 'update'])->can('update', 'standard')->name('update');
    });

    /*
     * Uploads (D-028): the target in the request decides the bucket, the
     * accepted types and who may upload (UploadPolicy). Private files are
     * only read through this application.
     */
    Route::post('/unggah', [UploadController::class, 'store'])->middleware('throttle:uploads')->name('unggah.store');
    Route::get('/berkas/{upload}', [UploadController::class, 'show'])->whereUlid('upload')->can('view', 'upload')->name('berkas.show');

    /*
     * Absensi Lapangan (D-034). Administrators set up field activities and
     * read the clock-ins; officers clock in from the /absen app at the
     * activities assigned to them (FieldActivityPolicy).
     */
    Route::prefix('absensi/kegiatan')->name('absensi.kegiatan.')->whereUlid('activity')->group(function (): void {
        Route::get('/', [FieldActivityController::class, 'index'])->can('viewAny', FieldActivity::class)->name('index');
        Route::post('/', [FieldActivityController::class, 'store'])->can('create', FieldActivity::class)->name('store');
        Route::post('/hapus', [FieldActivityDeletionController::class, 'store'])->can('deleteAny', FieldActivity::class)->name('hapus');
        Route::get('/{activity}', [FieldActivityController::class, 'show'])->can('view', 'activity')->name('show');
        Route::patch('/{activity}', [FieldActivityController::class, 'update'])->can('update', 'activity')->name('update');
    });
    Route::get('/absen', [ClockInController::class, 'index'])->name('absen.index');
    Route::post('/absen/{activity}', [ClockInController::class, 'store'])
        ->whereUlid('activity')
        ->can('clockIn', 'activity')
        ->middleware('throttle:uploads')
        ->name('absen.store');

    /*
     * Laporan Penerimaan (D-047): STS files exported by BPAD's system are
     * imported per counter, without the taxpayers' identities, and summed
     * into daily and monthly reports (RevenueImportPolicy).
     */
    Route::prefix('penerimaan')->name('penerimaan.')->group(function (): void {
        Route::get('/laporan', RevenueReportController::class)->can('viewAny', RevenueImport::class)->name('laporan');
        Route::get('/loket', RevenueCounterController::class)->can('viewAny', RevenueImport::class)->name('loket');
        Route::get('/impor', [RevenueImportController::class, 'index'])->can('viewAny', RevenueImport::class)->name('impor.index');
        Route::post('/impor/pratinjau', RevenueImportPreviewController::class)
            ->can('create', RevenueImport::class)
            ->middleware('throttle:uploads')
            ->name('impor.pratinjau');
        Route::post('/impor', [RevenueImportController::class, 'store'])
            ->can('create', RevenueImport::class)
            ->middleware('throttle:uploads')
            ->name('impor.store');
        // Shown on the portal only once an Administrator has checked it (D-048)
        Route::patch('/impor/{revenueImport}', [RevenueImportController::class, 'update'])
            ->can('publish', RevenueImport::class)
            ->name('impor.update');
        Route::post('/impor/hapus', [RevenueImportDeletionController::class, 'store'])->can('deleteAny', RevenueImport::class)->name('impor.hapus');
        // Nomoratur (D-071): the SKPD form numbers used per counter and day
        Route::get('/nomoratur', FormNumberReportController::class)->can('viewAny', RevenueImport::class)->name('nomoratur');
        // Realisasi target (D-068): Form I of a month against the year's target
        Route::get('/realisasi', [RevenueRealizationController::class, 'show'])->can('viewAny', RevenueImport::class)->name('realisasi');
        Route::get('/realisasi/unduh', [RevenueRealizationController::class, 'download'])->can('viewAny', RevenueImport::class)->name('realisasi.unduh');
        Route::put('/target/{tahun}', [RevenueTargetController::class, 'update'])
            ->where('tahun', '20[0-9]{2}')
            ->can('updateTargets', RevenueImport::class)
            ->name('target.update');
    });

    /*
     * Antrean Online (D-051): queue numbers printed at the information desk
     * and the services they are printed for (QueueTicketPolicy,
     * QueueServicePolicy; services are changed by administrators only).
     */
    Route::prefix('antrean')->name('antrean.')->group(function (): void {
        Route::get('/layanan', [QueueServiceController::class, 'index'])->can('viewAny', QueueService::class)->name('layanan.index');
        Route::put('/layanan/{queueService}', [QueueServiceController::class, 'update'])
            ->can('update', QueueService::class)
            ->whereUlid('queueService')
            ->name('layanan.update');
        Route::get('/tiket', [QueueTicketController::class, 'index'])->can('viewAny', QueueTicket::class)->name('tiket.index');
        Route::post('/tiket', [QueueTicketController::class, 'store'])->can('create', QueueTicket::class)->name('tiket.store');
        Route::post('/tiket/{queueTicket}/cetak-ulang', [QueueTicketController::class, 'reprint'])
            ->can('create', QueueTicket::class)
            ->whereUlid('queueTicket')
            ->name('tiket.cetak-ulang');
        Route::post('/tiket/{queueTicket}/batal', [QueueTicketController::class, 'cancel'])
            ->can('cancel', QueueTicket::class)
            ->whereUlid('queueTicket')
            ->name('tiket.batal');
        Route::get('/cari', [QueueTicketController::class, 'search'])->can('call', QueueTicket::class)->name('cari');

        // Slice 2: counters calling numbers, and the waiting-room TV displays (administrators)
        Route::get('/loket', [QueueCounterController::class, 'index'])->can('viewAny', QueueCounter::class)->name('loket.index');
        Route::post('/loket', [QueueCounterController::class, 'store'])->can('manage', QueueCounter::class)->name('loket.store');
        Route::whereUlid('queueCounter')->group(function (): void {
            Route::put('/loket/{queueCounter}', [QueueCounterController::class, 'update'])->can('manage', QueueCounter::class)->name('loket.update');
            Route::delete('/loket/{queueCounter}', [QueueCounterController::class, 'destroy'])->can('manage', QueueCounter::class)->name('loket.destroy');
            Route::get('/loket/{queueCounter}/antrean', [QueueCounterDeskController::class, 'show'])->can('call', QueueTicket::class)->name('loket.antrean');
            Route::post('/loket/{queueCounter}/panggil', [QueueCounterDeskController::class, 'call'])->can('call', QueueTicket::class)->name('loket.panggil');
        });
        Route::whereUlid('queueTicket')->group(function (): void {
            Route::post('/tiket/{queueTicket}/panggil-ulang', [QueueTicketCallController::class, 'recall'])->can('call', QueueTicket::class)->name('tiket.panggil-ulang');
            Route::post('/tiket/{queueTicket}/tunda', [QueueTicketCallController::class, 'postpone'])->can('call', QueueTicket::class)->name('tiket.tunda');
            Route::post('/tiket/{queueTicket}/selesai', [QueueTicketCallController::class, 'finish'])->can('call', QueueTicket::class)->name('tiket.selesai');
            // Slice 3: stations sending documents for a data change, and undoing a marking
            Route::post('/tiket/{queueTicket}/perubahan-data', [QueueStationController::class, 'requireDataChange'])->can('mark', QueueTicket::class)->name('tiket.perubahan-data');
            Route::post('/tiket/{queueTicket}/urungkan', [QueueStationController::class, 'undo'])->can('mark', QueueTicket::class)->name('tiket.urungkan');
        });
        // Slice 3: the stations that mark stages done (Penetapan, Server, Pelunasan, Cetak SKPD, Cetak STNK)
        Route::get('/pos/{pos}', [QueueStationController::class, 'show'])->can('mark', QueueTicket::class)->name('pos.show');
        Route::post('/pos/{pos}/tandai', [QueueStationController::class, 'mark'])->can('mark', QueueTicket::class)->name('pos.tandai');
        // Slice 4: the archive, a ticket's history, deleting finished tickets (administrators), and the summary
        Route::get('/arsip', [QueueArchiveController::class, 'index'])->can('viewAny', QueueTicket::class)->name('arsip');
        Route::whereUlid('queueTicket')->group(function (): void {
            Route::get('/tiket/{queueTicket}', [QueueArchiveController::class, 'show'])->can('viewAny', QueueTicket::class)->name('tiket.show');
            Route::delete('/tiket/{queueTicket}', [QueueArchiveController::class, 'destroy'])->can('delete', QueueTicket::class)->name('tiket.destroy');
        });
        Route::get('/ringkasan', [QueueSummaryController::class, 'show'])->can('viewAny', QueueTicket::class)->name('ringkasan');
        Route::get('/ringkasan/tiket', [QueueSummaryController::class, 'tickets'])->can('viewAny', QueueTicket::class)->name('ringkasan.tiket');
        Route::get('/layar', [QueueDisplayController::class, 'index'])->can('manage', QueueDisplay::class)->name('layar.index');
        Route::post('/layar', [QueueDisplayController::class, 'store'])->can('manage', QueueDisplay::class)->name('layar.store');
        Route::delete('/layar/{queueDisplay}', [QueueDisplayController::class, 'destroy'])
            ->can('manage', QueueDisplay::class)
            ->whereUlid('queueDisplay')
            ->name('layar.destroy');
    });

    /*
     * Pemetaan PKB (D-052): BPAD's potential-tax file imported in parts, the
     * summary for the map, and the vehicles with their owners for collection
     * (PotentialImportPolicy; deleting the data is for administrators).
     */
    Route::prefix('pemetaan')->name('pemetaan.')->group(function (): void {
        Route::get('/impor', [PotentialImportController::class, 'show'])->can('viewAny', PotentialImport::class)->name('impor.show');
        Route::post('/impor', [PotentialImportController::class, 'store'])->can('create', PotentialImport::class)->name('impor.store');
        Route::delete('/impor', [PotentialImportController::class, 'destroy'])->can('delete', PotentialImport::class)->name('impor.destroy');
        // Slice 2: shown on the portal only once an Administrator has checked it
        Route::patch('/impor', [PotentialImportController::class, 'update'])->can('publish', PotentialImport::class)->name('impor.update');
        Route::whereUlid('potentialImport')->group(function (): void {
            Route::post('/impor/{potentialImport}/baris', [PotentialImportController::class, 'rows'])->can('create', PotentialImport::class)->name('impor.baris');
            Route::post('/impor/{potentialImport}/selesai', [PotentialImportController::class, 'finish'])->can('create', PotentialImport::class)->name('impor.selesai');
        });
        Route::get('/ringkasan', PotentialSummaryController::class)->can('viewAny', PotentialImport::class)->name('ringkasan');
        // The columns of the tables, which the portal follows too: chosen by Administrators
        Route::get('/kolom', [PotentialMapColumnsController::class, 'show'])->can('viewAny', PotentialImport::class)->name('kolom.show');
        Route::put('/kolom', [PotentialMapColumnsController::class, 'update'])->can('arrangeColumns', PotentialImport::class)->name('kolom.update');
        Route::get('/kendaraan', [PotentialVehicleController::class, 'index'])->can('viewAny', PotentialImport::class)->name('kendaraan.index');
        Route::get('/kendaraan/{potentialVehicle}', [PotentialVehicleController::class, 'show'])
            ->can('viewAny', PotentialImport::class)
            ->whereNumber('potentialVehicle')
            ->name('kendaraan.show');
    });

    // Vehicle data for the Kalkulator PKB from the private NJKB API (D-041)
    Route::get('/kendaraan/{nopol}', VehicleController::class)->middleware('throttle:kendaraan')->name('kendaraan.show');

    Route::get('/aktivitas', ActivityController::class)->name('aktivitas');
});

/*
 * Anonymous, read-only content for the public portal. No session is started,
 * so visitors never receive a cookie from these endpoints.
 */
Route::prefix('public')
    ->name('public.')
    ->middleware('throttle:public-api')
    ->withoutMiddleware([EnsureFrontendRequestsAreStateful::class, EnsureAccountIsActive::class])
    ->group(function (): void {
        Route::get('/faq', PublicFaqController::class)->name('faq.index');
        Route::get('/keliling', PublicMobileServiceStopController::class)->name('keliling.index');
        Route::get('/jam', PublicOpeningHoursController::class)->name('jam.show');
        Route::get('/papan', PublicServiceBoardController::class)->name('papan.show');
        Route::get('/statistik', PublicStatisticsController::class)->name('statistik.show');
        Route::get('/kontak', PublicContactController::class)->name('kontak.show');
        Route::get('/visi-misi', PublicVisionMissionController::class)->name('visi-misi.show');
        Route::get('/profil', PublicProfileController::class)->name('profil.show');
        Route::get('/layanan', PublicServiceController::class)->name('layanan.index');
        Route::get('/dokumen', PublicDocumentController::class)->name('dokumen.index');
        Route::get('/dokumentasi', PublicDocumentationController::class)->name('dokumentasi.index');
        Route::get('/struktur', PublicOrganizationController::class)->name('struktur.show');
        Route::get('/aplikasi', PublicAgencyAppController::class)->name('aplikasi.show');
        Route::get('/berita', [PublicArticleController::class, 'index'])->name('berita.index');
        Route::get('/berita/{slug}', [PublicArticleController::class, 'show'])
            ->where('slug', Article::SLUG_PATTERN)
            ->name('berita.show');
        Route::get('/beranda', PublicHomepageController::class)->name('beranda.show');
        Route::get('/situs', PublicSiteController::class)->name('situs.show');
        Route::get('/halaman/{slug}', PublicStaticPageController::class)
            ->whereIn('slug', array_keys(StaticPage::PAGES))
            ->name('halaman.show');
        // Laporan Penerimaan: the imports shown on the portal, small groups masked (D-048)
        Route::get('/penerimaan', PublicRevenueReportController::class)->middleware('throttle:public-penerimaan')->name('penerimaan.show');
        // The homepage's summary of the year's published revenue (D-055)
        Route::get('/penerimaan/ringkasan', PublicRevenueSummaryController::class)->middleware('throttle:public-penerimaan')->name('penerimaan.ringkasan');
        // Pemetaan PKB: figures per kecamatan and kelurahan without identities, small groups hidden (D-052)
        Route::get('/pemetaan', PublicPotentialMapController::class)->name('pemetaan.show');
        // Antrean Online: the taxpayer's tracking page behind a ticket's QR, by its random code only (D-051)
        Route::get('/antrean/{kode}', PublicQueueTicketController::class)->where('kode', QueueTicket::TRACKING_CODE_PATTERN)->name('antrean.show');
        // The contact form; also limited to 5 messages per hour per address (D-016).
        Route::post('/pesan', PublicMessageController::class)->middleware('throttle:public-messages')->name('pesan.store');
        // The sender's tracking page for a message, by its random code only (D-058)
        Route::get('/pesan/{kode}', PublicMessageStatusController::class)->where('kode', Message::TRACKING_CODE_PATTERN)->name('pesan.show');
        // The way to it from the contact page: the ticket number and the contact address the message was sent with
        Route::post('/pesan/lacak', PublicMessageLookupController::class)->middleware('throttle:public-message-lookups')->name('pesan.lookup');
        // The portal's tax check: plate number and the last five chassis characters (D-041)
        Route::post('/kendaraan', PublicVehicleController::class)->middleware('throttle:public-kendaraan')->name('kendaraan.check');
    });

/*
 * Antrean Online (D-051): the waiting-room TV display, for a display set up
 * by an administrator. Its setup token is the bearer token; there is no
 * session or cookie.
 */
Route::get('/layar', QueueDisplayDataController::class)
    ->middleware('throttle:public-api')
    ->withoutMiddleware([EnsureFrontendRequestsAreStateful::class, EnsureAccountIsActive::class])
    ->name('layar.data');

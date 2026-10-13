<?php

use App\Http\Controllers\Admin\AccountActivationLinkController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AccountStatusController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Dashboard\ActivityController;
use App\Http\Controllers\Dashboard\ContactController;
use App\Http\Controllers\Dashboard\FaqController;
use App\Http\Controllers\Dashboard\FaqDeletionController;
use App\Http\Controllers\Dashboard\FaqOrderController;
use App\Http\Controllers\Dashboard\MessageController;
use App\Http\Controllers\Dashboard\MessageDeletionController;
use App\Http\Controllers\Dashboard\MobileServiceStopController;
use App\Http\Controllers\Dashboard\MobileServiceStopDeletionController;
use App\Http\Controllers\Dashboard\OpeningHoursController;
use App\Http\Controllers\Dashboard\ScheduleAdjustmentController;
use App\Http\Controllers\Dashboard\ScheduleAdjustmentDeletionController;
use App\Http\Controllers\Dashboard\ServiceBoardController;
use App\Http\Controllers\Dashboard\SettingTimestampController;
use App\Http\Controllers\Dashboard\StatisticsController;
use App\Http\Controllers\Dashboard\TariffController;
use App\Http\Controllers\PublicContactController;
use App\Http\Controllers\PublicFaqController;
use App\Http\Controllers\PublicMessageController;
use App\Http\Controllers\PublicMobileServiceStopController;
use App\Http\Controllers\PublicOpeningHoursController;
use App\Http\Controllers\PublicServiceBoardController;
use App\Http\Controllers\PublicStatisticsController;
use App\Http\Controllers\PublicTariffController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\Faq;
use App\Models\Message;
use App\Models\MobileServiceStop;
use App\Models\ScheduleAdjustment;
use App\Models\Setting;
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
     * Samsat Keliling stops (MobileServiceStopPolicy). The dashboard sorts
     * them by day and time, so there is no reorder endpoint.
     */
    Route::prefix('koleksi/keliling')->name('koleksi.keliling.')->whereUlid('stop')->group(function (): void {
        Route::get('/', [MobileServiceStopController::class, 'index'])->can('viewAny', MobileServiceStop::class)->name('index');
        Route::post('/', [MobileServiceStopController::class, 'store'])->can('create', MobileServiceStop::class)->name('store');
        Route::post('/hapus', [MobileServiceStopDeletionController::class, 'store'])->can('deleteAny', MobileServiceStop::class)->name('hapus');
        Route::get('/{stop}', [MobileServiceStopController::class, 'show'])->can('view', 'stop')->name('show');
        Route::patch('/{stop}', [MobileServiceStopController::class, 'update'])->can('update', 'stop')->name('update');
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

    // Tarif & Simulasi (SettingPolicy): every employee reads it for the Kalkulator PKB.
    $tariff = "'".Setting::TARIFF."'";
    Route::get('/pengaturan/tarif', [TariffController::class, 'show'])
        ->can('view', [Setting::class, $tariff])
        ->name('pengaturan.tarif.show');
    Route::put('/pengaturan/tarif', [TariffController::class, 'update'])
        ->can('update', [Setting::class, $tariff])
        ->name('pengaturan.tarif.update');

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
        Route::get('/tarif', PublicTariffController::class)->name('tarif.show');
        Route::get('/statistik', PublicStatisticsController::class)->name('statistik.show');
        Route::get('/kontak', PublicContactController::class)->name('kontak.show');
        // The contact form; also limited to 5 messages per hour per address (D-016).
        Route::post('/pesan', PublicMessageController::class)->middleware('throttle:public-messages')->name('pesan.store');
    });

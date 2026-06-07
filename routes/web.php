<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\GuardController;
use App\Http\Controllers\DeliveryPersonnelController;
use App\Http\Controllers\DeliveryLogController;
use App\Http\Controllers\InquiryController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('login', [AdminAuthController::class, 'store'])->middleware('throttle:5,1');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::get('dashboard', [App\Http\Controllers\AdminDashboardController::class, 'index'])->name('dashboard');
        
        Route::get('face-test', function () {
            return Inertia::render('Admin/FaceTest');
        })->name('face-test');

        // Admin Profile
        Route::get('profile', [App\Http\Controllers\AdminAuthController::class, 'profile'])->name('profile');
        Route::patch('profile', [App\Http\Controllers\AdminAuthController::class, 'updateProfile'])->name('profile.update');

        // Visitor Routes
        Route::resource('visitors', VisitorController::class);
        Route::get('visit-logs', [App\Http\Controllers\VisitorController::class, 'logs'])->name('visit-logs.index');
        Route::get('visit-logs/export', [App\Http\Controllers\VisitorController::class, 'exportLogs'])->name('visit-logs.export');
        Route::get('visit-logs/{visit}', [App\Http\Controllers\VisitorController::class, 'showLog'])->name('visit-logs.show');

        // Guard Routes
        Route::resource('guards', GuardController::class);

        // Delivery Service
        Route::prefix('delivery')->name('delivery.')->group(function () {
            Route::resource('personnel', DeliveryPersonnelController::class);
            Route::get('logs', [DeliveryLogController::class, 'index'])->name('logs.index');
            Route::get('logs/export', [DeliveryLogController::class, 'exportLogs'])->name('logs.export');
        });

        // House Units
        Route::resource('units', App\Http\Controllers\HouseUnitController::class);

        // Residents
        Route::resource('residents', App\Http\Controllers\ResidentController::class);

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [App\Http\Controllers\ReportController::class, 'index'])->name('index');
            Route::get('/users', [App\Http\Controllers\ReportController::class, 'exportUsers'])->name('export-users');
            Route::get('/units', [App\Http\Controllers\ReportController::class, 'exportUnits'])->name('export-units');
            Route::get('/applications', [App\Http\Controllers\ReportController::class, 'exportApplications'])->name('export-applications');
            Route::get('/records', [App\Http\Controllers\ReportController::class, 'exportVisitRecords'])->name('export-records');
        });

        // Inquiries
        Route::get('inquiries', [InquiryController::class, 'adminIndex'])->name('inquiries.index');
        Route::post('inquiries/{inquiry}/resolve', [InquiryController::class, 'adminResolve'])->name('inquiries.resolve');
        Route::post('inquiries/{inquiry}/reply', [InquiryController::class, 'adminReply'])->name('inquiries.reply');

        // User Manual
        Route::get('manual', function () {
            return Inertia::render('Manual/Index');
        })->name('manual.index');

        Route::post('logout', [AdminAuthController::class, 'destroy'])->name('logout');
    });
});

Route::prefix('guard')->name('guard.')->group(function () {
    Route::middleware('guest:guard')->group(function () {
        Route::get('login', [App\Http\Controllers\GuardAuthController::class, 'create'])->name('login');
        Route::post('login', [App\Http\Controllers\GuardAuthController::class, 'store'])->middleware('throttle:5,1');

        // Password Reset Routes
        Route::get('forgot-password', [App\Http\Controllers\GuardPasswordResetController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [App\Http\Controllers\GuardPasswordResetController::class, 'store'])->name('password.email');
        Route::get('reset-password/{token}', [App\Http\Controllers\GuardPasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('reset-password', [App\Http\Controllers\GuardPasswordResetController::class, 'update'])->name('password.store');
    });

    Route::middleware('auth:guard')->group(function () {
        Route::get('dashboard', [App\Http\Controllers\GuardScanController::class, 'dashboard'])->name('dashboard');
        
        // QR Scanner routes
        Route::get('scan', [App\Http\Controllers\GuardScanController::class, 'index'])->name('scan');
        Route::post('scan/lookup', [App\Http\Controllers\GuardScanController::class, 'lookup'])->name('scan.lookup');
        Route::get('scan/{visit}', [App\Http\Controllers\GuardScanController::class, 'show'])->name('scan.show');
        Route::get('scan/delivery/{log}', [App\Http\Controllers\GuardScanController::class, 'showDelivery'])->name('scan.show-delivery');
        Route::get('scan/{visit}/verify', [App\Http\Controllers\GuardScanController::class, 'verify'])->name('scan.verify');
        Route::get('scan/delivery/{log}/verify', [App\Http\Controllers\GuardScanController::class, 'verifyDelivery'])->name('scan.verify-delivery');
        Route::post('scan/check-in', [App\Http\Controllers\GuardScanController::class, 'checkIn'])->name('scan.checkin');
        Route::post('scan/check-in-delivery', [App\Http\Controllers\GuardScanController::class, 'checkInDelivery'])->name('scan.checkin-delivery');
        Route::post('scan/check-out', [App\Http\Controllers\GuardScanController::class, 'checkOut'])->name('scan.checkout');
        Route::post('scan/check-out-delivery', [App\Http\Controllers\GuardScanController::class, 'checkOutDelivery'])->name('scan.checkout-delivery');
        
        // Active Logs route
        Route::get('logs/active', [App\Http\Controllers\GuardScanController::class, 'activeLogs'])->name('logs.active');
        
        // Visit Records route
        Route::get('visit-records', [App\Http\Controllers\GuardScanController::class, 'visitRecords'])->name('visit-records');
        
        // Registration routes
        Route::get('register', [App\Http\Controllers\GuardScanController::class, 'showRegistration'])->name('register');
        Route::post('register/visitor', [App\Http\Controllers\GuardScanController::class, 'registerVisitor'])->name('register.visitor');
        Route::post('register/delivery', [App\Http\Controllers\GuardScanController::class, 'registerDelivery'])->name('register.delivery');
        
        Route::get('profile', [App\Http\Controllers\GuardAuthController::class, 'profile'])->name('profile');
        Route::patch('profile', [App\Http\Controllers\GuardAuthController::class, 'updateProfile'])->name('profile.update');

        // User Manual
        Route::get('manual', function () {
            return Inertia::render('Manual/Index');
        })->name('manual.index');

        Route::post('logout', [App\Http\Controllers\GuardAuthController::class, 'destroy'])->name('logout');
    });
});

// Resident Routes
Route::prefix('resident')->name('resident.')->group(function () {
    Route::middleware('guest:resident')->group(function () {
        Route::get('login', [App\Http\Controllers\ResidentAuthController::class, 'create'])->name('login');
        Route::post('login', [App\Http\Controllers\ResidentAuthController::class, 'store'])->middleware('throttle:5,1');
        Route::get('verify/{token}', [App\Http\Controllers\ResidentAuthController::class, 'verify'])->name('verify');

        // Password Reset Routes
        Route::get('forgot-password', [App\Http\Controllers\ResidentPasswordResetController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [App\Http\Controllers\ResidentPasswordResetController::class, 'store'])->name('password.email');
        Route::get('reset-password/{token}', [App\Http\Controllers\ResidentPasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('reset-password', [App\Http\Controllers\ResidentPasswordResetController::class, 'update'])->name('password.store');
    });

    Route::middleware('auth:resident')->group(function () {
        Route::get('dashboard', [App\Http\Controllers\ResidentAuthController::class, 'dashboard'])->name('dashboard');
        
        // Visitor Management for Residents
        Route::get('visitors', [App\Http\Controllers\ResidentVisitorController::class, 'index'])->name('visitors.index');
        Route::get('visitors/create', [App\Http\Controllers\ResidentVisitorController::class, 'create'])->name('visitors.create');
        Route::post('visitors', [App\Http\Controllers\ResidentVisitorController::class, 'store'])->name('visitors.store');
        Route::post('visitors/{visit}/approve', [App\Http\Controllers\ResidentVisitorController::class, 'approve'])->name('visitors.approve');
        Route::post('visitors/{visit}/reject', [App\Http\Controllers\ResidentVisitorController::class, 'reject'])->name('visitors.reject');
        
        // Delivery Approvals for Residents
        Route::post('delivery-logs/{log}/approve', [App\Http\Controllers\ResidentVisitorController::class, 'approveDelivery'])->name('deliveries.approve');
        Route::post('delivery-logs/{log}/reject', [App\Http\Controllers\ResidentVisitorController::class, 'rejectDelivery'])->name('deliveries.reject');
        
        Route::get('profile', [App\Http\Controllers\ResidentAuthController::class, 'profile'])->name('profile');
        Route::patch('profile', [App\Http\Controllers\ResidentAuthController::class, 'updateProfile'])->name('profile.update');
        Route::get('family', [App\Http\Controllers\ResidentAuthController::class, 'family'])->name('family');

        // Contact Us / Inquiries
        Route::get('inquiries', [InquiryController::class, 'residentIndex'])->name('inquiries.index');
        Route::get('inquiries/create', [InquiryController::class, 'residentCreate'])->name('inquiries.create');
        Route::post('inquiries', [InquiryController::class, 'residentStore'])->name('inquiries.store');

        // User Manual
        Route::get('manual', function () {
            return Inertia::render('Manual/Index');
        })->name('manual.index');

        Route::post('logout', [App\Http\Controllers\ResidentAuthController::class, 'destroy'])->name('logout');
    });
});

// Public Pre-Registered Guest Pass
Route::get('/pass/{token}', [App\Http\Controllers\VisitorAuthController::class, 'showPublicPass'])->name('public.pass');
Route::post('/pass/{token}/complete', [App\Http\Controllers\VisitorAuthController::class, 'completePublicPass'])->name('public.pass.complete');

// Dynamic QR Code Generator API
Route::get('/qr-code', function (Illuminate\Http\Request $request) {
    $data = $request->query('data');
    if (empty($data)) {
        return response('No data provided', 400);
    }
    return response(SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->generate($data))
        ->header('Content-Type', 'image/svg+xml');
})->name('qr.dynamic');

// Visitor Routes (Public/Phone Auth)
Route::name('visitor.')->group(function () {
    // Convenience route for users typing /visitor/login manually
    Route::redirect('/visitor/login', '/')->name('login');
    
    Route::post('/otp/send', [App\Http\Controllers\VisitorAuthController::class, 'sendOtp'])->name('otp.send')->middleware('throttle:5,1');
    Route::post('/otp/verify', [App\Http\Controllers\VisitorAuthController::class, 'verifyOtp'])->name('otp.verify')->middleware('throttle:5,1');
    
    Route::middleware('guest:visitor')->group(function () {
        Route::get('/register', [App\Http\Controllers\VisitorAuthController::class, 'create'])->name('register');
        Route::post('/register', [App\Http\Controllers\VisitorAuthController::class, 'store'])->name('store')->middleware('throttle:10,1');
    });

    Route::middleware('auth:visitor')->group(function () {
        Route::get('/visitor/dashboard', [App\Http\Controllers\VisitorAuthController::class, 'dashboard'])->name('dashboard');
        Route::post('/visitor/visits', [App\Http\Controllers\VisitController::class, 'store'])->name('visits.store');
        Route::get('/visitor/visits/history', [App\Http\Controllers\VisitorAuthController::class, 'history'])->name('visits.history');
        Route::delete('/visitor/visits/{visit}', [App\Http\Controllers\VisitController::class, 'destroy'])->name('visits.destroy');
        Route::get('/visitor/visits/{visit}/qr', [App\Http\Controllers\VisitorAuthController::class, 'showQr'])->name('visits.qr');
        Route::get('/visitor/profile', [App\Http\Controllers\VisitorAuthController::class, 'profile'])->name('profile');
        Route::patch('/visitor/profile', [App\Http\Controllers\VisitorAuthController::class, 'updateProfile'])->name('profile.update');

        // Contact Us / Inquiries
        Route::get('/visitor/inquiries', [InquiryController::class, 'visitorIndex'])->name('inquiries.index');
        Route::get('/visitor/inquiries/create', [InquiryController::class, 'visitorCreate'])->name('inquiries.create');
        Route::post('/visitor/inquiries', [InquiryController::class, 'visitorStore'])->name('inquiries.store');

        // User Manual
        Route::get('/visitor/manual', function () {
            return Inertia::render('Manual/Index');
        })->name('manual.index');

        Route::post('/visitor/logout', [App\Http\Controllers\VisitorAuthController::class, 'destroy'])->name('logout');
    });
});

// Delivery Personnel Routes
Route::prefix('delivery')->name('delivery.')->group(function () {
    Route::middleware('guest:delivery')->group(function () {
        Route::get('/register', [App\Http\Controllers\DeliveryDashboardController::class, 'register'])->name('register');
        Route::post('/register', [App\Http\Controllers\DeliveryDashboardController::class, 'store'])->name('store')->middleware('throttle:10,1');
    });

    Route::middleware('auth:delivery')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\DeliveryDashboardController::class, 'index'])->name('dashboard');
        Route::get('/history', [App\Http\Controllers\DeliveryDashboardController::class, 'history'])->name('history');
        Route::post('/trips', [App\Http\Controllers\DeliveryDashboardController::class, 'createTrip'])->name('trips.store');
        Route::delete('/trips/{run}', [App\Http\Controllers\DeliveryDashboardController::class, 'cancelTrip'])->name('trips.cancel');
        Route::get('/profile', [App\Http\Controllers\DeliveryDashboardController::class, 'profile'])->name('profile');
        Route::patch('/profile', [App\Http\Controllers\DeliveryDashboardController::class, 'updateProfile'])->name('profile.update');

        // Contact Us / Inquiries
        Route::get('/inquiries', [InquiryController::class, 'deliveryIndex'])->name('inquiries.index');
        Route::get('/inquiries/create', [InquiryController::class, 'deliveryCreate'])->name('inquiries.create');
        Route::post('/inquiries', [InquiryController::class, 'deliveryStore'])->name('inquiries.store');

        // User Manual
        Route::get('/manual', function () {
            return Inertia::render('Manual/Index');
        })->name('manual.index');

        Route::post('/logout', [App\Http\Controllers\DeliveryDashboardController::class, 'destroy'])->name('logout');
    });
});

// Notifications API
Route::get('/api/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
Route::post('/api/notifications/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');


Route::get('/fix-storage-link', function () {
    $publicStoragePath = public_path('storage');
    $targetPath = storage_path('app/public');
    
    $out = [];
    $out[] = "Public storage path: " . $publicStoragePath;
    $out[] = "Target storage path: " . $targetPath;
    
    // Check if target exists
    if (!file_exists($targetPath)) {
        $out[] = "WARNING: Target path does not exist! Creating it...";
        mkdir($targetPath, 0755, true);
    }
    
    // Check if public/storage already exists
    if (file_exists($publicStoragePath) || is_link($publicStoragePath)) {
        $out[] = "Public storage exists or is a symlink. Checking type...";
        if (is_link($publicStoragePath)) {
            $out[] = "It is a symlink. Deleting link...";
            unlink($publicStoragePath);
        } else if (is_dir($publicStoragePath)) {
            $out[] = "It is a real folder. Renaming to backup...";
            rename($publicStoragePath, $publicStoragePath . '_backup_' . time());
        } else {
            $out[] = "It is a file. Deleting file...";
            unlink($publicStoragePath);
        }
    }
    
    // Create symlink
    try {
        if (symlink($targetPath, $publicStoragePath)) {
            $out[] = "SUCCESS: Created symbolic link successfully!";
        } else {
            $out[] = "FAILED: symlink() returned false.";
        }
    } catch (\Exception $e) {
        $out[] = "ERROR creating symlink: " . $e->getMessage();
    }
    
    return implode("<br>\n", $out);
});

Route::get('/run-migrations', function () {
    try {
        Illuminate\Support\Facades\Artisan::call('migrate');
        return 'Migration output: <br><pre>' . Illuminate\Support\Facades\Artisan::output() . '</pre>';
    } catch (\Exception $e) {
        return 'Error running migrations: ' . $e->getMessage();
    }
});

// User Manual Fallback — redirects authenticated users to their specific portal's manual
Route::get('/manual', function () {
    $guards = ['admin', 'guard', 'resident', 'visitor', 'delivery'];
    foreach ($guards as $guard) {
        if (auth($guard)->check()) {
            return redirect()->route($guard . '.manual.index');
        }
    }
    return redirect()->route('login');
})->name('manual.index');


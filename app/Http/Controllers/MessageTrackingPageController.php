<?php

namespace App\Http\Controllers;

use App\Http\PortalPage;
use Illuminate\Http\Response;

class MessageTrackingPageController extends Controller
{
    /**
     * Serve a message's tracking page at /pesan/{kode} (D-058). Its React
     * component reads the message from GET /api/public/pesan/{kode};
     * addresses are made root-relative, since they would otherwise resolve
     * under /pesan/.
     */
    public function __invoke(): Response
    {
        return PortalPage::render('portal.lacak-pesan');
    }
}

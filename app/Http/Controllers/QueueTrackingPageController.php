<?php

namespace App\Http\Controllers;

use App\Http\PortalPage;
use Illuminate\Http\Response;

class QueueTrackingPageController extends Controller
{
    /**
     * Serve the taxpayer's tracking page at /lacak/{kode}, the address the
     * queue ticket's QR holds (D-051). Its React component reads the ticket
     * from GET /api/public/antrean/{kode}; addresses are made root-relative,
     * since they would otherwise resolve under /lacak/.
     */
    public function __invoke(): Response
    {
        return PortalPage::render('portal.lacak');
    }
}

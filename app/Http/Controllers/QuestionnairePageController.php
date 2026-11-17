<?php

namespace App\Http\Controllers;

use App\Http\PortalPage;
use Illuminate\Http\Response;

class QuestionnairePageController extends Controller
{
    /**
     * The questionnaires a taxpayer or an operator fills in (D-095): one page
     * at /kuesioner, which offers both with their QR codes, and at
     * /kuesioner/{jenis}, where its React component asks the questions and
     * sends the answers to the questionnaire's Google Form. Addresses are made
     * root-relative, since they would otherwise resolve under /kuesioner/.
     */
    public function __invoke(): Response
    {
        return PortalPage::render('portal.kuesioner');
    }
}
